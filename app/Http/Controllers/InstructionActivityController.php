<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\InstructionActivity;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="InstructionActivity",
 *     description="InstructionActivity APIs",
 * )
 **/
class InstructionActivityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/instructionActivities",
     *     tags={"InstructionActivities"},
     *     summary="Get instructionActivities",
     *     description="Get instructionActivities list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getInstructionActivities",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            $instruction = $request -> instruction;
            if($instruction){
                $result = InstructionActivity::where ('instruction_id', $instruction)
                    ->get();
            }else{
                $result = InstructionActivity::all();
            }
            if($result){
                return $this->responseSuccess($result);
            }else{
                return $this->responseSuccess([]);
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/instructionActivities",
     *     tags={"InstructionActivities"},
     *     summary="Create new instructionActivity",
     *     description="Create new instructionActivity",
     *     security={{"bearerAuth":{}}},
     *     operationId="createInstructionActivity",
     *     @OA\RequestBody(
     *         description="InstructionActivity schemas",
     *         @OA\JsonContent(ref="#/components/schemas/InstructionActivity")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, InstructionActivity $instructionActivity)
    {
        try {
            $rules = InstructionActivity::$rules;
            $input = $request -> all();

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            $newInstructionActivity = InstructionActivity::create($input);
            return $this->responseSuccess($newInstructionActivity,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/instructionActivities/{id}",
     *     tags={"InstructionActivities"},
     *     summary="Get instructionActivity by id",
     *     description="Get instructionActivity by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getInstructionActivityByIdAPI",
     *     @OA\Parameter(
     *         description="instructionActivity id",
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
            $instructionActivityData = InstructionActivity::where("id",$id)->first();
            if (empty($instructionActivityData)) {
                return $this->responseException('Not found instructionActivity', 404);
            }

            return $this->responseSuccess($instructionActivityData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/instructionActivities/{id}",
     *     tags={"InstructionActivities"},
     *     summary="Update instructionActivity API",
     *     description="Update instructionActivity API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateInstructionActivityAPI",
     *     @OA\Parameter(
     *         description="instructionActivity id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="InstructionActivity schemas",
     *         @OA\JsonContent(ref="#/components/schemas/InstructionActivity")
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
            $rules = InstructionActivity::$updateRules;
            $input = $request -> all();

            $instructionActivityData = InstructionActivity::where("id",$id)->first();
            if (empty($instructionActivityData)) {
                return $this->responseException('Not found instructionActivity', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $instructionActivityData->update($input);

            return $this->responseSuccess($instructionActivityData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/instructionActivities/{id}",
     *     tags={"InstructionActivities"},
     *     summary="Delete instructionActivity API",
     *     description="Delete instructionActivity API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteInstructionActivityAPI",
     *     @OA\Parameter(
     *         description="instructionActivity id",
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
            $instructionActivityData = InstructionActivity::where("id",$id)->first();
            if (empty($instructionActivityData)) {
                return $this->responseException('Not found instructionActivity', 404);
            }
            InstructionActivity::destroy($id);
            return $this->responseSuccess("Delete instructionActivity success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
