<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Permission;
use App\Models\User;
use Validator, Mail, Config;
use Illuminate\Http\Request;
use JWTAuth;


/**
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permissions APIs",
 * )
 **/
class PermissionController extends Controller
{
    /**
     * @OA\Put(
     *     path="/api/v1/permissions/{user_id}",
     *     tags={"Permissions"},
     *     summary="Update permission API",
     *     description="Update permission API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updatePermissionAPI",
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
     *         description="Permission schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Permission")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function update(Request $request, $user_id)
    {
        try {
            $input = $request -> all();

            $userData = User::where("id", $user_id)->first();
            if (empty($userData)) {
                return $this->responseException('Not found user', 404);
            }
            $userData->update(['role_id' => $input['role_id']]);

            $checkKeyAdminPermissionArray = array();
            $permissionsKeyArray = $input['permissionsKey'];
            foreach ($input['permissionsKey'] as $key) {
                $name = strstr($key, '-');
                if (!in_array( $name, $checkKeyAdminPermissionArray)) {
                    $adminKey = 'admin' . $name;
                    $checkView = 'index' . $name;
                    $checkShowDetail = 'show' . $name;
                    $checkCreate = 'store' . $name;
                    $checkEdit = 'update' . $name;
                    $checkDelete = 'destroy' . $name;

                    $checkFullPermissionsArray = array($checkView, $checkShowDetail, $checkCreate, $checkEdit, $checkDelete);

                    if (count(array_intersect($checkFullPermissionsArray,  $input['permissionsKey'])) == count($checkFullPermissionsArray)) {
                        array_push($permissionsKeyArray, $adminKey);
                    }
                    array_push($checkKeyAdminPermissionArray, $name);
                }
            }

            $permissionsIndex = Permission::whereIn('key', $permissionsKeyArray)
                ->select('id')
                ->get();

            $userData->permissions()->sync($permissionsIndex);

            return $this->responseSuccess($userData->permissions, 201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
