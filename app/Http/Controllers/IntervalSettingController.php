<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\IntervalSetting;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="IntervalSetting",
 *     description="IntervalSetting APIs",
 * )
 **/
class IntervalSettingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/intervalSetting",
     *     tags={"IntervalSetting"},
     *     summary="Get IntervalSetting",
     *     description="Get IntervalSetting list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getIntervalSetting",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $type = $request->type;
                $result = IntervalSetting::where('company_id', $user['company_id']);
                if (!empty($type)) {
                    $result = $result->where('type', $type);
                }
                $result = $result->get();
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
     *     path="/api/v1/intervalSetting",
     *     tags={"IntervalSetting"},
     *     summary="Create new IntervalSetting",
     *     description="Create new IntervalSetting",
     *     security={{"bearerAuth":{}}},
     *     operationId="createIntervalSetting",
     *     @OA\RequestBody(
     *         description="IntervalSetting schemas",
     *         @OA\JsonContent(ref="#/components/schemas/IntervalSetting")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, IntervalSetting $intervalSetting)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $rules = IntervalSetting::$rules;
                $input = $request -> all();

                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newIntervalSetting = IntervalSetting::create($input);

                return $this->responseSuccess($newIntervalSetting,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/intervalSetting/{id}",
     *     tags={"IntervalSetting"},
     *     summary="Get IntervalSetting by id",
     *     description="Get IntervalSetting by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getIntervalSettingByIdAPI",
     *     @OA\Parameter(
     *         description="IntervalSetting id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $intervalSettingData = IntervalSetting::find($id);
                if (empty($intervalSettingData)) {
                    return $this->responseException('Not found IntervalSetting', 404);
                }
                return $this->responseSuccess($intervalSettingData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/intervalSetting/{id}",
     *     tags={"IntervalSetting"},
     *     summary="Update IntervalSetting API",
     *     description="Update IntervalSetting API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateIntervalSettingAPI",
     *     @OA\Parameter(
     *         description="IntervalSetting id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="IntervalSetting schemas",
     *         @OA\JsonContent(ref="#/components/schemas/IntervalSetting")
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $rules = IntervalSetting::$updateRules;
                $input = $request->all();

                $intervalSettingData = IntervalSetting::find($id);
                if (empty($intervalSettingData)) {
                    return $this->responseException('Not found IntervalSetting', 404);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $intervalSettingData->update($input);

                return $this->responseSuccess($intervalSettingData, 201);

            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/intervalSetting/{id}",
     *     tags={"IntervalSetting"},
     *     summary="Delete IntervalSetting API",
     *     description="Delete IntervalSetting API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteIntervalSettingAPI",
     *     @OA\Parameter(
     *         description="IntervalSetting id",
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
            $intervalSettingData = IntervalSetting::find($id);
            if (empty($intervalSettingData)) {
                return $this->responseException('Not found IntervalSetting', 404);
            }
            IntervalSetting::destroy($id);
            return $this->responseSuccess("Delete IntervalSetting success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
