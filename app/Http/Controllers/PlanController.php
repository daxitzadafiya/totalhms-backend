<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Models\EmailContent;
use App\Models\EmailLog;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Plan;
use App\Notifications\NotifyPlanCancel;
use Carbon\Carbon;
use Exception;
use Validator;
use JWTAuth;
use Fiken;
use Illuminate\Support\Facades\Notification;

class PlanController extends Controller
{
     /**       
     * @return Response
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

            if($user->role->level == 0 || $user->role->level == 4){
                $plans = Plan::all();
            } else {
                $plans = Plan::with('ActiveSubscription')->withTrashed()->whereNull('deleted_at')
                    ->orWhere(function ($q) {
                        $q->whereHas('subscriptions', function ($query) {
                            $query->whereNull('deactivated_at');
                        });
                    })->get();
            }

            foreach ($plans as $plan) {
                $plan['stripe_system'] = Setting::where('key', 'stripe_system')->value('is_disabled');
                $plan['fiken_system'] = Setting::where('key', 'fiken_system')->value('is_disabled');
                $plan['plan_deleted'] = !!$plan->deleted_at;
            }  

            return $this->responseSuccess($plans);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @param  Request  $request
     *
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
            $rules = Plan::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();

            $vat = $vatSetting ? $vatSetting->value : 0;

            $plan = Plan::latest()->first();

            $productNumber = $plan ? $plan->id + 1 : 1;
            $input['fiken_product_number'] = str_pad($productNumber, 4, "0", STR_PAD_LEFT);

            $createPlanFiken = Fiken::createProduct($input, $vat);
            if(@$createPlanFiken['error']){
                Helper::SendEmailIssue($createPlanFiken['error']);
                throw new Exception($createPlanFiken['error']);
            }
            $createPlanAdditionalFiken = Fiken::createProductAdditional($input, $vat);

            $input['fiken_plan_id'] = $createPlanFiken['productId'];
            $input['fiken_additional_id'] = $createPlanAdditionalFiken['productId'];

            Plan::create($input);

            return $this->responseSuccess('Plan created successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @param $id
     *
     * @return Response
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

            $plan = Plan::findOrFail($id);
            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }

            return $this->responseSuccess($plan);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @param  Request  $request
     * @param $id
     *
     * @return Response
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
            $plan = Plan::find($id);
            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }

            $rules = plan::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();
            
            $vat = $vatSetting ? $vatSetting->value : 0;

            if ($plan->fiken_plan_id) {
                $updateProductFiken = Fiken::updateProduct($plan->fiken_plan_id, $input, $vat);
                if(@$updateProductFiken['error']){
                    Helper::SendEmailIssue($updateProductFiken['error']);
                    throw new Exception($updateProductFiken['error']);
                }
                $updateProductAdditionalFiken = Fiken::updateProductAdditional($plan->fiken_plan_id, $input, $vat);

                $productId = $updateProductFiken['productId'];
                $productAdditionalId = $updateProductAdditionalFiken['productId'];
            } else {
                $input['fiken_product_number'] = str_pad($plan->id, 4, "0", STR_PAD_LEFT);
                $createPlanFiken = Fiken::createProduct($input, $vat);
                if(@$createPlanFiken['error']){
                    Helper::SendEmailIssue($createPlanFiken['error']);
                    throw new Exception($createPlanFiken['error']);
                }
                $createPlanAdditionalFiken = Fiken::createProductAdditional($input, $vat);

                $productId = $createPlanFiken['productId'];
                $productAdditionalId = $createPlanAdditionalFiken['productId'];
            }

            $input['fiken_plan_id'] = $productId;
            $input['fiken_additional_id'] = $productAdditionalId;
            $plan->update($input);

            return $this->responseSuccess('Plan updated successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @param $id
     *
     * @return Response
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
            
            $plan = Plan::findorFail($id);
            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }
            foreach ($plan->ActiveSubscriptions as $subscription) {
                $subscription->billed_at = $subscription->next_billing_at;
                $subscription->next_billing_at = null;
                $subscription->cancelled_at = Carbon::now();
                $subscription->save();
                $deadline = date_format(date_create($subscription->billed_at), 'd.m.Y');

                if ($subscription->company->email) {
                    $emailContent = EmailContent::where('key', 'reminder_plan_cancel')->first();
                    $emailDescription = str_replace('{company_name}', $subscription->company->name, $emailContent['description']);
                    $emailDescription = str_replace('{plan_name}', $subscription->plan_detail['title'], $emailDescription);

                    try {
                        Notification::route('mail', $subscription->company->email)
                                    ->notify(new NotifyPlanCancel($emailContent, $emailDescription,$subscription->plan_detail['title'],$deadline));
                        $emailStatus = EmailLog::SENT;
                    } catch (\Exception $e) {
                        info('notify-purchase, Erro:' . $e->getMessage());
                        $emailStatus = EmailLog::FAIL;
                    }

                    EmailLog::create([
                        'company_id' => $subscription->company->id,
                        'type' => $emailContent->title,
                        'description' => 'plan has been deleted by supper admin please change your plan before expiry date',
                        'status' => $emailStatus,
                        'for_admin' => 1,
                    ]);

                }
                $this->pushNotification($user->id, $subscription->company->id, 2, [$subscription->user_id], 'cancel', 'plan_cancel', $subscription->id, $plan->title, 'plan_cancel',null,$deadline);
            }

            $plan->delete($id);

            return $this->responseSuccess("Delete plan success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    } 
}