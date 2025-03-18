<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\PermissionFormat;
use App\Models\RequestPushNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Role;


/**
 * @OA\Tag(
 *     name="PermissionsFormat",
 *     description="PermissionsFormat APIs",
 * )
 **/
class PermissionFormatController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/permissionsFormat",
     *     tags={"PermissionsFormat"},
     *     summary="PermissionsFormat list",
     *     description="Get PermissionsFormat API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getPermissionsFormatApi",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $filterBy = strtolower($request->filterBy);
                $result = PermissionFormat::where('filter_by', $filterBy)->get();
                if($result){
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

}
