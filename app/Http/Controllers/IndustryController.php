<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\Industry;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Industries",
 *     description="Industries APIs",
 * )
 **/

class IndustryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/industries",
     *     tags={"Industries"},
     *     summary="Get industries",
     *     description="Get industries list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getIndustries",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (!$user = $this->getAuthorizedUser('industry', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $result = Industry::all();
                if ($result) {
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/industries",
     *     tags={"Industries"},
     *     summary="Create new industry",
     *     description="Create new industry",
     *     security={{"bearerAuth":{}}},
     *     operationId="createIndustry",
     *     @OA\RequestBody(
     *         description="Industry schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Industry")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Industry $industry)
    {
        try {
            if (!$user = $this->getAuthorizedUser('industry', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Industry::$rules;
                $input = $request->all();

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $newIndustry = Industry::create($input);
                return $this->responseSuccess($newIndustry, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/industries/{id}",
     *     tags={"Industries"},
     *     summary="Get industry by id",
     *     description="Get industry by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getIndustryByIdAPI",
     *     @OA\Parameter(
     *         description="industry id",
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
            if (!$user = $this->getAuthorizedUser('industry', 'detail', 'show', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
            $industryData = Industry::find($id);
            if (empty($industryData)) {
                return $this->responseException('Not found industry', 404);
            }
            return $this->responseSuccess($industryData);
}
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/industries/{id}",
     *     tags={"Industries"},
     *     summary="Update industry API",
     *     description="Update industry API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateIndustryAPI",
     *     @OA\Parameter(
     *         description="industry id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Industry schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Industry")
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
            if (!$user = $this->getAuthorizedUser('industry', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Industry::$updateRules;
                $input = $request->all();

                $industryData = Industry::where("id", $id)->first();
                if (empty($industryData)) {
                    return $this->responseException('Not found industry', 404);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $industryData->update($input);

                return $this->responseSuccess($industryData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/industries/{id}",
     *     tags={"Industries"},
     *     summary="Delete industry API",
     *     description="Delete industry API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteIndustryAPI",
     *     @OA\Parameter(
     *         description="industry id",
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
            $industryData = Industry::where("id",$id)->first();
            if (empty($industryData)) {
                return $this->responseException('Not found industry', 404);
            }
            Industry::destroy($id);
            return $this->responseSuccess("Delete industry success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
