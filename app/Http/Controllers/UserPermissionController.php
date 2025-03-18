<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Validator, Mail, Config;
use Illuminate\Http\Request;
use JWTAuth;


/**
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permissions APIs",
 * )
 **/
class UserPermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/userPermissions/{user_id}",
     *     tags={"userPermissions"},
     *     summary="Get userPermissions by user_id",
     *     description="Get userPermissions by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUserPermissionsByIdAPI",
     *     @OA\Parameter(
     *         description="userPermissions user_id",
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
    public function show(Request $request, $user_id)
    {
        try {
            $userPermissionsData = UserPermission::leftJoin('users', 'user_permissions.user_id','=', 'users.id')
                ->leftJoin('job_titles', 'user_permissions.job_title_id','=', 'job_titles.id')
                ->where('user_permissions.user_id', $user_id)
                ->with(['job_title'])
                ->select('user_permissions.*', 'users.first_name as first_name', 'users.last_name as last_name',
                    'job_titles.name as job_title_name')
                ->first();
            if (empty($userPermissionsData)) {
                return $this->responseException('Not found userPermissions', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'user permission',
                'objectItem' => $userPermissionsData,
            ];

            if (!$user = $this->getAuthorizedUser('user permission', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $userPermissionsData->editPermission = $user->editPermission;
                return $this->responseSuccess($userPermissionsData, 201);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
    /**
     * @OA\Put(
     *     path="/api/v1/userPermissions/{id}",
     *     tags={"userPermissions"},
     *     summary="Update userPermissions API",
     *     description="Update userPermissions API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateUserPermissionsAPI",
     *     @OA\Parameter(
     *         description="userPermissions id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="UserPermissions schemas",
     *         @OA\JsonContent(ref="#/components/schemas/UserPermissions")
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
            if (!$user = $this->getAuthorizedUser('user permission', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = UserPermission::$updateRules;
                $input = $request->all();

                $userPermissionsData = UserPermission::find($id);
                if (empty($userPermissionsData)) {
                    return $this->responseException('Not found UserPermissions', 404);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $userPermissionsData->update($input);

                return $this->responseSuccess($userPermissionsData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
