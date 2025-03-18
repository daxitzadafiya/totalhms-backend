<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Imports\UsersImport;
use App\Mail\WelcomeMail;
use App\Models\AbsenceReason;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentNew;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\UserPermission;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use JWTAuth;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\EmployeeRelation;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee APIs",
 * )
 **/
class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/employees",
     *     tags={"Employees"},
     *     summary="Get employees",
     *     description="Get employees list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getEmployees",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('employee', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $department = $request->department;
                $role = $request->role;
                $jobTitle = $request->job_title;
                $arr_department = $request->arr_department;
                $arr_job_title = $request->arr_job_title;
                $filterBy = $request->filterBy; 
                $result = Employee::leftJoin(DB::raw(
                    '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                     users.active_date, users.status, users.avatar, users.zip_code, users.personal_number, users.phone_number,
                     users.created_at, roles.name as role_name, roles.level as role_level
                    FROM users
                    LEFT JOIN roles
                    ON users.role_id = roles.id) AS UR'), 'employees.user_id', '=', 'UR.id')
                    ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                    ->leftJoin('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
                    ->leftJoin(DB::raw('(SELECT user_id, name FROM employee_relations WHERE is_primary = 1) AS ER'), 'ER.user_id','=', 'UR.id')
                    ->where('UR.company_id', $user->company_id);
                if ($filterBy == 'security' && $user->role_id !== 2) {
                    $result = $this->getByRole($result, $user);
                }
                if ($department) {
                    $result = $this->getByDepartment($result, $department);
//                    $result = $result->where('employees.department_id', $department);
                }
                if ($role) {
                    $result = $result->where('UR.role_id', $role);
                }
                if ($jobTitle) {
                    $result = $result->where('employees.job_title_id', $jobTitle);
                }
                // filter with list department + list job title
                if ($arr_department && $arr_job_title) {
                    $result = $result->whereIn('employees.department_id', $arr_department)
                        ->orWhereIn('employees.job_title_id', $arr_job_title);
                } else if ($arr_department) {
                    $result = $this->getByMultipleDepartment($result, $arr_department);
//                    $result = $result->whereIn('employees.department_id', $arr_department);
                } else if ($arr_job_title) {
                    $result = $result->whereIn('employees.job_title_id', $arr_job_title);
                }
                $result = $result->select('UR.*', 'employees.department_id', 'departments.name as department_name',
                    'departments.parent_id as department_parent','employees.nearest_manager',
                    'employees.hourly_salary','employees.overtime_pay', 'employees.overtime_pay',
                    'employees.night_allowance', 'employees.holidays', 'employees.tax', 'employees.disable_status',
                    'ER.name as primary_employee_relative_name', 'employees.job_title_id as job_title_id',
                    'job_titles.name as job_title_name', 'job_titles.added_by as job_title_added_by')
                    ->get();
                if($result){
                    foreach ($result as $k=> $emp) {
//                        $documents = Document::where('employee_id', $emp['id'])
//                            ->where(function ($q) {
//                                $q->where('category_id', 9)->where('uri', '<>', null)
//                                    ->orWhere('category_id', '<>', 9);
//                            })->get();

                        $documents = DocumentNew::where('company_id', $user['company_id'])
                            ->where('object_type', 'employee')
                            ->where('object_id', $emp['id'])
                            ->get();
                        $emp->count_attachment = count($documents);
                    }
                    foreach ($result as $employee){
                        $employee->name = $employee->first_name . ' ' . $employee->last_name;
                    }
                    $result[] = ([
                        'id'=>'anonymous',
                        'name'=>'Anonymous',
                    ]);
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getByRole($employees, $user)
    {
        $employees = $employees->where('employees.user_id', '<>', $user->id); // remove login user
        $isSuper = $user->permissions->is_super;
        if ($isSuper) {
            $departments = Department::where ('departments.company_id', $user->company_id)->get();
            $list = $this->getIdSubDepartment($departments, $user->employee->department_id);
            array_push($list, $user->employee->department_id);
            return $result = $this->getByMultipleDepartment($employees, $list);
        } else {
            $result = $this->getByDepartment($employees, $user->employee->department_id);
            if ($user->role_id === 4) {
                return $result->where('UR.role_id', '<>', 3);
            }
        }
    }

    public function getByDepartment($result, $department)
    {
        return $result->where('department_id', $department);
    }

    public function getByMultipleDepartment($result, $department)
    {
        return $result->whereIn('employees.department_id', $department);
    }

    public function getIdSubDepartment($departments, $parent_id = null)
    {
        $result = array();
        foreach ($departments as $key => $item)
        {
            if ($item['parent_id'] == $parent_id)
            {
                array_push($result, $item['id']);
                $temp = $this->getIdSubDepartment($departments, $item['id']);
                $result = array_merge($result, $temp);
            }
        }
        return $result;
    }

    public function getAbsenceProcessor($absenceReasonId)
    {
        if (!$user = JWTAuth::parseToken()->authenticate()) {
            return $this->responseException('Not found user', 404);
        } else {
            $absenceReason = AbsenceReason::find($absenceReasonId);
            if (!$absenceReason) {
                return $this->responseException('Not found reason', 404);
            }
            $processorArray = json_decode($absenceReason->processor);

            $checkManager = false;
            $checkAdmin = false;
            if (in_array("manager", $processorArray)) {
                $checkManager = true;
            }
            if (in_array("admin", $processorArray)) {
                $checkAdmin = true;
            }

            $userRole = Role::find($user->role_id);

            $userEmployee = Employee::where('user_id', $user->id)->first();
            $userDepartment = Department::find($userEmployee->department_id);

            $result = Employee::leftJoin(DB::raw(
                '(SELECT departments.id, departments.name as department_name, departments.parent_id as department_parent
                 FROM departments WHERE departments.id = '. $userEmployee->department_id .') AS DF'),
                'employees.department_id','=', 'DF.id')
                ->join(DB::raw(
                '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                     users.active_date, users.status,
                     roles.name as role_name, roles.level as role_level , roles.company_id as role_company_id
                    FROM users
                    LEFT JOIN roles
                    ON users.role_id = roles.id
                    WHERE roles.level < '. $userRole->level .'
                    AND roles.company_id = '. $user->company_id .') AS UR'), 'employees.user_id','=', 'UR.id');

            if ($checkAdmin) {
                $result = $result->where('UR.role_level', 1);
                if ($checkManager) {
                    $result = $result->orWhere([['UR.role_level', '>', '1'],['DF.id', $userEmployee->department_id]]);
                }
            } else {
                $result = $result->where('DF.id', $userEmployee->department_id);
                if ($checkManager) {
                    $result = $result->where('UR.role_level', '>', 1);
                } else {
                    $result = $result->where('UR.role_level', 1);
                }
            }

            $result = $result->select('UR.*', 'employees.department_id', 'DF.department_name', 'DF.department_parent')
                ->get();

            if ($result->isEmpty()) {
                $result = Employee::leftJoin(DB::raw(
                    '(SELECT departments.id, departments.name as department_name, departments.parent_id as department_parent
                    FROM departments WHERE departments.id = '. $userDepartment->parent_id .') AS DF'),
                    'employees.department_id','=', 'DF.id')
                    ->join(DB::raw(
                        '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                     users.active_date, users.status,
                     roles.name as role_name, roles.level as role_level , roles.company_id as role_company_id
                    FROM users
                    LEFT JOIN roles
                    ON users.role_id = roles.id
                    WHERE roles.company_id = '. $user->company_id .') AS UR'), 'employees.user_id','=', 'UR.id');

                if ($checkAdmin) {
                    $result = $result->where('UR.role_level', 1);
                    if ($checkManager) {
                        $result = $result->orWhere([['UR.role_level', '>', '1'],['DF.id', $userDepartment->parent_id]]);
                    }
                } else {
                    $result = $result->where('DF.id', $userDepartment->parent_id);
                    if ($checkManager) {
                        $result = $result->where('UR.role_level', '>', 1);
                    } else {
                        $result = $result->where('UR.role_level', 1);
                    }
                }

                $result = $result->select('UR.*', 'employees.department_id', 'DF.department_name', 'DF.department_parent')
                    ->get();
            }

            if ($result->isEmpty()) {
                $result = Employee::leftJoin(DB::raw(
                        '(SELECT users.id, users.first_name, users.last_name, users.company_id, users.role_id, users.added_by,
                     users.active_date, users.status,
                     roles.name as role_name, roles.level as role_level , roles.company_id as role_company_id
                    FROM users
                    LEFT JOIN roles
                    ON users.role_id = roles.id
                    WHERE roles.level = 1
                    AND roles.company_id = '. $user->company_id .') AS UR'), 'employees.user_id','=', 'UR.id')
                    ->select('UR.*', 'employees.department_id')
                    ->get();
            }

            if($result){
                foreach ($result as $employee){
                    $employee->name = $employee->first_name . ' ' . $employee->last_name;
                }
                return $this->responseSuccess($result);
            }else{
                return $this->responseSuccess([]);
            }
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/employees",
     *     tags={"Employees"},
     *     summary="Create new employee",
     *     description="Create new employee",
     *     security={{"bearerAuth":{}}},
     *     operationId="createEmployee",
     *     @OA\RequestBody(
     *         description="Employee schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Employee $employee)
    {
        try {
            if (!$user = $this->getAuthorizedUser('employee', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Employee::$rules;
                $input = $request->all();

                // add new user
                $userRules = User::$rules;
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user['company_id'];

                //Set default password and send mail
//                $password = Str::random(10);
//                $password = '123123';
//                $email = $input['email'];
//                $data = ([
//                    'name' => $input['first_name'],
//                    'email' => $input['email'],
//                    'password' => $password,
//                    'url' => config('app.site_url')
//                ]);
//
//                $input['password'] = $password;
//                $permissionIndexs = array();
                $jobTitle = JobTitle::find($input['job_title_id']);
                if (empty($jobTitle)) {
                    return $this->responseException('Not found job title', 404);
                }
                $input['role_id'] = $jobTitle->role_id;

//                $role = Role::where("id",$userTemp['role_id'])->first();
//                if (empty($role)) {
//                    return $this->responseException('Not found role', 404);
//                }
//                $permissionsOfRole = $role->permissionsOfRole;
//                if (!empty($permissionsOfRole)) {
//                    foreach ($permissionsOfRole as $permission) {
//                        $permissionId = $permission->pivot->permission_id;
//                        array_push($permissionIndexs, $permissionId);
//                    }
//                }
                $userValidator = Validator::make($input, $userRules);

                if ($userValidator->fails()) {
                    $errors = ValidateResponse::make($userValidator);
                    return $this->responseError($errors,400);
                }
                $newUser = User::create($input);

                //set permission for user
//                $newUser->permissions()->sync($permissionIndexs);
                $userPermission['user_id'] = $newUser->id;
                $userPermission['job_title_id'] = $jobTitle->id;
                $userPermission['permission'] = $jobTitle->permission;
                $userPermission['is_super'] = $jobTitle->is_super;

                $userPermissionRule = UserPermission::$rules;
                $userPermissionValidator = Validator::make($userPermission, $userPermissionRule);

                if ($userPermissionValidator->fails()) {
                    $errors = ValidateResponse::make($userPermissionValidator);
                    return $this->responseError($errors,400);
                }
                UserPermission::create($userPermission);

                // add new employee
                $input['user_id'] = $newUser->id;

//                $absenceSetting = AbsenceReason::where('company_id', $user['company_id'])->where('type', 1)->first();
//                if (empty($absenceSetting)) {
//                    return $this->responseException('Not found absence setting', 404);
//                }

//                $input['max_interval'] = $absenceSetting->interval;

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $newEmployee = Employee::create($input);

                //Handle to create relation
//                $employeeRelation = $input['employeeRelations'];
//                if (!empty($employeeRelation)) {
//                    foreach ($employeeRelation as $item) {
//                        $employeeRelationRules = EmployeeRelation::$rules;
//                        $item['user_id'] = $newEmployee->user_id;
//                        $employeeRelationValidator = Validator::make($item, $employeeRelationRules);
//
//                        if ($employeeRelationValidator->fails()) {
//                            $errors = ValidateResponse::make($employeeRelationValidator);
//                            return $this->responseError($errors, 400);
//                        }
//                        $newEmployeeRelation = EmployeeRelation::create($item);
//                    }
//
//                    $this->updateDaysOffSickChild($newUser->id);
//                }

                //Send verify email
                $this->sendActiveAccountEmail($newUser);

                return $this->responseSuccess($newEmployee, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function importCsvFile()
    {
        if (!$user = $this->getAuthorizedUser('employee', 'basic', 'store', 1)) {
            return $this->responseException('This action is unauthorized.', 404);
        } else {
            Excel::import(new UsersImport, request()->file('file'));

            return $this->responseSuccess($user, 201);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Get employee by id",
     *     description="Get employee by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getEmployeeByIdAPI",
     *     @OA\Parameter(
     *         description="employee id",
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
            if (!$userLogged = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $employeeData = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                    ->leftJoin('departments','departments.id','=','employees.department_id')
                    ->leftJoin('job_titles','job_titles.id','=','employees.job_title_id')
                    ->where('employees.user_id', $id)
                    ->select('employees.*','departments.id as department_id' ,'departments.name as department_name',
                        'users.active_date', 'users.role_id', 'users.first_name', 'users.last_name',
                        'users.address', 'users.email', 'users.phone_number', 'users.personal_number',
                        'users.zip_code', 'users.city', 'users.avatar',
                        'job_titles.id as job_title_id', 'job_titles.name as job_title_name')
                    ->first();
                if (empty($employeeData)) {
                    return $this->responseException('Not found employee', 404);
                }
                $objectInfo = [
                    'name' => 'objectInfo',
                    'objectType' => 'employee',
                    'objectItem' => $employeeData,
                ];
                $user = $this->getAuthorizedUser('employee', 'detail', 'show', $objectInfo);
                if ($userLogged['id'] != $id && !$user) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $list_employees = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')->get();
                if ($employeeData->nearest_manager) {
                    $key = array_search($employeeData->nearest_manager, array_column($list_employees->toArray(), 'user_id'));
                    $employeeData->nearestManagerName = $list_employees[$key]->first_name . ' ' . $list_employees[$key]->last_name;
                }
//                $logoOriginalName = Document::where("employee_id", $id)
//                    ->where('category_id', '=', 9)->where('added_from', '=', 4)
//                    ->first();

                $avatarOriginalName = DocumentNew::where('company_id', $user['company_id'])
                    ->where('type', 'attachment')
                    ->where('object_type', 'employee')
                    ->where('object_id', $id)
                    ->where('category_id', '=', 9)
                    ->first();
                if ($avatarOriginalName) {
                    $avatarOriginalName = DocumentAttachment::where('document_id', $avatarOriginalName['id'])->first();
                    $employeeData->original_file_name = $avatarOriginalName->original_file_name;
                }
                $employeeData->editPermission = $user->editPermission;
                return $this->responseSuccess($employeeData);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Update employee API",
     *     description="Update employee API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateEmployeeAPI",
     *     @OA\Parameter(
     *         description="employee id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Employee schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
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
            if (!$user = $this->getAuthorizedUser('employee', 'basic', 'update', 1)) {
                return $this->responseException('Not found user', 404);
            } else {
                $input = $request -> all();

                if (!empty($input['isEditUser'])) {
                    $userRules = User::$updateRules;
                    $userData = User::find($input['user_id']);
                    if (empty($userData)) {
                        return $this->responseException('Not found employee', 404);
                    }

                    $userValidator = Validator::make($input, $userRules);
                    if ($userValidator->fails()) {
                        $errors = ValidateResponse::make($userValidator);
                        return $this->responseError($errors,400);
                    }

                    $userData->update([
                        'first_name' => $input['first_name'],
                        'last_name' => $input['last_name'],
                        'email' => $input['email'],
                        'address' => $input['address'],
                        'zip_code' => $input['zip_code'],
                        'city' => $input['city'],
                        'phone_number' => $input['phone_number'],
                        'personal_number' => $input['personal_number']
                    ]);

                    return $this->responseSuccess($userData);
                } else {
                    $rules = Employee::$updateRules;

                    $employeeData = Employee::find($id);
                    if (empty($employeeData)) {
                        return $this->responseException('Not found employee', 404);
                    }

                    if (!empty($input['job_title_id']) && $employeeData->job_title_id !== $input['job_title_id']) {
                        $jobTitleInfo = JobTitle::find($input['job_title_id']);
                        if (empty($jobTitleInfo)) {
                            return $this->responseException('Not found job title', 404);
                        }

                        $user = User::find($employeeData->user_id);
                        if (empty($user)) {
                            return $this->responseException('Not found user', 404);
                        }

                        $user->update(['role_id' => $jobTitleInfo->role_id]);

                        $userPermission = UserPermission::where('user_id', $employeeData->user_id)->first();

                        $inputUserPermission['job_title_id'] = $input['job_title_id'];
                        $inputUserPermission['permission'] = $jobTitleInfo->permission;
                        if (empty($userPermission)) {
                            $inputUserPermission['user_id'] = $employeeData->user_id;
                            UserPermission::create($inputUserPermission);

                            $this->sendActiveAccountEmail($user);
                        } else {
                            $userPermission->update($inputUserPermission);
                        }
                    }

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors,400);
                    }

                    $employeeData->update($input);

                    return $this->responseSuccess($employeeData);
                }

//            $roleInfo = Role::find($input['role_id']);
//            if (empty($roleInfo)) {
//                return $this->responseException('Not found role', 404);
//            }
//            if ($user->role_id != $input['role_id']) {
//                $permissionIndexs = array();
//                $role = Role::where("id",$input['role_id'])->first();
//                if (empty($role)) {
//                    return $this->responseException('Not found role', 404);
//                }
//                $user->update(['role_id' => $input['role_id']]);
//                $permissionsOfRole = $role->permissionsOfRole;
//                if (!empty($permissionsOfRole)) {
//                    foreach ($permissionsOfRole as $permission) {
//                        $permissionId = $permission->pivot->permission_id;
//                        array_push($permissionIndexs, $permissionId);
//                    }
//                }
//                //set permission for user
//                $user->permissions()->sync($permissionIndexs);
//            }
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/employees/{id}",
     *     tags={"Employees"},
     *     summary="Delete employee API",
     *     description="Delete employee API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteEmployeeAPI",
     *     @OA\Parameter(
     *         description="employee id",
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
            $employeeData = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                ->where('employees.user_id', $id)
                ->select('employees.*', 'users.first_name as user_first_name', 'users.last_name as user_last_name')
                ->first();
            if (empty($employeeData)) {
                return $this->responseException('Not found employee', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'employee',
                'objectItem' => $employeeData,
            ];
            if (!$user = $this->getAuthorizedUser('employee', 'basic', 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $userName = $employeeData->user_first_name . ' ' . $employeeData->user_last_name;
                if ($this->moveToRepository($user['id'], $user['company_id'], 0, 'Employee', $employeeData->user_id, $userName)) {
                    $nearestManagerId = $employeeData->nearest_manager;
                    if ($nearestManagerId) {
                        $members = Employee::where('nearest_manager', $employeeData->user_id)->get();
                        if (!empty($members)) {
                            foreach ($members as $member) {
                                $member->update(['nearest_manager' => $nearestManagerId]);
                            }
                        }
                    }
                    $employeeData->update(['disable_status' => 1]);

//                    Employee::destroy($id);
                    return $this->responseSuccess("Delete employee success", 200);
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
