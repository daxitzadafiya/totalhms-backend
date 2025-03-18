<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\Topic;
use App\Models\ChecklistOption;
use App\Models\ChecklistOptionAnswer;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Topics",
 *     description="Topic APIs",
 * )
 **/
class TopicController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/topics",
     *     tags={"Topics"},
     *     summary="Get topics",
     *     description="Get topics list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTopics",
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
                $checklist = $request->checklist;
                if($checklist){
                    $result = Topic::where('checklist_id', $checklist)
                        ->with(['questions'])
                        ->get();
                }else{
                    $result = Topic::where('company_id', $user -> company_id)
                        ->get();
                }

                if($result){
                    foreach ($result as $topic) {
                        if (!empty($topic['questions'])) {
                            foreach ($topic['questions'] as $question) { 
                                $dataCheck = ChecklistOption::where("id", $question['default_option_id'])->first();
                                $question->type_of_option_answer = $dataCheck['type_of_option_answer'] ?? '';
                                $question->option_name = $dataCheck['name'] ?? '';
                                $question->option_answers = ChecklistOptionAnswer::where("default_option_id", $question['default_option_id'])
                                    ->get();
                            }
                        }
                    }
                    return $this->responseSuccess($result);
                }else{
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/topics",
     *     tags={"Topics"},
     *     summary="Create new topic",
     *     description="Create new topic",
     *     security={{"bearerAuth":{}}},
     *     operationId="createTopic",
     *     @OA\RequestBody(
     *         description="topic schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Topic")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Topic $topic)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Topic::$rules;
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newTopic = Topic::create($input);
                return $this->responseSuccess($newTopic,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/topics/{id}",
     *     tags={"Topics"},
     *     summary="Get topic by id",
     *     description="Get topic by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTopicByIdAPI",
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
    public function show(Request $request, $id)
    {
        try {
            $topicData = Topic::where("id",$id)->first();
            if (empty($topicData)) {
                return $this->responseException('Not found topic', 404);
            }

            return $this->responseSuccess($topicData,201);

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
