<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="VerificationCode",
 *     description="VerificationCode APIs",
 * )
 **/
class VerificationCodeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/verificationCode/{code}",
     *     tags={"VerificationCode"},
     *     summary="Get verificationCode by id",
     *     description="Get verificationCode by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getVerificationCodeByCodeAPI",
     *     @OA\Parameter(
     *         description="verificationCode code",
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
    public function show(Request $request, $code)
    {
        try {
            $verificationCodeData = VerificationCode::where("code", $code)
                ->with(['user'])
                ->first();
            if (empty($verificationCodeData)) {
                return $this->responseException('Invalid token', 404);
            }

            $today = new \DateTime('now');
            $expired = new \DateTime($verificationCodeData->expired_time);
            if ($today > $expired) {
                $verificationCodeData->delete();
                return $this->responseException('Invalid token', 404);
            }

            return $this->responseSuccess($verificationCodeData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/topics/{id}",
     *     tags={"Topics"},
     *     summary="Update topic API",
     *     description="Update topic API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateTopicAPI",
     *     @OA\Parameter(
     *         description="topic id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Topics schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Topic")
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
            $rules = Topic::$updateRules;
            $input = $request -> all();

            $topicData = Topic::where("id",$id)->first();
            if (empty($topicData)) {
                return $this->responseException('Not found topic', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $topicData->update($input);

            return $this->responseSuccess($topicData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/topics/{id}",
     *     tags={"Topics"},
     *     summary="Delete topic API",
     *     description="Delete topic API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteTopicAPI",
     *     @OA\Parameter(
     *         description="topic id",
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
            $topicData = Topic::where("id",$id)->first();
            if (empty($topicData)) {
                return $this->responseException('Not found topic', 404);
            }
            Topic::destroy($id);
            return $this->responseSuccess("Delete topic success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

}
