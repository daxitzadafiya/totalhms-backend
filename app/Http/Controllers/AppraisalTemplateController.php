<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\AppraisalTemplate;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="AppraisalTemplates",
 *     description="AppraisalTemplate APIs",
 * )
 **/
class AppraisalTemplateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/appraisalTemplates",
     *     tags={"AppraisalTemplates"},
     *     summary="Get appraisalTemplates",
     *     description="Get appraisalTemplates list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAppraisalTemplates",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $result = AppraisalTemplate::where ('company_id', $user -> company_id)
                    ->get();
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
     *     path="/api/v1/appraisalTemplates",
     *     tags={"AppraisalTemplates"},
     *     summary="Create new appraisalTemplate",
     *     description="Create new appraisalTemplate",
     *     security={{"bearerAuth":{}}},
     *     operationId="createAppraisalTemplate",
     *     @OA\RequestBody(
     *         description="AppraisalTemplate schemas",
     *         @OA\JsonContent(ref="#/components/schemas/AppraisalTemplate")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, AppraisalTemplate $appraisalTemplate)
    {
        try {
            $rules = AppraisalTemplate::$rules;
            $input = $request -> all();

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            $newAppraisalTemplate = AppraisalTemplate::create($input);
            return $this->responseSuccess($newAppraisalTemplate,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/appraisalTemplates/{id}",
     *     tags={"AppraisalTemplates"},
     *     summary="Get appraisalTemplate by id",
     *     description="Get appraisalTemplate by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAppraisalTemplateByIdAPI",
     *     @OA\Parameter(
     *         description="appraisalTemplate id",
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
            $appraisalTemplateData = AppraisalTemplate::where("id",$id)->first();
            if (empty($appraisalTemplateData)) {
                return $this->responseException('Not found appraisalTemplate', 404);
            }

            return $this->responseSuccess($appraisalTemplateData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/appraisalTemplates/{id}",
     *     tags={"AppraisalTemplates"},
     *     summary="Update appraisalTemplate API",
     *     description="Update appraisalTemplate API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateAppraisalTemplateAPI",
     *     @OA\Parameter(
     *         description="appraisalTemplate id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="AppraisalTemplate schemas",
     *         @OA\JsonContent(ref="#/components/schemas/AppraisalTemplate")
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
            $rules = AppraisalTemplate::$updateRules;
            $input = $request -> all();

            $appraisalTemplateData = AppraisalTemplate::where("id",$id)->first();
            if (empty($appraisalTemplateData)) {
                return $this->responseException('Not found appraisalTemplate', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $appraisalTemplateData->update($input);

            return $this->responseSuccess($appraisalTemplateData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/appraisalTemplates/{id}",
     *     tags={"AppraisalTemplates"},
     *     summary="Delete appraisalTemplate API",
     *     description="Delete appraisalTemplate API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteAppraisalTemplateAPI",
     *     @OA\Parameter(
     *         description="appraisalTemplate id",
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
            $appraisalTemplateData = AppraisalTemplate::where("id",$id)->first();
            if (empty($appraisalTemplateData)) {
                return $this->responseException('Not found appraisalTemplate', 404);
            }
            AppraisalTemplate::destroy($id);
            return $this->responseSuccess("Delete appraisalTemplate success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
