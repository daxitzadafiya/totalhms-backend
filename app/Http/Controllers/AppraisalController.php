<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use Validator;
use JWTAuth;
use App\Models\Appraisal;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Appraisals",
 *     description="Appraisal APIs",
 * )
 **/
class AppraisalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/appraisals",
     *     tags={"Appraisals"},
     *     summary="Get appraisals",
     *     description="Get appraisals list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAppraisals",
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
                $result = Appraisal::where ('company_id', $user -> company_id)
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
     *     path="/api/v1/appraisals",
     *     tags={"Appraisals"},
     *     summary="Create new appraisal",
     *     description="Create new appraisal",
     *     security={{"bearerAuth":{}}},
     *     operationId="createAppraisal",
     *     @OA\RequestBody(
     *         description="Appraisal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Appraisal")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Appraisal $appraisal)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Appraisal::$rules;
                $companyData = Company::where("id",$user['company_id'])->first();
                $input = $request -> all();
                if (empty($input['industry_id'])) {
                    $input['industry_id'] = $companyData['industry_id'];
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newAppraisal = Appraisal::create($input);
                return $this->responseSuccess($newAppraisal,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/appraisals/{id}",
     *     tags={"Appraisals"},
     *     summary="Get appraisal by id",
     *     description="Get appraisal by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAppraisalByIdAPI",
     *     @OA\Parameter(
     *         description="appraisal id",
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
            $appraisalData = Appraisal::where("id",$id)->first();
            if (empty($appraisalData)) {
                return $this->responseException('Not found appraisal', 404);
            }

            return $this->responseSuccess($appraisalData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/appraisals/{id}",
     *     tags={"Appraisals"},
     *     summary="Update appraisal API",
     *     description="Update appraisal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateAppraisalAPI",
     *     @OA\Parameter(
     *         description="appraisal id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Appraisal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Appraisal")
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
            $rules = Appraisal::$updateRules;
            $input = $request -> all();

            $appraisalData = Appraisal::where("id",$id)->first();
            if (empty($appraisalData)) {
                return $this->responseException('Not found appraisal', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $appraisalData->update($input);

            return $this->responseSuccess($appraisalData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/appraisals/{id}",
     *     tags={"Appraisals"},
     *     summary="Delete appraisal API",
     *     description="Delete appraisal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteAppraisalAPI",
     *     @OA\Parameter(
     *         description="appraisal id",
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
            $appraisalData = Appraisal::where("id",$id)->first();
            if (empty($appraisalData)) {
                return $this->responseException('Not found appraisal', 404);
            }
            Appraisal::destroy($id);
            return $this->responseSuccess("Delete appraisal success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
