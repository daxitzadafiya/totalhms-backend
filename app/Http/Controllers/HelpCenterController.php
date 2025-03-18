<?php


namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentNew;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use app\helpers\ValidateResponse;
use App\Models\HelpCenter;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="HelpCenter",
 *     description="HelpCenter APIs",
 * )
 **/
class HelpCenterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/help",
     *     tags={"HelpCenter"},
     *     summary="Get help",
     *     description="Get help list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getHelp",
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
                $getByType = $request->getByType; //type: main article, topic, title
                $getByParentId = $request->getByParentId;

                $result = HelpCenter::where('help_center.disable_status', 0)
                    ->leftJoin(DB::raw('(SELECT * FROM help_center) AS PD'), 'help_center.parent_id', '=', 'PD.id');

                if ($getByType) {
                    $result = $result->where('help_center.type', $getByType);
                    if ($getByParentId) {
                        $result = $result->where('help_center.parent_id', $getByParentId);
                    }
                }

                if (!$isHelpSetting) {
                    if ($user->role->level == 0) {
                        $result = $result->where('help_center.role', 'Super admin');
                    } elseif ($user->role->level == 1) {
                        $result = $result->whereIn('help_center.role', ['Manager', 'User']);
                    } elseif ($user->role->level == 2) {
                        $result = $result->whereIn('help_center.role', ['Manager', 'User'])->where('only_company_admin', 0);
                    } elseif ($user->role->level == 3) {
                        $result = $result->where('help_center.role', 'User');
                    } else {
                        $result = $result->where('help_center.role', '');
                    }
                }

                $result = $result->get(['help_center.*', 'PD.name as parent_name']);

                if ($result) {
                    if (!$isHelpSetting) {
                        foreach ($result as $help) {
                            if ($help['type'] === 'Main article') {
                                $help['topics'] = $result->where('type', 'Topic')
                                    ->where('parent_id', $help['id']);
                            }
                        }
                        $result = $result->where('type', 'Main article');
                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/help",
     *     tags={"HelpCenter"},
     *     summary="Create new help",
     *     description="Create new help",
     *     security={{"bearerAuth":{}}},
     *     operationId="createHelp",
     *     @OA\RequestBody(
     *         description="Help schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Help")
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
                $topicArray = $input['topicArray'];

                if ($input['type'] == 'Topic') {
                    if (!$topicArray) {
                        return $this->responseException('Save failed!', 404);
                    }

                    foreach ($topicArray as $topic) {
                        $input['name'] = $topic['name'];

                        $rules = HelpCenter::$rules;
                        $validator = Validator::make($input, $rules);
                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($validator);
                            return $this->responseError($errors, 400);
                        }
                        HelpCenter::create($input);
                    }
                    return $this->responseSuccess('Save successfully!');
                } else {
                    $rules = HelpCenter::$rules;

                    $validator = Validator::make($input, $rules);

                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
                    $newHelp = HelpCenter::create($input);

                    return $this->responseSuccess($newHelp);
                }
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/help/{id}",
     *     tags={"HelpCenter"},
     *     summary="Get help by id",
     *     description="Get help by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getHelpByIdAPI",
     *     @OA\Parameter(
     *         description="help id",
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
                $helpData = HelpCenter::where('id', $id)
                    ->with(['questions'])
                    ->first();
                if (empty($helpData)) {
                    return $this->responseException('Not found help', 404);
                }
//                $doc = Document::whereRaw('FIND_IN_SET(?, documents.help_center_id)', [$id])->first();
                $doc = DocumentNew::where('company_id', $user['company_id'])
                    ->where('object_type', 'help center')
                    ->whereRaw('FIND_IN_SET(?, documents_new.object_id)', [$id])
                    ->first();
                if (!empty($doc)) {
                    $baseUrl = config('app.app_url');
//                    $baseUrl = 'http://localhost:8000';
                    $helpData['uri'] = $doc['uri'];
                    $helpData['original_file_name'] = $doc['original_file_name'];
                    $helpData['url'] = $baseUrl . "/api/v1/image/" . $doc['uri'];
                }
                return $this->responseSuccess($helpData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/help/{id}",
     *     tags={"HelpCenter"},
     *     summary="Update help API",
     *     description="Update help API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateHelpAPI",
     *     @OA\Parameter(
     *         description="help id",
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
     *         @OA\JsonContent(ref="#/components/schemas/Help")
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
                $helpData = HelpCenter::find($id);
                if (empty($helpData)) {
                    return $this->responseException('Not found help', 404);
                }

                $rules = HelpCenter::$updateRules;
                $input = $request->all();
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $helpData->update($input);

                return $this->responseSuccess($helpData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/help/{id}",
     *     tags={"HelpCenter"},
     *     summary="Delete help API",
     *     description="Delete help API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteHelpAPI",
     *     @OA\Parameter(
     *         description="help id",
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
                $helpData = HelpCenter::find($id);
                if (empty($helpData)) {
                    return $this->responseException('Not found help', 404);
                }
                if ($this->moveToRepository($user['id'], null, 0, 'Help center', $helpData->id, $helpData->name)) {
                    $helpData->update(['disable_status' => 1]);
                    return $this->responseSuccess("Delete help guide success");
                }
                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
