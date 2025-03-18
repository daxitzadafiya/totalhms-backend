<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Department;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Departments",
 *     description="Departments APIs",
 * )
 **/
class DepartmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/departments",
     *     tags={"Departments"},
     *     summary="Get departments",
     *     description="Get departments list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDepartments",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('department', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $parent = $request->parent;
                $getChildren = $request->getChildren;
                $filterBy = $request->filterBy;
                if ($parent) {
                    $result = Department::where('company_id', $user->company_id)
                        ->leftJoin('employees', 'departments.parent_id', '=', 'employees.department_id')
                        ->distinct()
                        ->get(['departments.name', 'employees.user_id as parent_id']);
                } else {
                    $result = Department::where ('departments.company_id', $user->company_id)
                        ->leftJoin(DB::raw('(SELECT * FROM departments) AS PD'), 'departments.parent_id', '=', 'PD.id')
                        ->get(['departments.*', 'PD.name as parent_name']);
                }
                if($result){
                    foreach ($result as $department) {
                        $employees = Employee::where('department_id', $department->id)
                            ->with(['userPermission', 'jobTitle', 'user'])
                            ->get();
                        $countEmployee = 0;
                        $countSuperManager = 0;
                        $countManager = 0;
                        $countSuperUser = 0;
                        $countUser = 0;
                        $superManager = '';
                        $normalManager = '';
                        $superUser = '';
                        $normalUser = '';
                        if (!empty($employees)) {
                            $countEmployee = count($employees);
                            foreach ($employees as $employee) {
                                $employeeJobTitle = $employee->jobTitle;
                                if ($employeeJobTitle) {
                                    $employeePermission = $employee->userPermission;
                                    if ($employeeJobTitle->role_name == 'Manager') {
                                        if ($employeePermission->is_super) {
                                            $countSuperManager += 1;
                                            $superManager = $superManager . $employee->user->first_name . ' ' . $employee->user->last_name . ', ';
                                        } else {
                                            $countManager += 1;
                                            $normalManager = $normalManager . $employee->user->first_name . ' ' . $employee->user->last_name . ', ';
                                        }
                                    } elseif ($employeeJobTitle->role_name == 'User') {
                                        if ($employeePermission->is_super) {
                                            $countSuperUser += 1;
                                            $superUser = $superUser . $employee->user->first_name . ' ' . $employee->user->last_name . ', ';
                                        } else {
                                            $countUser += 1;
                                            $normalUser = $normalUser . $employee->user->first_name . ' ' . $employee->user->last_name . ', ';
                                        }
                                    }
                                }
                            }
                        }
                        $department->countEmployee = $countEmployee;
                        $department->countSuperManager = $countSuperManager;
                        $department->countManager = $countManager;
                        $department->countSuperUser = $countSuperUser;
                        $department->countUser = $countUser;
                        if (strlen($superManager) > 0) {
                            $superManager = substr($superManager, 0, strlen($superManager)-2);
                        }
                        $department->superManager = $superManager;
                        if (strlen($normalManager) > 0) {
                            $normalManager = substr($normalManager, 0, strlen($normalManager)-2);
                        }
                        $department->normalManager = $normalManager;
                        if (strlen($superUser) > 0) {
                            $superUser = substr($superUser, 0, strlen($superUser)-2);
                        }
                        $department->superUser = $superUser;
                        if (strlen($normalUser) > 0) {
                            $normalUser = substr($normalUser, 0, strlen($normalUser)-2);
                        }
                        $department->normalUser = $normalUser;
                    }
                    if ($getChildren){
                        $result = $this->getSubDepartment($result, $getChildren);
                    }
                    if ($filterBy == 'security' && $user->role_id !== 2) {
                        $result = $this->getByRole($result, $user);
                    }
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getByRole($departments, $user)
    {
        $result = array();
        $isSuper = $user->permissions->is_super;
        if ($isSuper) {
            $result = $this->getSubDepartment($departments, $user->employee->department_id);
            $current = $departments->find($user->employee->department_id);
            array_push($result, $current);
        } else {
            $item = $departments->where('id', $user->employee->department_id)->first();
            array_push($result, $item);
        }
        return $result;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/departments",
     *     tags={"Departments"},
     *     summary="Create new department",
     *     description="Create new department",
     *     security={{"bearerAuth":{}}},
     *     operationId="createDepartment",
     *     @OA\RequestBody(
     *         description="Department schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Department")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Department $department)
    {
        try {
            if (!$user = $this->getAuthorizedUser('department', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Department::$rules;
                $input = $request -> all();
                $input['company_id'] = $user['company_id'];

                $input['manager_job_title'] = json_encode($input['manager_job_title']);
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newDepartment = Department::create($input);
                return $this->responseSuccess($newDepartment,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/departments/{id}",
     *     tags={"Departments"},
     *     summary="Get department by id",
     *     description="Get department by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDepartmentByIdAPI",
     *     @OA\Parameter(
     *         description="department id",
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
            if (!$user = $this->getAuthorizedUser('department', 'detail', 'show', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $departmentData = Department::where("id", $id)->first();
                if (empty($departmentData)) {
                    return $this->responseException('Not found department', 404);
                }
                $departmentData->editPermission = $user->editPermission;
                return $this->responseSuccess($departmentData, 201);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/departments/{id}",
     *     tags={"Departments"},
     *     summary="Update department API",
     *     description="Update department API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateDepartmentAPI",
     *     @OA\Parameter(
     *         description="department id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Department schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Department")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        if (!$user = $this->getAuthorizedUser('department', 'basic', 'update', 1)) {
            return $this->responseException('This action is unauthorized.', 404);
        } else {
            try {
                $rules = Department::$updateRules;
                $input = $request->all();
                $departmentData = Department::where("id", $id)->first();
                if (empty($departmentData)) {
                    return $this->responseException('Not found department', 404);
                }
//                $managerInfo = array(
//                    'employee' => $input['manager_by_employee_arr'],
//                    'job_title' => $input['manager_by_jobtitle_arr'],
//                );
//                $memberInfo = array(
//                    'employee' => $input['member_by_employee_arr'],
//                    'job_title' => $input['member_by_jobtitle_arr'],
//                );
//                $input['manager_array'] = json_encode($managerInfo);
//                $input['member_array'] = json_encode($memberInfo);
                $input['manager_job_title'] = json_encode($input['manager_job_title']);

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $departmentData->update($input);

                return $this->responseSuccess($departmentData, 201);

            } catch (Exception $e) {
                return $this->responseException($e->getMessage(), 400);
            }
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/departments/{id}",
     *     tags={"Departments"},
     *     summary="Delete department API",
     *     description="Delete department API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteDepartmentAPI",
     *     @OA\Parameter(
     *         description="department id",
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
            if (!$user = $this->getAuthorizedUser('department', 'basic', 'destroy', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $departmentData = Department::find($id);
                if (empty($departmentData)) {
                    return $this->responseException('Not found department', 404);
                }

                $countEmployee = Employee::where('department_id', $id)->count();

                if ($countEmployee == 0 && $this->moveToRepository($user['id'], $user['company_id'], 0, 'Department', $departmentData->id, $departmentData->name)) {
                    $departmentData->update(['disable_status' => 1]);

//                    Department::destroy($id);
                    return $this->responseSuccess("Delete department success", 200);
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function list(Request $request)
    {
        try {
            if (!$user = $this->getAuthorizedUser('department', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $result = Department::where('departments.company_id', $request->company_id)
                ->leftJoin(DB::raw('(SELECT * FROM departments) AS PD'), 'departments.parent_id', '=', 'PD.id')
                ->get(['departments.*', 'PD.name as parent_name']);

            if ($result) {
                return $this->responseSuccess($result);
            }
            return $this->responseSuccess([]);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
