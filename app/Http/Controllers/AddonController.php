<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\JsonResponse;
use app\helpers\ValidateResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Addon;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Notifications\NotifyAddonCancel;
use Carbon\Carbon;
use Validator;
use Exception;
use JWTAuth;
use Fiken;
use Illuminate\Support\Facades\Notification;

class AddonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            if ($user->role->level > 1 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            if ($user->role->level == 0 || $user->role->level == 4) {
                $addons = Addon::all();
            } else {
                $addons = Addon::with('ActiveAddon')->withTrashed()->whereNull('deleted_at')
                    ->orWhere(function ($q) {
                        $q->whereHas('subscriptions', function ($query) {
                            $query->whereNull('deactivated_at');
                        });
                    })->get();
            }

            foreach ($addons as $addon) {
                $addon['stripe_system'] = Setting::where('key', 'stripe_system')->value('is_disabled');
                $addon['fiken_system'] = Setting::where('key', 'fiken_system')->value('is_disabled');
                $addon['addon_deleted'] = !!$addon->deleted_at;
            }

            return $this->responseSuccess($addons);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $input = $request->all();
            $rules = Addon::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $vatSetting = Setting::where('key', 'vat')->where('is_disabled', 1)->first();

            $vat = $vatSetting ? $vatSetting->value : 0;

            $addon = Addon::latest()->first();

            $productNumber = $addon ? $addon->id + 1 : 1;
            $input['fiken_product_number'] = str_pad($productNumber, 4, "0", STR_PAD_LEFT);

            $createProductFiken = Fiken::createProduct($input, $vat);
            if(@$createProductFiken['error']){
                Helper::SendEmailIssue($createProductFiken['error']);
                throw new Exception($createProductFiken['error']);
            }

            $input['fiken_addon_id'] = $createProductFiken['productId'];
            Addon::create($input);

            return $this->responseSuccess('Addon created successfully.');
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $plan = Addon::findOrFail($id);
            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }
            
            return $this->responseSuccess($plan);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  \App\Addon  $addon
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $input = $request->all();
            $addon = Addon::find($id);
            if (empty($addon)) {
                return $this->responseException('Not found addon', 404);
            }

            $rules = Addon::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);

                return $this->responseError($errors, 400);
            }
            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();
            
            $vat = $vatSetting ? $vatSetting->value : 0;

            if ($addon->fiken_addon_id) {
                $updateProductFiken = Fiken::updateProduct($addon->fiken_addon_id, $input, $vat);
                if(@$updateProductFiken['error']){
                    Helper::SendEmailIssue($updateProductFiken['error']);
                    throw new Exception($updateProductFiken['error']);
                }
                $productId = $updateProductFiken['productId'];
            } else {
                $input['fiken_product_number'] = str_pad($addon->id, 4, "0", STR_PAD_LEFT);
                $createProductFiken = Fiken::createProduct($input, $vat);
                if(@$createProductFiken['error']){
                    Helper::SendEmailIssue($createProductFiken['error']);
                    throw new Exception($createProductFiken['error']);
                }
                $productId = $createProductFiken['productId'];
            }

            $input['fiken_addon_id'] = $productId;
            $addon->update($input);

            return $this->responseSuccess('Plan updated successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Addon  $addon
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $addon = Addon::findOrFail($id);
            if (empty($addon)) {
                return $this->responseException('Not found addon', 404);
            }

            foreach ($addon->ActiveAddons as $subscription) {
                $subscription->billed_at = $subscription->next_billing_at;
                $subscription->next_billing_at = null;
                $subscription->cancelled_at = Carbon::now();
                $subscription->save();

                $deadline = date_format(date_create($subscription->billed_at), 'd.m.Y');
                
                if ($subscription->company->email) {
                    $emailContent = EmailContent::where('key', 'reminder_addon_cancel')->first();
                    $emailDescription = str_replace('{company_name}', $subscription->company->name, $emailContent['description'],$deadline);
                    $emailDescription = str_replace('{addon_name}', $subscription->addon_detail['title'], $emailDescription);
                    try {
                        Notification::route('mail', $subscription->company->email)
                                    ->notify(new NotifyAddonCancel($emailContent, $emailDescription,$subscription->addon_detail['title'],$deadline));
                        $emailStatus = EmailLog::SENT;
                    } catch (\Exception $e) {
                        info('notify-purchase, Erro:' . $e->getMessage());
                        $emailStatus = EmailLog::FAIL;
                    }

                    EmailLog::create([
                        'company_id' => $subscription->company->id,
                        'type' => $emailContent->title,
                        'description' => 'Addon has been deleted by supper admin,please change your addon before expiry date.',
                        'status' => $emailStatus,
                        'for_admin' => 1,
                    ]);

                }
                $this->pushNotification($user->id, $subscription->company->id, 2, [$subscription->user_id], 'cancel', 'addon_cancel', $subscription->id, $addon->title, 'addon_cancel',null, $deadline);
            }

            $addon->delete($id);

            return $this->responseSuccess("Delete addon success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}