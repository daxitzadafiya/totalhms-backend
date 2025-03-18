<?php

namespace App\Http\Controllers;


use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\ChecklistOptionAnswer;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="OptionAnswers",
 *     description="ChecklistOptionAnswer APIs",
 * )
 **/

class ChecklistOptionAnswerController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/optionAnswers/{id}",
     *     tags={"OptionAnswers"},
     *     summary="Get optionAnswer by id",
     *     description="Get optionAnswer by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDocumentByIdAPI",
     *     @OA\Parameter(
     *         description="optionAnswer id",
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
    public function index(Request $request)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $option_id = $request->option;
                $result = ChecklistOptionAnswer::where('default_option_id', $option_id)->get();
                if($result){
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

}
