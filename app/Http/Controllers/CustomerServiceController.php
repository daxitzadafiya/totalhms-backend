<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\CustomerServicePermission;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\RequestPushNotification;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\InviteCustomerService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use JWTAuth;
use Validator;

class CustomerServiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $users = User::whereHas('role', function ($query) {
                $query->where('level', 4);
            })->get();

            if ($users) {
                return $this->responseSuccess($users);
            } else {
                return $this->responseSuccess([]);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

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
            $input['role_id'] = 5; // role custome service

            $rules = User::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $newUser = User::create($input);
            
            //Send verify email
            $this->ActiveCustomerService($newUser);
            $this->pushNotification($user->id, null, 2, [$user->id], 'cs_invite', 'send_cs_invite', $newUser->id, $newUser->first_name, 'send'); 

            return $this->responseSuccess($newUser);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $userData = User::find($id);
            if (empty($userData)) {
                return $this->responseException('Not found user', 404);
            }
            return $this->responseSuccess($userData);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

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
            $userData = User::find($id);
            if (empty($userData)) {
                return $this->responseException('Not found user', 404);
            }

            $rules = User::$updateRules;
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            $oldEmail = $userData['email'];
            $userData->update($input);

            if (strcmp($oldEmail, $input['email']) != 0) {
                // Send verify email - email updated
                $this->ActiveCustomerService($userData);
                $this->pushNotification($user->id, '', 2, [$user->id], 'cs_invite', 'send_cs_invite', $userData->id, $userData->first_name, 'resend'); 
            }
            return $this->responseSuccess($userData);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $userData = User::find($id);
            if (empty($userData)) {
                return $this->responseException('Not found user', 404);
            }
            RequestPushNotification::where('feature_id',$id)->delete();
            User::destroy($id);
            return $this->responseSuccess("Delete user success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function ActiveCustomerService($user)
    {
        if ($user->status != 'pending') {
            return null;
        }

        $code = sha1(time() . $user->id);
        $today = new \DateTime('now');

        $dataVerificationCodeItem['user_id'] = $user->id;
        $dataVerificationCodeItem['email'] = $user->email;
        $dataVerificationCodeItem['action'] = 'active account';
        $dataVerificationCodeItem['expired_time'] = $today->add(new \DateInterval('P30D')); //30 day
        $dataVerificationCodeItem['code'] = $code;

        VerificationCode::create($dataVerificationCodeItem);

        $emailContent = EmailContent::where('key', 'invite_customer_service')->first();
        $emailDescription = str_replace('{user_name}', $user->first_name . ' ' . @$user->last_name, $emailContent['description']);

        $url = URL::signedRoute('customerService.invitation.show', ['code' => $code]);

        if($user->email){
            try {
                Notification::route('mail', $user->email)
                    ->notify(new InviteCustomerService($emailContent, $emailDescription,$url));
                $emailStatus = EmailLog::SENT;    
            } catch (\Exception $e) {
                info('notify-invite customer service, Erro:' . $e->getMessage());
                $emailStatus = EmailLog::FAIL;
            }

            EmailLog::create([
                'type' => $emailContent->title,
                'description' => $emailDescription,
                'status' => $emailStatus,
                'for_admin' => 1,
            ]);
        }
    }

    public function invitationShow($code)
    {
        $inviteLink = VerificationCode::where('code', $code)->first();

        if (empty($inviteLink)) {
            $msg = 'Link has been expired.';
            return view('invitation.invitation-failed', ['message' => $msg]);
        }
        return view('invitation.invite-customerService')->with(
            ['code' => $code, 'email' => $inviteLink->email]
        );
    }

    public function invitationAccepet(Request $request)
    {
        try {
            $verificationCode = VerificationCode::where('code', $request->code)->where('email', $request->email)->first();
            $user = User::where('email', $request->email)->first();
            
            if ($user->status == 'active') {
                $msg = 'The invitation has already been accepted.';
                return view('invitation.invitation-failed', ['message' => $msg]);
            }

            $user->status = 'active';
            $user->password = $request->password;
            $user->save();
            
            $verificationCode->code = null;
            $verificationCode->save();

            return redirect()->to(config('app.site_url'));
            
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function permissions(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $permissions = CustomerServicePermission::all();

            return $this->responseSuccess($permissions);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


    public function updatepermission(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $permissions = $request->all();

            foreach ($permissions as $permission) {

                CustomerServicePermission::where('module', $permission['module'])->update([
                    'is_enabled' => $permission['is_enabled'],
                ]);
            }

            return $this->responseSuccess($permissions);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getPermissions(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            
            $data = [];
            $permissions = CustomerServicePermission::all();

            foreach ($permissions as $key => $permission) {
                $data[$permission['module']] = $permission['is_enabled'];
            }
            
            return $this->responseSuccess($data);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    
}