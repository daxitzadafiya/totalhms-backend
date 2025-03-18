<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\RequestPushNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Role;


/**
 * @OA\Tag(
 *     name="Roles",
 *     description="Roles APIs",
 * )
 **/
class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Roles list",
     *     description="Get roles API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRolesApi",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $isHelpSetting = $request->isHelpSetting;
                if ($user['role_id'] === 1) { //super admin
                    if ($isHelpSetting) { //Help center: get all roles
                        $result = Role::where('id', '<>', 2)->get();
                    } else {
                        $result = Role::where('level', '>', 0)->get();
                    }
                } else {
                    $result = Role::where('level', '>', 1)->get();
                }
                if($result) {
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function all(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            $isHelpSetting = $request->isHelpSetting;
            if ($user['role_id'] === 1) { //super admin
                if ($isHelpSetting) { //Help center: get all roles
                    $result = Role::where('id', '<>', 2)->get();
                } else {
                    $result = Role::where('level', '>', 0)->get();
                }
            } else {
                $result = Role::where('level', '>', 1)->get();
            }
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
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Create new role",
     *     description="Create new role",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRole",
     *     @OA\RequestBody(
     *         description="role schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */

    public function store(Request $request, Role $role)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Role::$rules;
                $input = $request -> all();
                $company_id = null;
                if ($user['role_id'] != 1) {
                    $input['company_id'] = $user['company_id'];
                    $company_id = $user['company_id'];
                }
                if ($input['addNewLevel']) {
                    $maxLevel = $this->getMaxLevel($company_id);

                    $input['level'] = $maxLevel + 1;
                }
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newRole = Role::create($input);

                $permissionsKeyArray = $input['permissionsKey'];
//                $checkKeyAdminPermissionArray = array();
//                foreach ($input['permissionsKey'] as $key) {
//                    $name = strstr($key, '-');
//                    if (!in_array( $name, $checkKeyAdminPermissionArray)) {
//                        $adminKey = 'admin' . $name;
//                        $checkView = 'index' . $name;
//                        $checkShowDetail = 'show' . $name;
//                        $checkCreate = 'store' . $name;
//                        $checkEdit = 'update' . $name;
//                        $checkDelete = 'destroy' . $name;
//
//                        $checkFullPermissionsArray = array($checkView, $checkShowDetail, $checkCreate, $checkEdit, $checkDelete);
//
//                        if (count(array_intersect($checkFullPermissionsArray,  $input['permissionsKey'])) == count($checkFullPermissionsArray)) {
//                            array_push($permissionsKeyArray, $adminKey);
//                        }
//                        array_push($checkKeyAdminPermissionArray, $name);
//                    }
//                }

                $permissionIndexs = Permission::whereIn('key', $permissionsKeyArray)
                    ->select('id')
                    ->get();

                $newRole->permissionsOfRole()->sync($permissionIndexs);

                if (!$newRole->company_id) {
                    $companies = Company::get();
                    $input['level'] = 0;
                    $input['related_id'] = $newRole->id;
                    foreach ($companies as $company) {
                        $input['company_id'] = $company->id;

                        $newCompanyRole = Role::create($input);

                        $newCompanyRole->permissionsOfRole()->sync($permissionIndexs);
                    }
                }

                return $this->responseSuccess($newRole,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getMaxLevel($company_id = null)
    {
        if ($company_id) {
            $role = Role::where('company_id', $company_id)->orderBy('level', 'desc')->first();
        } else {
            $role = Role::whereNull('company_id')->orderBy('level', 'desc')->first();
        }

        if ($role) {
            return $role->level;
        }

        return 0;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Get role by id",
     *     description="Get role by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRoleByIdAPI",
     *     @OA\Parameter(
     *         description="role id",
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
            $roleData = Role::where("id",$id)->with(['permissionsOfRole'])->first();
            if (empty($roleData)) {
                return $this->responseException('Not found role', 404);
            }

            return $this->responseSuccess($roleData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Update role API",
     *     description="Update role API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRoleAPI",
     *     @OA\Parameter(
     *         description="role id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Role schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
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
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $rules = Role::$updateRules;
                $input = $request -> all();
                $roleData = Role::find($id);
                if (empty($roleData)) {
                    return $this->responseException('Not found role', 404);
                }

                $oldLevel = $roleData->level;
                if ($input['addNewLevel']) {
                    $maxLevel = $this->getMaxLevel($roleData->company_id);

                    $input['level'] = $maxLevel + 1;
                }
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

//                $permissionsKeyArray = $input['permissionsKey'];
//                $checkKeyAdminPermissionArray = array();
//                foreach ($input['permissionsKey'] as $key) {
//                    $name = strstr($key, '-');
//                    if (!in_array( $name, $checkKeyAdminPermissionArray)) {
//                        $adminKey = 'admin' . $name;
//                        $checkView = 'index' . $name;
//                        $checkShowDetail = 'show' . $name;
//                        $checkCreate = 'store' . $name;
//                        $checkEdit = 'update' . $name;
//                        $checkDelete = 'destroy' . $name;
//
//                        $checkFullPermissionsArray = array($checkView, $checkShowDetail, $checkCreate, $checkEdit, $checkDelete);
//
//                        if (count(array_intersect($checkFullPermissionsArray,  $input['permissionsKey'])) == count($checkFullPermissionsArray)) {
//                            array_push($permissionsKeyArray, $adminKey);
//                        }
//                        array_push($checkKeyAdminPermissionArray, $name);
//                    }
//                }

//                $permissionIndexs = Permission::whereIn('key', $permissionsKeyArray)
//                    ->select('id')
//                    ->get();
//
//                $roleData->permissionsOfRole()->sync($permissionIndexs);

//                $usersHasRole = User::where('role_id', '=', $id)->get();
//                if (!empty($usersHasRole)) {
//                    foreach ($usersHasRole as $user) {
//                        $user->permissions()->sync($permissionIndexs);
//                    }
//                }

                $roleData->update($input);
                $this->pushNotificationToAllCompanies('Role', $roleData['id'], $roleData['name'],'update');

//                if ($roleData->level != $oldLevel) {
//                    $this->setLevelOfRole($oldLevel, $roleData->company_id);
//                }

                return $this->responseSuccess($roleData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function setLevelOfRole($oldLevel, $company_id = null)
    {
        if (!$oldLevel) return null;

        if ($company_id) {
            $rolesHasOldLevel = Role::where('company_id', $company_id)->where('level', $oldLevel)->doesntExist();
        } else {
            $rolesHasOldLevel = Role::whereNull('company_id')->where('level', $oldLevel)->doesntExist();
        }

        if ($rolesHasOldLevel) {
            if ($company_id) {
                Role::where('company_id', $company_id)
                    ->where('level', '>', $oldLevel)
                    ->update(['level' =>  DB::raw('level - 1')]);
            } else {
                Role::whereNull('company_id')
                    ->where('level', '>', $oldLevel)
                    ->update(['level' =>  DB::raw('level - 1')]);
            }
        }

    }


    public function applyNewUpdate($request_push_notification_id)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $request = RequestPushNotification::find($request_push_notification_id);

                if (empty($request)) {
                    return $this->responseException('Not found request', 404);
                }

                $defaultRole = Role::where("id", $request->feature_id)->with(['permissionsOfRole'])->first();

                $permission_ids = [];
                if (!empty($defaultRole->permissionsOfRole)) {
                    foreach ($defaultRole->permissionsOfRole as $permission) {
                        $permission_ids[] = $permission->id;
                    }
                }

                if (!empty($permission_ids)) {
                    $role = Role::where('related_id', $request->feature_id)
                        ->where('company_id', $user['company_id'])
                        ->first();

//                    $role->permissions()->attach($permission_ids);
//                    $workers = User::where('role_id', $role->id)->get();
//                    foreach ($workers as $worker) {
//                        $worker->permissions()->attach($permission_ids);
//                    }

                    $role->permissionsOfRole()->sync($permission_ids);

                    $usersHasRole = User::where('role_id', '=',  $role->id)->get();
                    if (!empty($usersHasRole)) {
                        foreach ($usersHasRole as $u) {
                            $u->permissions()->sync($permission_ids);
                        }
                    }

                    $input['processed_by'] = $user['id'];
                    $input['process_status'] = 'applied';

                    $request->update($input);

                    $notificationsOfRequest = Notification::where('request_push_notification_id', $request->id)->get();
                    if (!empty($notificationsOfRequest)) {
                        foreach ($notificationsOfRequest as $notification) {
                            $inputNotification['show_action'] = 0;
                            if ($notification->user_id == $user['id']) {
                                $inputNotification['read_status'] = 1;
                            }
                            $notification->update($inputNotification);
                        }
                    }

                    return $this->responseSuccess($request, 201);
                }

                return $this->responseSuccess($request, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}