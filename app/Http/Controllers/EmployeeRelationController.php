<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\AbsenceReason;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\EmployeeRelation;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="EmployeeRelations",
 *     description="EmployeeRelations APIs",
 * )
 **/
class EmployeeRelationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/employeeRelations",
     *     tags={"EmployeeRelations"},
     *     summary="Get employeeRelations",
     *     description="Get employeeRelations list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getEmployeeRelations",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $user_id = $request -> user_id;
                if($user_id){
                    $result = EmployeeRelation::where ('user_id', $user_id)
                        ->get();
                }else{
                    $result = EmployeeRelation::all();
                }
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

    /**
     * @OA\Post(
     *     path="/api/v1/employeeRelations",
     *     tags={"EmployeeRelations"},
     *     summary="Create new employeeRelation",
     *     description="Create new employeeRelation",
     *     security={{"bearerAuth":{}}},
     *     operationId="createEmployeeRelation",
     *     @OA\RequestBody(
     *         description="EmployeeRelation schemas",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeRelation")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, EmployeeRelation $employeeRelation)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $rules = EmployeeRelation::$rules;
                $input = $request -> all();
                if ($input['is_primary']){
                    EmployeeRelation::where('is_primary', '=', 1)->where('user_id', '=', $input['user_id'])->update(array('is_primary' => 0));
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newEmployeeRelation = EmployeeRelation::create($input);

                if ($input['relation'] == 'Children') {
                    $this->updateDaysOffSickChild($newEmployeeRelation->user_id);
                }

                return $this->responseSuccess($newEmployeeRelation,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employeeRelations/{id}",
     *     tags={"EmployeeRelations"},
     *     summary="Get employeeRelation by id",
     *     description="Get employeeRelation by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getEmployeeRelationByIdAPI",
     *     @OA\Parameter(
     *         description="employeeRelation id",
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
            $employeeRelationData = EmployeeRelation::where("id",$id)->first();
            if (empty($employeeRelationData)) {
                return $this->responseException('Not found employeeRelation', 404);
            }

            return $this->responseSuccess($employeeRelationData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/employeeRelations/{id}",
     *     tags={"EmployeeRelations"},
     *     summary="Update employeeRelation API",
     *     description="Update employeeRelation API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateEmployeeRelationAPI",
     *     @OA\Parameter(
     *         description="employeeRelation id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="EmployeeRelation schemas",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeRelation")
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
            $rules = EmployeeRelation::$updateRules;
            $input = $request -> all();

            $employeeRelationData = EmployeeRelation::where("id",$id)->first();
            if (empty($employeeRelationData)) {
                return $this->responseException('Not found employeeRelation', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            if ($input['is_primary']){
                EmployeeRelation::where('is_primary', '=', 1)->where('user_id', '=', $employeeRelationData->user_id)->update(array('is_primary' => 0));
            }

            $employeeRelationData->update($input);

//            if ($input['relation'] == 'Children') {
//                $test = $this->updateDaysOffSickChild($employeeRelationData->user_id);
//            }

            return $this->responseSuccess($employeeRelationData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/employeeRelations/{id}",
     *     tags={"EmployeeRelations"},
     *     summary="Delete employeeRelation API",
     *     description="Delete employeeRelation API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteEmployeeRelationAPI",
     *     @OA\Parameter(
     *         description="employeeRelation id",
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
            $employeeRelationData = EmployeeRelation::where("id",$id)->first();
            if (empty($employeeRelationData)) {
                return $this->responseException('Not found employeeRelation', 404);
            }
            EmployeeRelation::destroy($id);
            return $this->responseSuccess("Delete employeeRelation success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
