<?php


namespace App\Http\Controllers;

use App\Models\HelpCenterQuestion;
use Validator;
use JWTAuth;
use app\helpers\ValidateResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="HelpCenterQuestion",
 *     description="HelpCenterQuestion APIs",
 * )
 **/
class HelpCenterQuestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/helpQuestion",
     *     tags={"HelpCenterQuestion"},
     *     summary="Get helpQuestion",
     *     description="Get helpQuestion list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getHelpQuestion",
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
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $isHelpSetting = $request->isHelpSetting;
                $getByType = $request->getByType; //type: topic, title
                $getByTypeId = $request->getByTypeId;

                if (($user->role->level == 0 && $isHelpSetting) || ($getByType && $getByTypeId)) {
                    $result = HelpCenterQuestion::where('disable_status', 0);

                    if ($getByType && $getByTypeId) {
                        if ($getByType == 'topic') {
                            $result = $result->where('topic_id', $getByTypeId);
                        } elseif ($getByType == 'title') {
                            $result = $result->where('title_id', $getByTypeId);
                        }
                    }

                    if (($user->role->level == 0 && !$isHelpSetting) || $user->role->level > 1) {
                        $result = $result->where('only_company_admin', 0);
                    }

                    $result = $result->with(['helpTopic'])->get();

                    if ($result) {
                        return $this->responseSuccess($result);
                    }
                }

                return $this->responseSuccess([]);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/helpQuestion",
     *     tags={"HelpCenterQuestion"},
     *     summary="Create new helpQuestion",
     *     description="Create new helpQuestion",
     *     security={{"bearerAuth":{}}},
     *     operationId="createHelpQuestion",
     *     @OA\RequestBody(
     *         description="Help schemas",
     *         @OA\JsonContent(ref="#/components/schemas/HelpQuestion")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $input = $request->all();
                $questionArray = $input['questionArray'];

                if (!$questionArray) {
                    return $this->responseException('Save failed!', 404);
                }

                foreach ($questionArray as $question) {
                    $inputQuestion['topic_id'] = $input['topic_id'];
                    $inputQuestion['title_id'] = $input['title_id'];
                    $inputQuestion['only_company_admin'] = $input['only_company_admin'];
                    $inputQuestion['question'] = $question['question'];
                    $inputQuestion['answer'] = $question['answer'];

                    $rules = HelpCenterQuestion::$rules;
                    $validator = Validator::make($inputQuestion, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
                    $newHelp = HelpCenterQuestion::create($inputQuestion);
                }

                return $this->responseSuccess('Save successfully!');
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/helpQuestion/{id}",
     *     tags={"HelpCenterQuestion"},
     *     summary="Get helpQuestion by id",
     *     description="Get helpQuestion by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getHelpQuestionByIdAPI",
     *     @OA\Parameter(
     *         description="helpQuestion id",
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
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $helpQuestionData = HelpCenterQuestion::find($id);
                if (empty($helpQuestionData)) {
                    return $this->responseException('Not found helpQuestion', 404);
                }
                return $this->responseSuccess($helpQuestionData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/helpQuestion/{id}",
     *     tags={"HelpCenterQuestion"},
     *     summary="Update helpQuestion API",
     *     description="Update helpQuestion API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateHelpQuestionAPI",
     *     @OA\Parameter(
     *         description="helpQuestion id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Help schemas",
     *         @OA\JsonContent(ref="#/components/schemas/HelpQuestion")
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
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $helpQuestionData = HelpCenterQuestion::find($id);
                if (empty($helpQuestionData)) {
                    return $this->responseException('Not found helpQuestion', 404);
                }

                $rules = HelpCenterQuestion::$updateRules;
                $input = $request->all();
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $helpQuestionData->update($input);

                return $this->responseSuccess($helpQuestionData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/helpQuestion/{id}",
     *     tags={"HelpCenterQuestion"},
     *     summary="Delete help API",
     *     description="Delete helpQuestion API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteHelpQuestionAPI",
     *     @OA\Parameter(
     *         description="helpQuestion id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $helpQuestionData = HelpCenterQuestion::find($id);
                if (empty($helpQuestionData)) {
                    return $this->responseException('Not found helpQuestion', 404);
                }
                if ($this->moveToRepository($user['id'], null, 0, 'Help question', $helpQuestionData->id, $helpQuestionData->question)) {
                    $helpQuestionData->update(['disable_status' => 1]);
                    return $this->responseSuccess("Delete help question success");
                }
                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
