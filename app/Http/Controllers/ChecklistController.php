<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Checklist;
use App\Models\Topic;
use App\Models\Question;
use App\Models\ObjectItem;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Responsible;
use App\Models\ChecklistOption;
use App\Models\ChecklistOptionAnswer;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Category;

/**
 * @OA\Tag(
 *     name="Checklists",
 *     description="Checklist APIs",
 * )
 **/
class ChecklistController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/checklists",
     *     tags={"Checklists"},
     *     summary="Get checklists",
     *     description="Get checklists list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getChecklists",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            
            if (!$user = $this->getAuthorizedUser('checklist', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = Checklist::select(
                    'checklists.*',
                    'categories_new.name as category_name',
                    'departments.name as department_name',
                    'job_titles.name as job_title_name',
                    'users.first_name as added_by_first_name',
                    'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name')
                    )
                    ->leftJoin('departments', 'checklists.department_id', '=', 'departments.id')
                    ->leftJoin('categories_new', 'categories_new.id', '=', 'checklists.category_id')
                    ->leftJoin('users', 'checklists.added_by', '=', 'users.id')
                    ->leftJoin('job_titles', 'checklists.job_title_id', '=', 'job_titles.id')
                    ->where('checklists.is_template', 1)
                    ->where('checklists.delete_status', 0);

                    if(isset($request->startDate) && isset($request->endDate)) {
                        $from = date('Y-m-d', strtotime($request->startDate));
                        $to = date('Y-m-d', strtotime($request->endDate));
                        $result = $result->whereBetween('checklists.updated_at', [$from.' 00:00:00',$to.' 23:59:59']);
                    }

                    if(isset($request->reported_by) && !empty($request->reported_by)) {
                        if($request->reported_by !== 0) {
                            $result = $result->where('checklists.added_by',$request->reported_by);
                        }
                    }

                    if(isset($request->by_name) && !empty($request->by_name)) {
                        if(isset($request->startDate) && isset($request->endDate)) {
                            $from = date('Y-m-d', strtotime($request->startDate));
                            $to = date('Y-m-d', strtotime($request->endDate));
                            $result = $result->whereBetween('checklists.updated_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                $q->orWhere('checklists.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            });     
                        } else if(isset($request->reported_by) && !empty($request->reported_by)) {
                            if($request->reported_by !== 0) { 
                                $result = $result->where('checklists.added_by',$request->reported_by)->where(function($q) use($request) {
                                    $q->orWhere('checklists.name', 'Like', "%{$request->by_name}%");
                                    $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                });
                            }
                        } else if(isset($request->reported_by) && (isset($request->startDate) && isset($request->endDate))) {
                            if($request->reported_by !== 0) { 
                                $from = date('Y-m-d', strtotime($request->startDate));
                                $to = date('Y-m-d', strtotime($request->endDate));
                                $result = $result->where('checklists.added_by',$request->reported_by)->whereBetween('checklists.updated_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                    $q->orWhere('checklists.name', 'Like', "%{$request->by_name}%");
                                    $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                });
                            } else {
                                $from = date('Y-m-d', strtotime($request->startDate));
                                $to = date('Y-m-d', strtotime($request->endDate));
                                $result = $result->whereBetween('checklists.updated_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                    $q->orWhere('checklists.name', 'Like', "%{$request->by_name}%");
                                    $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                }); 
                            }
                        } else {
                            $result = $result->where('checklists.name', 'Like', "%{$request->by_name}%")->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                        }
                    }
                   
                    $result = $result->where(function ($q) use ($user) {
                        if ($user['role_id'] > 1) {
                        $q->whereRaw('FIND_IN_SET(?, checklists.industry_id)', [$user['company']['industry_id']])
                            ->where(function ($query) use ($user) {
                                $query->where('checklists.company_id', $user['company_id'])
                                ->orWhere('checklists.added_by', 1);
                            });
                        } else if ($user['role_id'] == 1) {
                            $q->where('checklists.added_by', 1);
                        }
                    })
                    ->orderBy('id','desc')
                    // ->with(['defaultOptions']) 
                    ->paginate(10);
                    
                    

                if ($result) {
                    $result = $this->filterViewList('checklist', $user, $user->filterBy, $result, $orderBy, $limit);

                    foreach ($result as $item) {
                        $item->count_related_object = 0;
                        $item->checklist_used = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = Checklist::where('parent_id', $item['id'])->count();

                            if ($countRelatedObject > 0) {
                                $item->count_related_object = $countRelatedObject;
                            }
                        }

                        if ($item->id) {
                            $topics = Topic::where('checklist_id', $item->id)->get();
                            if ($topics) {
                                foreach ($topics as $topic) {
                                    $defaultOptions = [];
                                    if (!empty($topic['questions'])) {
                                        foreach ($topic['questions'] as $question) {
                                            $dataCheck = ChecklistOption::where("id", $question['default_option_id'])->first();
                                            $question->type_of_option_answer = $dataCheck['type_of_option_answer'] ?? '';
                                            $question->option_name = $dataCheck['name'] ?? '';
                                            $question->option_answers = ChecklistOptionAnswer::where("default_option_id", $question['default_option_id'])
                                                ->get();
                                            $defaultOptions[] = ChecklistOption::where('id',$question->default_option_id)->first();
                                        }
                                    }
                                }
                            }
                            $item->topics = $topics;

                            $item->checklist_used = $item->used_count ?? 0;
                        }
                        // count topics
                        $item->count_topic = Topic::where('checklist_id', $item['id'])->count();
                        $checklistData['id'] = $item['id'];
                        $item->employee_array = $this->getSecurityObject('checklist', $checklistData)["employee_array"] ?? '';
                        $item->employee_names = $this->getSecurityObject('checklist', $checklistData)["employee_names"] ?? ''; 
                        $item->defaultOptions = $defaultOptions ?? []; 
                        $item->topic = Topic::where('checklist_id', $item['id'])->count();
                        $item->checkpoints = Question::where('checklist_id', $item['id'])->count();
                    }
                    return $result;
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
     *     path="/api/v1/checklists",
     *     tags={"Checklists"},
     *     summary="Create new checklist",
     *     description="Create new checklist",
     *     security={{"bearerAuth":{}}},
     *     operationId="createChecklist",
     *     @OA\RequestBody(
     *         description="Checklist schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Checklist")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Checklist $checklist)
    {
        try {
            $input = $request->all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('checklist', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Checklist::$rules;
                $input['added_by'] = $user['id'];
                if ($user->role_id > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $newChecklist = Checklist::create($input);

                if(!empty($input['connectToArray'])){  
                    $this->addConnectToObject($user, $newChecklist['id'], $input['object_type'], $input['connectToArray']);
                }
                if ($newChecklist && $user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Checklist', $newChecklist['id'], $newChecklist['name'], 'create');
                }

                //Handle to create topic
                $topics = $input['topics'];
               
                foreach ($topics as $item) {
                    $topicRules = Topic::$rules;
                    if ($user['company_id']) {
                        $item['company_id'] = $user['company_id'];
                    }
                    $item['checklist_id'] = $newChecklist->id;
                    $topicValidator = Validator::make($item, $topicRules);

                    if ($topicValidator->fails()) {
                        $errors = ValidateResponse::make($topicValidator);
                        return $this->responseError($errors, 400);
                    }
                    $newTopic = Topic::create($item);

                    //Handle to create question
                    $questions = $item['questions'];
                    foreach ($questions as $question) {
                        $questionRules = Question::$rules;
                        $question['added_by'] = $user['id'];
                        if ($user['company_id']) {
                            $question['company_id'] = $user['company_id'];
                        }
                        $question['checklist_id'] = $newChecklist->id;
                        $question['topic_id'] = $newTopic->id;
                        $question['status'] = 'New';
                        $questionValidator = Validator::make($question, $questionRules);

                        if ($questionValidator->fails()) {
                            $errors = ValidateResponse::make($questionValidator);
                            return $this->responseError($errors, 400);
                        }
                        $source_id = Question::create($question);
                        $this->createQuestionObject($input, $user, $question, $source_id);
                    }
                }

                $this->updateChecklistOptions($input['defaultOptions'], $newChecklist['id']);

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newChecklist, $input);

                return $this->responseSuccess($newChecklist);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function createQuestionObject($input, $user, $question, $source_id)
    {
        $inputTemp = $input;

        $rules = ObjectItem::$rules;

        $input['added_by'] = $user['id'];
        $input['type'] = 'checkpoint';
        $input['source_id'] = $source_id['id'];
        $input['required_attachment'] = $question['required_attachment'] ?? 0;
        $input['required_comment'] = $question['required_comment'] ?? 0;

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        unset($input['topics']);
        $newObject = ObjectItem::create($input);

        if ($user['role_id'] > 1) {
            // Responsible
            $this->createObjectResponsible($inputTemp, $newObject, $user, $question);
        }
        return $newObject;
    }


    private function createObjectResponsible($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
        $input['required_comment'] = $inputObject['responsible_required_comment'] ?? 0;
        $input['required_attachment'] = $inputObject['responsible_required_attachment'] ?? 0;

        if ($inputObject['isDefaultResponsible']) {
            $input['employee_array'] = json_encode(array($user['id']));
        } elseif (!empty($inputObject['responsible_employee_array'])) {
            if (!is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] = array($inputObject['responsible_employee_array']);
            }
            $input['employee_array'] = json_encode($inputObject['responsible_employee_array']);
        } elseif (!empty($inputObject['responsible_department_array'])) {   // choose department
            $responsible = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['responsible_department_array'])
                ->pluck('user_id')
                ->toArray();
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = json_encode($responsible);
        } else {    // not choose department & employee
            $input['employee_array'] = json_encode(array($user['id']));
        }

        if ($requestEdit) {
            Responsible::where('object_id', $object['id'])->delete();
        }

        $rules = Responsible::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $responsible = Responsible::create($input);

        $this->requestPushNotification($user['id'], $user['company_id'], json_decode($responsible['employee_array']), 'notification', $object, 'responsible');

        return $responsible;
    }

    private function updateObject($id, $input, $user)
    {
        $inputTemp = $input;

        $objectData = ObjectItem::find($id);
        if (empty($objectData)) {
            return $this->responseException('Not found object', 404);
        }
        $rules = ObjectItem::$updateRules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        unset($input['topics']);
        $objectData->update($input);
        $this->updateConnectToObject($user, $objectData['id'], $objectData['type'], $input['connectToArray']);

        if ($objectData['type'] == 'instruction') {
            // Handle to save Security/
            $this->updateSecurityObject('instruction', $inputTemp, $user['id']);
        }

        // Responsible
        if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
            $objectData->responsible = $this->createObjectResponsible($inputTemp, $objectData, $user, true);
        }

        // Attendee
        if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
            $objectData->attendee = $this->createObjectAttendee($inputTemp, $objectData, $user, true);

            // Attendee processing
            $attendee = Attendee::where('object_id', $objectData['id'])->first();
            $objectData->processing = $this->createObjectAttendeeProcessing($attendee, $user, true);
        }

        // Time management
        if ($inputTemp['start_date'] || $inputTemp['deadline']) {
            $objectData->time = $this->createObjectTimeManagement($inputTemp, $objectData, $user, true);
        }

        // Source of danger
        if ($inputTemp['type'] == 'risk-analysis') {
            $objectData->source_of_danger = $this->createObjectSourceOfDanger($inputTemp['source_of_danger'], $objectData, $user, true);
        }

        return $objectData;
    }

    // private function createObjectResponsible($inputObject, $object, $user,$question) {
    //     $input['company_id'] = $object['company_id'];
    //     $input['added_by'] = $user['id'];
    //     $input['object_id'] = $object['id']; 
    //     $input['required_attachment'] = $question['required_attachment'] ?? 0;
    //     $input['required_comment'] = $question['required_comment'] ?? 0;
    //     if (empty($inputObject['department_array']) && empty($inputObject['employee_array'])) {
    //         // not choose department & employee
    //         $input['employee_array'] = json_encode(array($user['id'])); 
    //     } else if (!empty($inputObject['department_array']) && empty($inputObject['employee_array'])) {
    //         // choose department - not choose employee
    //         $responsible = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
    //             ->where('users.company_id', $object['company_id'])
    //             ->whereIn('users.role_id', [2, 3])
    //             ->whereIn('employees.department_id', $inputObject['department_array'])->pluck('user_id')->toArray(); 
    //         if (!is_array($responsible)) {
    //             $responsible = array($responsible);
    //         }
    //         $input['employee_array'] = json_encode($responsible);
    //     } else if (!empty($inputObject['employee_array'])) { 
    //         if (!is_array($inputObject['employee_array'])) {
    //             $inputObject['employee_array'] = array($inputObject['employee_array']);
    //         }
    //         $input['employee_array'] = json_encode($inputObject['employee_array']);
    //     }

    //     $rules = Responsible::$rules;
    //     $validator = Validator::make($input, $rules);
    //     if ($validator->fails()) {
    //         $errors = ValidateResponse::make($validator);
    //         return $this->responseError($errors,400);
    //     }
    //     return Responsible::create($input);
    // }

    /**
     * @OA\Get(
     *     path="/api/v1/checklists/{id}",
     *     tags={"Checklists"},
     *     summary="Get checklist by id",
     *     description="Get checklist by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getChecklistByIdAPI",
     *     @OA\Parameter(
     *         description="checklist id",
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
            $checklistData = Checklist::leftJoin('departments', 'checklists.department_id', '=', 'departments.id')
                ->leftJoin('job_titles', 'checklists.job_title_id', '=', 'job_titles.id')
                ->where('checklists.id', $id)
                ->select('checklists.*', 'departments.name as department_name', 'job_titles.name as job_title_name')
                // ->with(['defaultOptions'])
                ->first();
            if (empty($checklistData)) {
                return $this->responseException('Not found checklist', 404);
            }

          
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'checklist',
                'objectItem' => $checklistData,
            ];
            if (!$user = $this->getAuthorizedUser('checklist', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $checklistData->count_related_object = 0;
                $checklistData->related_objects = '';
                if ($checklistData['is_template']) {
                    $relatedObject = Checklist::leftJoin('users', 'checklists.added_by', '=', 'users.id')
                        ->leftJoin('companies', 'checklists.company_id', '=', 'companies.id') 
                        ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('checklists.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select(
                        'checklists.id',
                        'checklists.name',
                        'users.first_name as added_by_first_name',
                        'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'companies.name as company_name'
                    )
                        ->get();

                    if (count($relatedObject) > 0) {
                        $checklistData->count_related_object = count($relatedObject);
                        $checklistData->related_objects = $relatedObject;
                    }
                }
                if (!empty($checklistData->added_by)) {
                    $added_by =  User::where('id', $checklistData->added_by)->select('first_name', 'last_name')->first();
                    $checklistData->added_by_name = $added_by->first_name . ' ' . $added_by->last_name;
                }
                $checklistData->editPermission = $user->editPermission;
                // get Security information
                $c_data['id'] = $checklistData->id;
                $checklistData->employee_array = !empty($this->getSecurityObject('checklist', $c_data)["employee_array"]) ? json_decode($this->getSecurityObject('checklist', $c_data)["employee_array"]) : '';
                $checklistData->employee_names = $this->getSecurityObject('checklist', $c_data)["employee_names"] ?? '';
                $checklistData->function = 'checklist';
                $category_name =  DB::table('categories_new')->where('id', $checklistData->category_id)->select('name')->first();
               
                // $resp =  Responsible::where('object_id', $checklistData->id)->select('employee_array')->first();
                // $checklistData->responsible = $resp;
                $checklistData->category_name = $category_name->name ?? '';
                // $public = Checklist::where('id', $checklistData->id)->with(['defaultOptions'])->first();
                // $checklistData->is_public = $public->is_public ?? 0;
                // $checklistData->defaultOptions = $public->defaultOptions ?? '';
                $topi = Topic::where('checklist_id', $checklistData->id)->get();
                if ($topi) {
                    foreach ($topi as $topic) {
                        $defaultOptions = [];
                        if (!empty($topic['questions'])) {
                            foreach ($topic['questions'] as $question) {
                                $dataCheck = ChecklistOption::where("id", $question['default_option_id'])->first();
                                $question->type_of_option_answer = $dataCheck['type_of_option_answer'] ?? '';
                                $question->option_name = $dataCheck['name'] ?? '';
                                $question->checklist_required_comment = $dataCheck['checklist_required_comment'] ?? '';
                                $question->checklist_required_attachment = $dataCheck['checklist_required_attachment'] ?? '';
                                $question->option_answers = ChecklistOptionAnswer::where("default_option_id", $question['default_option_id'])
                                    ->get();
                                $defaultOptions[] = ChecklistOption::where('id',$question->default_option_id)->first();

                            }
                        }
                    }
                }
                $checklistData->topics =$topi;
                $checklistData->default_options = $defaultOptions ?? [];
                // $checklistData->defaultOptions = $public->defaultOptions ?? '';
                // $this->getSecurityObject('checklist', $checklistData); 
                return $this->responseSuccess($checklistData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/checklists/{id}",
     *     tags={"Checklists"},
     *     summary="Update checklist API",
     *     description="Update checklist API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateChecklistAPI",
     *     @OA\Parameter(
     *         description="checklist id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Checklist schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Checklist")
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
            $rules = Checklist::$updateRules;
            $input = $request->all();

            $checklistData = Checklist::find($id);
            if (empty($checklistData)) {
                return $this->responseException('Not found checklist', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'checklist',
                'objectItem' => $checklistData,
            ];

            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('checklist', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $checklistData->update($input);

                $this->updateConnectToObject($user, $checklistData['id'], 'checklist', $input['connectToArray']);

                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Checklist', $checklistData['id'], $checklistData['name'], 'update');
                }

                //Handle to update topic
                $topics = $input['topics'];
                $oldTopics = Topic::where('checklist_id', $id)
                    ->pluck('id')->toArray();
                $topicDiff = array_diff($oldTopics, array_column($topics, 'id'));
                //delete topic
                // Topic::whereIn("id", $topicDiff)->delete();

                foreach ($topics as $topic) {
                    if (!empty($topic['id'])) {
                        $res = Topic::find($topic['id']);
                        if (!empty($res)) {
                            Topic::find($topic['id'])->update([
                                'name' => $topic['name'],
                            ]);

                            $oldQuestions = Topic::find($topic['id'])->questions->pluck('id')->toArray();
                            $newQuestions = array_column($topic['questions'], 'id');

                            $questionDiff = array_diff($oldQuestions, $newQuestions);

                            Question::whereIn("id", $questionDiff)->delete();

                            foreach ($topic['questions'] as $question) {
                                if (isset($question['id'])) {
                                    $this->updateObject($question['id'], $input, $user);
                                    Question::find($question['id'])->update([
                                        'name' => $question['name'],
                                        'default_option_id' => $question['default_option_id']
                                    ]);
                                } else {
                                    $questionRules = Question::$rules;
                                    $question['added_by'] = $user['id'];
                                    $question['company_id'] = $user['company_id'];
                                    $question['checklist_id'] = $checklistData->id;
                                    $question['topic_id'] = $topic['id'];
                                    $question['status'] = 'New';
                                    $questionValidator = Validator::make($question, $questionRules);

                                    if ($questionValidator->fails()) {
                                        $errors = ValidateResponse::make($questionValidator);
                                        return $this->responseError($errors, 400);
                                    }
                                    Question::create($question);
                                }
                            }
                        }
                    } else {
                        $topicRules = Topic::$rules;
                        $topic['added_by'] = $user['id'];
                        $topic['checklist_id'] = $checklistData->id;
                        $topic['company_id'] = $user['company_id'];
                        $topic['status'] = 'New';
                        $topicValidator = Validator::make($topic, $topicRules);

                        if ($topicValidator->fails()) {
                            $errors = ValidateResponse::make($topicValidator);
                            return $this->responseError($errors, 400);
                        }
                        $newTopic = Topic::create($topic);

                        //Handle to create question
                        $questions = $topic['questions'];
                        foreach ($questions as $question) {
                            $questionRules = Question::$rules;
                            $question['added_by'] = $user['id'];
                            $question['company_id'] = $user['company_id'];
                            $question['checklist_id'] = $checklistData->id;
                            $question['topic_id'] = $newTopic->id;
                            $question['status'] = 'New';
                            $questionValidator = Validator::make($question, $questionRules);

                            if ($questionValidator->fails()) {
                                $errors = ValidateResponse::make($questionValidator);
                                return $this->responseError($errors, 400);
                            }
                            Question::create($question);
                        }
                    }
                }

                $this->updateChecklistOptions($input['defaultOptions'], $checklistData['id']);

                // update Security
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('checklist', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('checklist', $input, null);
                }

                return $this->responseSuccess($checklistData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

 
    private function updateChecklistOptions($options, $checklistID)
    {
        if (!empty($options)) {
            foreach ($options as $option) {
                $optionData = ChecklistOption::find($option['id']); 
                $optionRules = ChecklistOption::$rules;
                $newOption['name'] = $option['name'];
                $newOption['type_of_option_answer'] = $option['type_of_option_answer'];
                $newOption['company_id'] = $option['company_id'];
                $newOption['checklist_id'] = $checklistID;
                $newOption['count_option_answers'] = $option['count_option_answers'];
                $newOption['is_template'] = 0;
                $newOption['count_used_time'] = 1;
                $newOption['added_by'] = $option['added_by'];
                $optionValidator = Validator::make($newOption, $optionRules);
                if ($optionValidator->fails()) {
                    $errors = ValidateResponse::make($optionValidator);
                    return $this->responseError($errors, 400);
                }
               
                if (!empty($optionData)) {
                    if ($option['is_template'] == 1) {
                        // $new = ChecklistOption::create($newOption);
                        // $optionData = ChecklistOption::find($new->id);
                        if (!empty($optionData)) {
                            $optionData->update(['count_used_time' => $optionData['count_used_time'] + 1]);
                        } 
                    } else {
                        $optionData->update(['checklist_id' => $checklistID]);
                    }
                } else { 
                    ChecklistOption::create($newOption);
                }
            }
        }
        return $options;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/checklists/{id}",
     *     tags={"Checklists"},
     *     summary="Delete checklist API",
     *     description="Delete checklist API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteChecklistAPI",
     *     @OA\Parameter(
     *         description="checklist id",
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
            $checklistData = Checklist::find($id);
            if (empty($checklistData)) {
                return $this->responseException('Not found checklist', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'checklist',
                'objectItem' => $checklistData,
            ];
            if ($checklistData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('checklist', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($checklistData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Checklist', $checklistData->id, $checklistData->name)) {
                    $checklistData->update(['delete_status' => 1]);

                    //                    Checklist::destroy($id);
                    return $this->responseSuccess("Delete checklist success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
