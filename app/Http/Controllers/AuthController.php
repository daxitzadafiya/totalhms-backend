<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator, DB, Hash, Mail;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\WelcomeMail;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication APIs",
 * )
 **/
class AuthController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Get the response for a successful password reset link.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return $this->responseSuccess('Password reset email sent.');
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
//        $result = [
//            'message' => 'Email could not be sent to this email address.',
//            'data' => $response
//        ];
        return $this->responseSuccess('Email could not be sent to this email address. ' . $response);
    }

    /**
     * Reset the given user's password.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     * @param string $password
     * @return void
     */
    protected function resetPassword($user)
    {
        $user->save();
        event(new PasswordReset($user));
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return $this->responseSuccess('Password reset successfully.');
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return $this->responseSuccess('Failed, Invalid Token.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Auth"},
     *     summary="Login API",
     *     description="Login API",
     *     operationId="loginApi",
     *     @OA\RequestBody(
     *         description="User name and Password to login",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *              @OA\Schema(
     *              required={"id"},
     *              @OA\Property(property="email", format="int64", type="string", default="worker@totalhms.com"),
     *              @OA\Property(property="password", format="int64", type="string", default="123123")
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
        
        if (!$token = auth()->attempt($credentials)) {
            return $this->responseException('Wrong email or password', 401);
        }
        $user = JWTAuth::user();
                
        if ($user->role->level > 0 && $user->role->level < 4 && $user->company->status != 'active') {
            return $this->responseException('Company is not activated', 401);
        }
        
        if ($user->status != 'active') {
            return $this->responseException('User is not activated', 401);
        }

        $permissionsKey = [];

        $checkSuperAdmin = $this->checkSuperAdmin($user);
        $checkCompanyAdmin = $this->checkCompanyAdmin($user);
        $checkCustomerServiceAdmin = $this->checkCustomerServiceAdmin($user);
        $is_super = false;
        $assign_group = false;
        if (!$checkSuperAdmin && !$checkCompanyAdmin && !$checkCustomerServiceAdmin) {
            $permissions = $user->permissions->permission;
            if ($user->permissions->is_super) {
                $is_super = true;
            }
            if ($user->permissions->assign_group) {
                $assign_group = true;
            }
            $user->department_id = $user->employee->department_id;
            $permissions = json_decode($permissions);
            if (!empty($permissions)) {
                foreach ($permissions as $function) {
                    $functionName = $function->name;
//                    $filterByManager = $function->managerPermission;

//                    if (!empty($filterByManager)) {
//                        foreach ($filterByManager as $permission) {
//                            $permissionName = $permission->name;
////                            if ($permission->company) {
////                                $applyFor = 'company';
////                            } else {
////                                $applyFor = 'manager';
////                            }
//                            if (($permissionName == 'view' || $permissionName == 'basic') && ($permission->type == 'boolean' && $permission->value)) {
//                                $permissionsKey[] = $functionName . '-' . $permissionName;
//                            }
//                        }
//                    }

                    $userPermission = $function->userPermission;
                    if (!empty($userPermission)) {
                        foreach ($userPermission as $permission) {
                            $permissionName = $permission->name;
                            $apply = $permission->apply;
//                            if ($permission->type == 'boolean' && ($permission->value || ($apply == 'group' || $apply == 'company'))) {
//                                $permissionsKey[] = $functionName . '-' . $permissionName;
//                            }
                            if ($permission->type == 'boolean' && ($permission->value || $apply == 'company')) {
                                $permissionsKey[] = $functionName . '-' . $permissionName;
                            }
                        }
                    }
                }
            }
        }


        $result = [
            'user_info' => $user,
            'permissionsKey' => $permissionsKey,
            'checkAdmin' => $checkCompanyAdmin,
            'checkSuperAdmin' => $checkSuperAdmin,
            'checkCustomerServiceAdmin' => $checkCustomerServiceAdmin,
            'is_super' => $is_super,
            'assign_group' => $assign_group,
            'base_url' => config('app.app_url'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 8
        ];

        return $this->responseSuccess($result);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout API",
     *     description="Logout API",
     *     operationId="logoutApi",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');

        try {
            JWTAuth::parseToken()->invalidate($token);

            return $this->responseSuccess("Logged out successfully");
        } catch (TokenExpiredException $e) {
            return $this->responseException('Token has expired', 401);
        } catch (TokenInvalidException $e) {
            return $this->responseException('Invalid token', 401);

        } catch (JWTException $e) {
            return $this->responseException($e->getMessage(), 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/forgot",
     *     tags={"Auth"},
     *     summary="Forgot password API",
     *     description="Forgot password API",
     *     operationId="forgotPassApi",
     *     @OA\RequestBody(
     *         description="Send email to request reset pass",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *              @OA\Schema(
     *              @OA\Property(property="email", format="int64", type="string", default="worker@totalhms.com")
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function forgot(Request $request)
    {
        $email = $request['email'];
        $user = User::where('email', $email)->first();

        if (empty($user)) {
            return $this->sendResetLinkFailedResponse($request, 'Not found user');
        }

        $status = $user->status;
        if ($status == 'pending') {
            $this->sendResetLinkFailedResponse($request, 'Unverified email');
        }

        $verificationCodeItem = VerificationCode::where('user_id')->first();

        $code = sha1(time() . $user->id);
        $today = new \DateTime('now');

        if (empty($verificationCodeItem)) {
            $dataVerificationCodeItem['company_id'] = $user->company_id;
            $dataVerificationCodeItem['user_id'] = $user->id;
            $dataVerificationCodeItem['email'] = $email;
            $dataVerificationCodeItem['action'] = 'reset password';
            $dataVerificationCodeItem['expired_time'] = $today->add(new \DateInterval('P2D')); //2 day
            $dataVerificationCodeItem['code'] = $code;

            VerificationCode::create($dataVerificationCodeItem);
        } else {
            $expired = new \DateTime($verificationCodeItem->expired_time);
            if ($today > $expired) {
                $dataVerificationCodeItem['code'] = $code;
                $dataVerificationCodeItem['expired_time'] = $today->add(new \DateInterval('P2D')); //2 day
                VerificationCode::update($dataVerificationCodeItem);
            } else {
                $code = $verificationCodeItem->code;
            }
        }

        $data = ([
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $email,
            'url' => config('app.site_url') . '/reset-password/' . $code,
        ]);
        Mail::to($request['email'])->send(new ResetPasswordMail($data));

        return $this->sendResetLinkResponse($request, '');
//        return $this->sendResetLinkEmail($request);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset/{token}",
     *     tags={"Auth"},
     *     summary="Reset password API",
     *     description="Reset password API",
     *     operationId="resetPassApi",
     *     @OA\Parameter(
     *         description="token",
     *         in="path",
     *         name="token",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Set new pass",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *              @OA\Schema(
     *              @OA\Property(property="password", format="int64", type="string", default="newp@ssword"),
     *              @OA\Property(property="email", format="int64", type="string", default="worker@totalhms.com")
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function callResetPassword(Request $request)
    {
        $code = $request['code'];
        $password = str_replace( ' ', '', $request['password']);
        $email = $request['email'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->responseException('Not found user', 401);
        }

        $verificationCodeItem = VerificationCode::where('code', $code)->first();

        if(!$verificationCodeItem){
            return $this->responseException('Invalid token', 400);
        }else{
            if ($verificationCodeItem->user_id != $user->id) {
                return $this->responseException('Invalid token', 401);
            } else {
                $user->password = $password;
                if ($user->status != 'active') {
                    $user->status = 'active';
                    $user->active_date = new \DateTime('now');
                }
                $user->update();
                // If the user shouldn't reuse the token later, delete the token
                $verificationCodeItem->delete();
                return $this->responseSuccess("Password has been updated successfully.");
            }
        }
    }

}