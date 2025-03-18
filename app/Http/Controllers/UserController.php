<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\User;
use Validator, Mail, Config;
use Illuminate\Http\Request;
use JWTAuth;
use App\Mail\WelcomeMail;
use App\Models\BillingDetail;
use App\Models\DocumentAttachment;
use App\Models\Repository;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Str;
use DB;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Users APIs",
 * )
 **/
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users/me",
     *     tags={"Users"},
     *     summary="Current User Info",
     *     description="Get current user API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getCurrentUserApi",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function me()
    {
        if (!$user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }
        $user->role_name = $user->role->name;

        if ($user->role_id == 1 || $user->role_id == 5) { 
            return $this->responseSuccess($user); 
        }
        // billing module 
        if (count($user->company->subscriptions) == 0) {
            $user['subscription_message'] = 'User no subscription';
            return $this->responseSuccess($user);
        }
        $freezingSystem = Setting::where('key', 'freezing_system')->where('is_disabled', 1)->first();
        if ($freezingSystem) {
            if (($user->role->level === Role::COMPANY_ROLE_LEVEL || $user->role->level === Role::MANAGER_ROLE_LEVEL || $user->role->level === Role::USER_ROLE_LEVEL) && $freezingSystem->value === 'manually' && $user->company->is_freeze) {
                $user['subscription_message'] = 'Admin has freeze system for your company due to unpaid invoice';
                return $this->responseSuccess($user);
            }
            if (($user->role->level === Role::COMPANY_ROLE_LEVEL || $user->role->level === Role::MANAGER_ROLE_LEVEL || $user->role->level === Role::USER_ROLE_LEVEL) && $user->company->subscriptions &&  $freezingSystem->value === 'automatic') {
                $subscriptions = $user->company->subscriptions;
                foreach ($subscriptions as $subscription) {
                    $today = Carbon::now();
                    if ($subscription && $subscription->billing && (!$subscription->trial_end_at || Carbon::parse($subscription->trial_end_at) < $today) && !$subscription->cancelled_at) {
                        $billingDate = Carbon::parse($subscription->billed_at);

                        $status = $subscription->billing->billingDetail->status;
                        $freezingDays = Setting::where('key', 'freezing_days')->where('is_disabled', 1)->first();
                        if ($freezingDays && $status == BillingDetail::PENDING && Carbon::parse($billingDate)->diffInDays($today) > $freezingDays->value) {
                            $user['subscription_message'] = 'The bill is due, please pay it';
                            return $this->responseSuccess($user);
                        }
                    }
                }
            }
        }

        // Check Storage of company
        if ($user->role->level === Role::COMPANY_ROLE_LEVEL && $user->company->subscriptions && "Storage" == "Skip for now") {
            $subscriptions = $user->company->subscriptions;
            foreach ($subscriptions as $subscription) {
                if ($subscription->plan_id) {
                    $storageUpload = DocumentAttachment::leftJoin('documents_new', 'documents_attachments.document_id', 'documents_new.id')
                        ->where('documents_new.company_id', $subscription->company_id)->where('documents_new.delete_status', 0)
                        ->sum('documents_attachments.file_size');

                    $storageRepo = Repository::where('company_id', $subscription->company_id)->whereNotNull('attachment_uri')
                        ->whereNull('restore_date')->sum('attachment_size');

                    $totalStorage = $subscription->plan_detail['user_per_storage'] * $subscription->plan_detail['total_users'];

                    $useStorage = $storageUpload + $storageRepo;

                    if ($totalStorage < $useStorage) {
                        $user['subscription_message'] = 'There is no more space in the storage, please purchase an addon';
                        return $this->responseSuccess($user);
                    }
                }
            }
        }

        if ($user->role_id > 1) {
            $user->company_name = $user->company->name;

            return $this->responseSuccess($user);
        }


        return $this->responseSuccess($user);
    }

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

            $result = User::leftJoin('companies', 'users.company_id', '=', 'companies.id')
                ->where('companies.status', 'active')
                ->where('users.role_id', 2)
                ->select('users.*', 'companies.name as company_name')
                ->get();
            if ($result) {
                return $this->responseSuccess($result);
            } else {
                return $this->responseSuccess([]);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function employees(Request $request)
    {
        try {
            $employeeIDs = isset($request['emp_ids']) && !empty($request['emp_ids']) ? explode(',',$request['emp_ids']) : [];
            $depIDs = isset($request['dep_ids']) && !empty($request['dep_ids']) ? explode(',',$request['dep_ids']) : [];
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($user['company_id'])) {
                    return $this->responseException('User company not found!.', 404);
                }

                $result = Employee::leftJoin(DB::raw(
                    '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                         users.active_date, users.status, users.avatar, users.zip_code, users.personal_number, users.phone_number,
                         users.created_at, roles.name as role_name, roles.level as role_level
                        FROM users
                        LEFT JOIN roles
                        ON users.role_id = roles.id) AS UR'
                ), 'employees.user_id', '=', 'UR.id')
                    ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                    ->leftJoin('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
                    ->leftJoin(DB::raw('(SELECT user_id, name FROM employee_relations WHERE is_primary = 1) AS ER'), 'ER.user_id', '=', 'UR.id')
                    ->where('UR.company_id', $user['company_id'])
                    ->where('UR.role_id', '>=', 4)
                    ->whereNotIn('UR.id', $employeeIDs);

                if(isset($depIDs) && !empty($depIDs)){
                    $result->whereIn('employees.department_id', $depIDs);
                }
                    

                $result = $result->select(
                    'UR.*',
                    'employees.department_id',
                    'departments.name as department_name',
                    'departments.parent_id as department_parent',
                    'employees.nearest_manager',
                    'employees.hourly_salary',
                    'employees.overtime_pay',
                    'employees.overtime_pay',
                    'employees.night_allowance',
                    'employees.holidays',
                    'employees.tax',
                    'employees.disable_status',
                    'ER.name as primary_employee_relative_name',
                    'employees.job_title_id as job_title_id',
                    'job_titles.name as job_title_name',
                    'job_titles.added_by as job_title_added_by'
                )
                    ->get();
                if ($result) {
                    foreach ($result as $r) {
                        $fname = !empty($r->first_name) ? $r->first_name : '';
                        $lname = !empty($r->last_name) ? $r->last_name : '';
                        $r->full_name = $fname . ' ' . $lname;
                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function managers(Request $request)
    {
        try {
            $managerIDs = isset($request['manager_ids']) && !empty($request['manager_ids']) ? explode(',',$request['manager_ids']) : [];
            $depIDs = isset($request['dep_ids']) && !empty($request['dep_ids']) ? explode(',',$request['dep_ids']) : [];
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($user['company_id'])) {
                    return $this->responseException('User company not found!.', 404);
                }


                $result = Employee::leftJoin(DB::raw(
                    '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                         users.active_date, users.status, users.avatar, users.zip_code, users.personal_number, users.phone_number,
                         users.created_at, roles.name as role_name, roles.level as role_level
                        FROM users
                        LEFT JOIN roles
                        ON users.role_id = roles.id) AS UR'
                ), 'employees.user_id', '=', 'UR.id')
                    ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                    ->leftJoin('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
                    ->leftJoin(DB::raw('(SELECT user_id, name FROM employee_relations WHERE is_primary = 1) AS ER'), 'ER.user_id', '=', 'UR.id')
                    ->where('UR.company_id', $user['company_id'])
                    ->where('UR.role_id', '=', 3)
                    ->where('UR.id', '!=', $user['id'])
                    ->whereNotIn('UR.id', $managerIDs);

                if(isset($depIDs) && !empty($depIDs)){
                    $result->whereIn('employees.department_id', $depIDs);
                }
                $result = $result->select(
                    'UR.*',
                    'employees.department_id',
                    'departments.name as department_name',
                    'departments.parent_id as department_parent',
                    'employees.nearest_manager',
                    'employees.hourly_salary',
                    'employees.overtime_pay',
                    'employees.overtime_pay',
                    'employees.night_allowance',
                    'employees.holidays',
                    'employees.tax',
                    'employees.disable_status',
                    'ER.name as primary_employee_relative_name',
                    'employees.job_title_id as job_title_id',
                    'job_titles.name as job_title_name',
                    'job_titles.added_by as job_title_added_by'
                )
                    ->get();
                if ($result) {
                    foreach ($result as $r) {
                        $fname = !empty($r->first_name) ? $r->first_name : '';
                        $lname = !empty($r->last_name) ? $r->last_name : '';
                        $r->full_name = $fname . ' ' . $lname;
                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
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
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $input = $request->all();
                $input['added_by'] = 1;
                $input['role_id'] = 2; // role Company admin

                $rules = User::$rules;
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $userData = User::where('company_id', $input['company_id'])
                    ->where('role_id', 2)->first();
                if (!empty($userData)) {
                    return $this->responseException('This company already have a company admin.', 404);
                } else {
                    $newUser = User::create($input);

                    // add new employee
                    $employeeRules = Employee::$rules;
                    $employee['user_id'] = $newUser->id;
                    $employeeValidator = Validator::make($employee, $employeeRules);

                    if ($employeeValidator->fails()) {
                        $errors = ValidateResponse::make($employeeValidator);
                        return $this->responseError($errors, 400);
                    }
                    Employee::create($employee);

                    //Send verify email
                    $this->sendActiveAccountEmail($newUser);

                    return $this->responseSuccess($newUser);
                }
            }
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
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $userData = User::find($id);
                if (empty($userData)) {
                    return $this->responseException('Not found user', 404);
                }
                return $this->responseSuccess($userData);
            }
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
            } else {
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
                    //Send verify email - email updated
                    $this->sendActiveAccountEmail($userData);
                }

                return $this->responseSuccess($userData);
            }
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
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $userData = User::find($id);
                if (empty($userData)) {
                    return $this->responseException('Not found user', 404);
                }
                User::destroy($id);
                return $this->responseSuccess("Delete user success");
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}