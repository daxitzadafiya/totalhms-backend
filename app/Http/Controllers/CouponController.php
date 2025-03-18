<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Illuminate\Support\Facades\Notification;
use App\Mail\CouponMail;
use App\Models\Company;
use App\Models\Coupon;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\NotifyCoupon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $result = Coupon::with('company')->get();

            return $this->responseSuccess($result);

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
            DB::beginTransaction();
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $input = $request->all();
            $rules = Coupon::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);

                return $this->responseError($errors, 400);
            }
            
            $companyArray = $input['companyArray'];
            if (!empty($companyArray)) {
                foreach ($companyArray as $id) {

                    $input['company_id'] = $id;
                    $couponData = Coupon::create($input);
                    $company = Company::where('id', $id)->first();
                    $emailContent = EmailContent::where('key', 'coupon_code')->first();
                    if (!$emailContent) {
                        return $this->responseException('Not found email content', 404);
                    }
                    $emailDescription = str_replace('{company_name}',$company['name'],$emailContent['description']);
                    $emailDescription = str_replace('{coupon_code}',$couponData->code, $emailDescription);

                    if($company->email){
                        try {
                            if(!empty($company->email)){
                                Notification::route('mail', $company->email)
                                    ->notify(new NotifyCoupon($emailContent, $couponData->code, $emailDescription));
                                $emailStatus = EmailLog::SENT;
                                // Mail::to($company->email)->send(new CouponMail($emailContent->subject,$emailDescription));
                            }
                        } catch (\Exception $e) {
                            Log::debug('Coupon mail issue : ', ['error' => $e]);
                            $emailStatus = EmailLog::FAIL;
                        }
                        EmailLog::create([
                            'company_id' => $company->id,
                            'type' => $emailContent->title,
                            'description' => $emailDescription,
                            'status' => $emailStatus,
                            'for_admin' => 1,
                        ]);
                    }
                    $companyUser = User::where('company_id',$company->id)->where('role_id',2)->first();
                    $this->pushNotification($user->id, $company->id, 2, [$companyUser], 'coupon', 'coupon', '', $couponData->code, 'invite');
                    DB::commit();
                }
                return $this->responseSuccess('Coupon created successfully.');
            }
        } catch (Exception $e) {
            DB::rollBack();
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

            $coupon = Coupon::findOrFail($id);
            if (empty($coupon)) {
                return $this->responseException('Not found coupon', 404);
            }

            return $this->responseSuccess($coupon);
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
            DB::beginTransaction();
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $input = $request->all();
            $coupon = Coupon::find($id);
            if (empty($coupon)) {
                return $this->responseException('Not found coupon', 404);
            }

            $rules = Coupon::$rules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);

                return $this->responseError($errors, 400);
            }

            $coupon->update($input);
            DB::commit();

            return $this->responseSuccess('Coupon updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
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

            $coupon = Coupon::findOrFail($id);
            if (empty($coupon)) {
                return $this->responseException('Not found coupon', 404);
            }

            $coupon->delete($id);

            return $this->responseSuccess("Delete coupon successfully.");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function couponCheck(Request $request)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $plan = Plan::findOrFail($request->plan_id);
            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }

            $coupon = Coupon::NotUse()->where('code', $request->coupon)->first();
            if (empty($coupon)) {
                return $this->responseException('Not found coupon', 404);
            }

            if($coupon){
                $discount = ($plan->price * $coupon->discount) / 100;
            }
            $data= [
                'coupon_code' => $coupon->code,
                'discount' => $discount,
                'discount_percentage' => $coupon->discount,
            ];
            return $this->responseSuccess($data);

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


}