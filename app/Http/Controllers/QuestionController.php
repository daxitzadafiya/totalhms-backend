<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\Question;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Questions",
 *     description="Question APIs",
 * )
 **/
class QuestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/questions",
     *     tags={"Questions"},
     *     summary="Get questions",
     *     description="Get questions list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getQuestions",
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
                $topic = $request->topic;
                if($topic){
                    $result = Question::where('topic_id', $topic)
                        ->get();
                }else{
                    $result = Question::where('company_id', $user -> company_id)
                        ->get();
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
     *     path="/api/v1/questions",
     *     tags={"Questions"},
     *     summary="Create new question",
     *     description="Create new question",
     *     security={{"bearerAuth":{}}},
     *     operationId="createQuestion",
     *     @OA\RequestBody(
     *         description="question schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Question")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Question $question)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Question::$rules;
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newQuestion = Question::create($input);
                return $this->responseSuccess($newQuestion,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/questions/{id}",
     *     tags={"Questions"},
     *     summary="Get question by id",
     *     description="Get question by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getQuestionByIdAPI",
     *     @OA\Parameter(
     *         description="question id",
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
            $questionData = Question::where("id",$id)->first();
            if (empty($questionData)) {
                return $this->responseException('Not found question', 404);
            }

            return $this->responseSuccess($questionData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/questions/{id}",
     *     tags={"Questions"},
     *     summary="Update question API",
     *     description="Update question API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateQuestionAPI",
     *     @OA\Parameter(
     *         description="question id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Questions schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Question")
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
            $rules = Question::$updateRules;
            $input = $request -> all();

            $questionData = Question::where("id",$id)->first();
            if (empty($questionData)) {
                return $this->responseException('Not found question', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $questionData->update($input);

            return $this->responseSuccess($questionData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/questions/{id}",
     *     tags={"Questions"},
     *     summary="Delete question API",
     *     description="Delete question API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteQuestionAPI",
     *     @OA\Parameter(
     *         description="question id",
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
            $questionData = Question::where("id",$id)->first();
            if (empty($questionData)) {
                return $this->responseException('Not found question', 404);
            }
            Question::destroy($id);
            return $this->responseSuccess("Delete question success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
