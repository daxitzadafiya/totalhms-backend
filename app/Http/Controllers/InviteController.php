<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use app\helpers\ValidateResponse;
use App\Imports\InviteImport;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Employee;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use JWTAuth;
use App\Models\Invite;
use App\Models\RequestPushNotification;
use App\Models\User;
use App\Notifications\AcceptInvitaion;
use App\Notifications\InviteMember;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Users APIs",
 * )
 **/
class InviteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Get users",
     *     description="Get users list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUsers",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $result = Invite::with(['company', 'role', 'department', 'jobTitle'])->get();
            if ($result) {
                return $this->responseSuccess($result);
            } else {
                return $this->responseSuccess([]);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create new user",
     *     description="Create new user",
     *     security={{"bearerAuth":{}}},
     *     operationId="createUser",
     *     @OA\RequestBody(
     *         description="User schemas",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
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
            $input['role_id'] = 4; // role employee
            $rules = Invite::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $inviteUser = Invite::create($input);

            $token = sha1(time() . $inviteUser->id);
            $today = new \DateTime('now');

            $inviteUser->token = $token;
            $inviteUser->expired_time = $today->add(new \DateInterval('P2D'));
            $inviteUser->save();

            $this->sendEmail($inviteUser,$token);

            $company_user = User::where('company_id',$inviteUser->company_id)->where('role_id',2)->first();
            $this->pushNotification($user->id, $inviteUser->company_id, 2, [$company_user->id], 'invite', 'send_invite', $inviteUser->id, $inviteUser->first_name, 'send');

            return $this->responseSuccess($inviteUser);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get user by id",
     *     description="Get user by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUserByIdAPI",
     *     @OA\Parameter(
     *         description="user id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $userData = Invite::with(['company', 'role', 'department', 'jobTitle'])->find($id);
            if (empty($userData)) {
                return $this->responseException('Not found user', 404);
            }
            return $this->responseSuccess($userData);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update user API",
     *     description="Update user API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateUserAPI",
     *     @OA\Parameter(
     *         description="user id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="User schemas",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
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
            $inviteUser = Invite::find($id);
            if (empty($inviteUser)) {
                return $this->responseException('Not found user', 404);
            }

            $input['role_id'] = 4; // role employee
            $rules = Invite::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $inviteUser->update($input);

            return $this->responseSuccess($inviteUser);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user API",
     *     description="Delete user API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteUserAPI",
     *     @OA\Parameter(
     *         description="user id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
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

            $inviteUser = Invite::find($id);
            if (empty($inviteUser)) {
                return $this->responseException('Not found user', 404);
            }
            RequestPushNotification::where('feature_id',$id)->delete();
            Invite::destroy($id);
            
            return $this->responseSuccess("Delete user success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function invitationShow($token)
    {
        $inviteUser = Invite::where('token', $token)->first();

        if (empty($inviteUser)) {
            $msg = 'Link has been expired.';
            return view('invitation.invitation-failed', ['message' => $msg]);
        }
        return view('invitation.invite')->with(
            ['token' => $token, 'email' => $inviteUser->email]
        );
    }

    public function invitationAccepet(Request $request)
    {
        try {
            DB::beginTransaction();

            $inviteUser = Invite::where('token', $request->token)->where('email', $request->email)->first();
            $user = User::where('email', $request->email)->first();

            if ($user) {
                $msg = 'The invitation has already been accepted.';
                return view('invitation.invitation-failed', ['message' => $msg]);
            }

            $data['first_name'] = $inviteUser->first_name;
            $data['last_name'] = $inviteUser->last_name;
            $data['email'] = $inviteUser->email;
            $data['password'] = $request->password;
            $data['company_id'] = $inviteUser->company_id;
            $data['role_id'] = $inviteUser->role_id;
            $data['address'] = $inviteUser->address;
            $data['city'] = $inviteUser->city;
            $data['phone_number'] = $inviteUser->phone_number;
            $data['personal_number'] = $inviteUser->personal_number;
            $data['avatar'] = $inviteUser->avatar;
            $data['zip_code'] = $inviteUser->zip_code;
            $data['added_by'] = 1;
            $data['status'] = 'active';

            $user = User::create($data);

            $employee['user_id'] = $user->id;
            $employee['department_id'] = $inviteUser->department_id;
            $employee['job_title_id'] = $inviteUser->job_title_id;

            Employee::create($employee);

            $inviteUser->token = null;
            $inviteUser->status = Invite::ACCEPTED;
            $inviteUser->save();

            $emailContent = EmailContent::where('key', 'accept_invite')->first();
            $emailDescription = str_replace('{user_name}', $inviteUser->first_name . ' ' . $inviteUser->last_name, $emailContent['description']);

            if ($inviteUser->email) {
                try {
                    Notification::route('mail', $inviteUser->email)
                        ->notify(new AcceptInvitaion($emailContent, $emailDescription));
                    $emailStatus = EmailLog::SENT;
                } catch (\Exception $e) {
                    info('notify-invite member, Erro:' . $e->getMessage());
                    $emailStatus = EmailLog::FAIL;
                }

                EmailLog::create([
                    'company_id' => $inviteUser->company_id,
                    'type' => $emailContent->title,
                    'description' => $emailDescription,
                    'status' => $emailStatus,
                    'for_admin' => 1,
                    'for_company' => 1,
                ]);
            }
            $company_user = User::where('company_id',$inviteUser->company_id)->where('role_id',2)->first();
            $this->pushNotification($user->id, $inviteUser->company_id, 2, [$company_user->id], 'invite', 'accept_invite', $inviteUser->id, $inviteUser->first_name, 'accept');
            
            DB::commit();
            return redirect()->to(config('app.site_url'));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function resendInvitation($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $inviteUser = Invite::find($id);
            if (empty($inviteUser)) {
                return $this->responseException('Not found user', 404);
            }

            $token = sha1(time() . $inviteUser->id);
            $today = new \DateTime('now');

            $inviteUser->token = $token;
            $inviteUser->expired_time = $today->add(new \DateInterval('P2D'));
            $inviteUser->save();

            $this->sendEmail($inviteUser,$token);

            $company_user = User::where('company_id',$inviteUser->company_id)->where('role_id',2)->first();
            $this->pushNotification($user->id, $inviteUser->company_id, 2, [$company_user->id], 'invite', 'send_invite', $inviteUser->id, $inviteUser->first_name, 'resend');

            return $this->responseSuccess("resend Invitation successfully.");
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function sendEmail($inviteUser,$token)
    {
        $emailContent = EmailContent::where('key', 'invite_member')->first();
        $emailDescription = str_replace('{user_name}', $inviteUser->first_name . ' ' . $inviteUser->last_name, $emailContent['description']);

        $url = URL::signedRoute('invitation.show', ['token' => $token]);

        if ($inviteUser->email) {
            try {
                Notification::route('mail', $inviteUser->email)
                    ->notify(new InviteMember($emailContent, $emailDescription, $url));
                $emailStatus = EmailLog::SENT;
            } catch (\Exception $e) {
                info('notify-invite member, Erro:' . $e->getMessage());
                $emailStatus = EmailLog::FAIL;
            }

            EmailLog::create([
                'company_id' => $inviteUser->company_id,
                'type' => $emailContent->title,
                'description' => $emailDescription,
                'status' => $emailStatus,
                'for_admin' => 1,
                'for_company' => 1,
            ]);
        }   
    }

    public function inviteImport(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $company_id = $request->company_id;
            Excel::import(new InviteImport($company_id), request()->file('file'));

            return $this->responseSuccess('Invites imported successfully');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function fileShow($fileName) {

        try {
            //This method will look for the file and get it from drive
            $path = storage_path('app/uploads/invites/' . $fileName);
            $header = array(
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment',
                'filename' => $fileName,
            );
            // auth code
            return Response::download($path, $fileName, $header);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}