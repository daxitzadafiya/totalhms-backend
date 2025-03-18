<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Models\Company;
use App\Models\Deviation;
use App\Models\Report;
use App\Models\RiskAnalysis;
use App\Models\RiskElement;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\ObjectItem;
use App\Models\Employee;
use App\Models\SourceOfDanger;
use App\Models\Responsible;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RiskAnalysis",
 *     description="RiskAnalysis APIs",
 * )
 **/
class RiskAnalysisController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/riskAnalysis",
     *     tags={"RiskAnalysis"},
     *     summary="Get riskAnalysis",
     *     description="Get riskAnalysis list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRiskAnalysis",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('risk analysis', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $getByProjectID = $request->getByProjectID;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
//                $checkPermission = $user->hasAccess('update-riskanalysis');
                $result = RiskAnalysis::where('company_id', $user->company_id);
//                if(!$checkPermission){
//                    $result = $result->where('added_by', $user->id);
//                }
                $result = $result->with(['user', 'elements', 'tasks'])->get();
                if($result) {
                    $result = $this->filterViewList('risk analysis', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $report) {
                        $reportedUser = User::find($report['added_by']);
                        $report['added_by_name'] = $reportedUser['first_name'] . " " . $reportedUser['last_name'];
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
     *     path="/api/v1/riskAnalysis",
     *     tags={"RiskAnalysis"},
     *     summary="Create new riskAnalysis",
     *     description="Create new riskAnalysis",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRiskAnalysis",
     *     @OA\RequestBody(
     *         description="RiskAnalysis schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RiskAnalysis")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, RiskAnalysis $riskAnalysis)
    { 
        try {
            if (!$user = $this->getAuthorizedUser('risk analysis', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $rules = RiskAnalysis::$rules;
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user['company_id']; 

                if(empty($input['checklist'])){
                    return $this->responseException('Checklist id is required.', 400);
                }
                if ($user->employee->nearest_manager) {
                    $input['responsible'] = $user->employee->nearest_manager;
                } else {
                    $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                    if ($companyAdmin) {
                        $input['responsible'] = $companyAdmin->id;
                    }
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newRiskAnalysis = RiskAnalysis::create($input);
                $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'risk', 'Risk analysis', $newRiskAnalysis['id'], $newRiskAnalysis['name'], 'responsible');

                $riskElements = $input['risk_elements'] ?? '';
                if (!empty($riskElements)) {
                    foreach ($riskElements as $element) {
                        $elementRules = RiskElement::$rules;
                        $element['risk_analysis_id'] = $newRiskAnalysis->id;
                        $element['added_by'] = $user['id'];
                        $elementValidator = Validator::make($element, $elementRules);

                        if ($elementValidator->fails()) {
                            $errors = ValidateResponse::make($elementValidator);
                            return $this->responseError($errors,400);
                        }
                        RiskElement::create($element);
                    }
                }
                
                if (!empty($input['report_id'])) {
                    $report = Report::find($input['report_id']);
                    $answers = json_decode($report->answer);
                    foreach ($answers as $answer){
                        if ($answer->question_id == $input['question_id']) {
                            $answer->action = $input['action'] ?? '';
                            $answer->risk_id = $newRiskAnalysis['id'];
                        }
                    }
                    $report->answer = json_encode($answers);

                    $data = array(
                        'answer' => $report->answer,
                        'status' => 2
                    );
                    $report->update($data);
                }
                $source_id = $newRiskAnalysis->id;
                $obj = $this->createObject($input, $user,$source_id);
                $this->createObjectSourceOfDanger($riskElements, $obj->id, $user);
                if($input['status'] == 1){
                    $input['status'] = 'New';
                }elseif($input['status'] == 3){
                    $input['status'] = 'Completed';
                }
                // Handle to save Security/ Connect to
                $this->createSecurityObject($newRiskAnalysis, $input);

                return $this->responseSuccess($newRiskAnalysis);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function createObject ($input, $user,$source_id) {
        $inputTemp = $input;
       
        $rules = ObjectItem::$rules;
        
        $input['added_by'] = $user['id'];
        $input['type'] = 'risk-analysis';
        $input['source_id'] = $source_id;
        $input['required_attachment'] = 0;
        $input['required_comment'] = 0;
         
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        $newObject = ObjectItem::create($input);
        
        if ($user['role_id'] > 1) {
            // Responsible
            $this->createObjectResponsible($inputTemp, $newObject, $user); 
        }
        
        return $newObject;
    }
    
    private function createObjectSourceOfDanger($inputArray, $source_id, $user) {
        $array = $inputArray; 

        foreach ($array as $item) {
            $input['company_id'] = $user['company_id'];
            $input['added_by'] = $user['id'];
            $input['object_id'] = $source_id; 
            $input['name'] = $item['name'];
            $input['probability'] = $item['probability'];
            $input['consequence'] = $item['consequence'];
            $input['comment'] = $item['comment'];
            // $input['need_to_process'] = $item['need_to_process'];
            $rules = SourceOfDanger::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            SourceOfDanger::create($input);
        }
        return $array;
    }

    private function createObjectResponsible($inputObject, $object, $user) {
        $input['company_id'] = $object['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id']; 
        $input['required_attachment'] = 0;
        $input['required_comment'] = 0;
        if (empty($inputObject['department_array']) && empty($inputObject['employee_array'])) {
            // not choose department & employee
            $input['employee_array'] = json_encode(array($user['id'])); 
        } else if (!empty($inputObject['department_array']) && empty($inputObject['employee_array'])) {
            // choose department - not choose employee
            $responsible = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['department_array'])->pluck('user_id')->toArray(); 
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = json_encode($responsible);
        } else if (!empty($inputObject['employee_array'])) { 
            if (!is_array($inputObject['employee_array'])) {
                $inputObject['employee_array'] = array($inputObject['employee_array']);
            }
            $input['employee_array'] = json_encode($inputObject['employee_array']);
        }

        $rules = Responsible::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        return Responsible::create($input);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/riskAnalysis/{id}",
     *     tags={"RiskAnalysis"},
     *     summary="Get riskAnalysis by id",
     *     description="Get riskAnalysis by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRiskAnalysisByIdAPI",
     *     @OA\Parameter(
     *         description="riskAnalysis id",
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
            $riskAnalysisData = RiskAnalysis::leftJoin('projects', 'risk_analysis.project_id','=', 'projects.id')
                ->leftJoin('departments','risk_analysis.department_id','=','departments.id')
                ->leftJoin('job_titles','risk_analysis.job_title_id','=','job_titles.id')
                ->where('risk_analysis.id', $id)
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }, 'elements'])
                ->select('risk_analysis.*','projects.name as project_name','departments.name as department_name','job_titles.name as job_title_name')
                ->first();
            if (empty($riskAnalysisData)) {
                return $this->responseException('Not found risk analysis', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk analysis',
                'objectItem' => $riskAnalysisData,
            ]; 
            $department = Department::where('id', $riskAnalysisData->department_id)->first();
            if ($department) {
                $riskAnalysisData->department_name = $department->name;
            } 
            $added_by = User::where('id', $riskAnalysisData->added_by)->select('first_name','last_name')->first();
            if ($added_by) {
                $riskAnalysisData->added_by_name = $added_by->first_name .' '.$added_by->last_name;
            } 
            $responsible = User::where('id', $riskAnalysisData->responsible)->select('first_name','last_name')->first();
            if ($responsible) {
                $riskAnalysisData->responsible_name = $responsible->first_name .' '.$responsible->last_name;
            } 
            if (!$user = $this->getAuthorizedUser('risk analysis', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                foreach ($riskAnalysisData->tasks as $key => $task) {
                    $task->remaining_time = '';
                    if ($task->deadline) {
                        $task->remaining_time = $this->calRemainingTime($task->deadline);
                    }
                    $riskAnalysisData->responsible_id = $task->responsible_id;
                    $riskAnalysisData->deadline = $task->deadline;
                }
//                if ($riskAnalysisData->status == 1) {
//                    $riskAnalysisData['is_action_done'] = false;
//                } else {
//                    $tasks = Task::where('type', '=', 'Risk analysis')
//                        ->where('type_id', $id)
//                        ->whereIn('status', [1,2])->get();
//                    if (count($tasks) == 0) {
//                        $riskAnalysisData['is_action_done'] = true; // done all deviation tasks
//                    } else {
//                        $riskAnalysisData['is_action_done'] = false;
//                    }
//                }
 
                // get Security information
                // $this->getSecurityObject('risk-analysis', $riskAnalysisData);

                return $this->responseSuccess($riskAnalysisData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/user/riskAnalysis/{id}",
     *     tags={"RiskAnalysis"},
     *     summary="Get riskAnalysis by id",
     *     description="Get riskAnalysis by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRiskAnalysisByIdAPI",
     *     @OA\Parameter(
     *         description="riskAnalysis id",
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
    public function showLimit(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $riskAnalysisData = RiskAnalysis::leftJoin('projects', 'risk_analysis.project_id','=', 'projects.id')
                ->leftJoin('departments','risk_analysis.department_id','=','departments.id')
                ->leftJoin('job_titles','risk_analysis.job_title_id','=','job_titles.id')
                ->where('risk_analysis.id', $id);
//            if ($checkRiskAnalysisPermission) {
//                $riskAnalysisData = $riskAnalysisData->with(['tasks' => function($query) {
//                    $query->with(['task_assignees']);
//                }, 'elements']);
//            } else {
//                $riskAnalysisData = $riskAnalysisData->with(['tasks' => function($query) use($user){
//                    $query->with(['task_assignees' => function($q) use($user){
//                        $q->where('user_id', $user['id']);
//                    }]);
//                }, 'elements']);
//            }

            $riskAnalysisData = $riskAnalysisData->with(['tasks' => function($query) {
                $query->with(['task_assignees']);
            }, 'elements'])
                ->select('risk_analysis.*','projects.name as project_name','departments.name as department_name','job_titles.name as job_title_name')
                ->first();
            if (empty($riskAnalysisData)) {
                return $this->responseException('Not found risk analysis', 404);
            }
            foreach ($riskAnalysisData->tasks as $key => $task) {
                if ($task->status == 1) {
                    $task->update(['status' => 2]);
                }
                $checkExistAssignee = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));
                if ($checkExistAssignee === false) {
                    unset($riskAnalysisData->tasks[$key]);
                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
            }
            $riskAnalysisData->editPermission = $user->editPermission;
            return $this->responseSuccess($riskAnalysisData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/riskAnalysis/{id}",
     *     tags={"RiskAnalysis"},
     *     summary="Update riskAnalysis API",
     *     description="Update riskAnalysis API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRiskAnalysisAPI",
     *     @OA\Parameter(
     *         description="riskAnalysis id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Risk Analysis schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RiskAnalysis")
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
            $riskAnalysisData = RiskAnalysis::where("id",$id)->first();
            if (empty($riskAnalysisData)) {
                return $this->responseException('Not found risk analysis', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk analysis',
                'objectItem' => $riskAnalysisData,
            ];
            // Process REPORT permission
            if (!$user = $this->getAuthorizedUser('risk analysis', 'process', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
//                $companyData = Company::find($user['company_id']);
//                $input['industry_id'] = $companyData['industry_id'];
//                $input['responsible_id'] = $user['id'];
//                $input['deadline'] = null;
//                $task = $input['tasks'];
//                $oldTasks = Task::where('type', '=', 'Risk analysis')
//                    ->where('type_id', $id)->pluck('id')->toArray();
//                $this->updateTaskByType('Risk analysis', $input, $oldTasks, $task, $user, $riskAnalysisData, null);
//
//                $validator = Validator::make($input, $rules);
//                if ($validator->fails()) {
//                    $errors = ValidateResponse::make($validator);
//                    return $this->responseError($errors, 400);
//                }
//                $riskAnalysisData->update($input);
//                return $this->responseSuccess($riskAnalysisData, 201);

                $input = $request -> all();

                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $input['start_time'] = strtotime($input['start_time']);
                } else {
                    $input['start_time'] = strtotime("today");
                }
                if (!empty($input['deadline'])) {
                    $input['deadline'] = strtotime($input['deadline']);
                } else {
                    $input['deadline'] = strtotime("today");
                }
                $input['recurring'] = 'indefinite';

                //change responsible person
                if (!empty($input['updateResponsible'])) {
                    if ($riskAnalysisData->responsible != $input['responsible']) {
                        $riskAnalysisData->update(['responsible' => $input['responsible']]);
                        $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'risk', 'Risk analysis', $riskAnalysisData['id'], $riskAnalysisData['name'], 'responsible');
                    }
                    $tasksOfRisk = Task::where('type', 'Risk analysis')->where('type_id', $riskAnalysisData->id)->get();
                    if (!empty($tasksOfRisk)) {
                        if ($tasksOfRisk[0]->responsible_id != $input['responsible_id']) {
                            foreach ($tasksOfRisk as $item) {
                                $item->update(['responsible_id' => $input['responsible_id']]);
                            }
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible_id']], 'task', 'Risk analysis', $riskAnalysisData['id'], $riskAnalysisData['name'], 'responsible');
                        }
                    }
                    return $this->responseSuccess($riskAnalysisData);
                }

                $rules = RiskAnalysis::$updateRules;
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                if ($riskAnalysisData->status < 3 || $riskAnalysisData->status == 6) {
                    $companyData = Company::where("id", $user['company_id'])->first();
                    $input['industry_id'] = $companyData['industry_id'];
                    // close previous task to reopen deviation action
                    if ($input['status'] == 4 || (!empty($input['reopen']) && $input['reopen'] == 1)) {
                        $tasks = Task::where('type', '=', 'Risk analysis')->where('type_id', $id)->get();
                        foreach ($tasks as $task) {
                            $task->update(['status' => 5]);
                        }
                    }
                    if ($input['status'] < 3 && (($riskAnalysisData->responsible == $input['responsible']) && !empty($input['tasks']))) {
                        $newTask = '';
                        foreach ($input['tasks'] as $task) {
                            $newTask = $this->addTasksByType($task, $input, $user['id'], $user['company_id'], 'Risk analysis', $riskAnalysisData['id']);
                        }
                        if ($newTask) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$newTask['responsible_id']], 'task', 'Risk analysis', $riskAnalysisData['id'], $riskAnalysisData['name'], 'responsible');
                        }
                    }
                    if ($input['status'] == 3 || $input['status'] == 4) {
                        if ($riskAnalysisData['deviation_id']) {
                            $deviation = Deviation::find($riskAnalysisData['deviation_id']);
                            if (!empty($deviation)) {
                                $deviation->update(['status' => 6]); //all action are done
                                $this->pushNotification($user['id'], $user['company_id'], 2, [$deviation['responsible']], 'deviation', 'Deviation', $riskAnalysisData['deviation_id'], $deviation['subject'], 'action_done');
                            }
                        } elseif ($riskAnalysisData['report_id']) {
                            $report = Report::find($riskAnalysisData['report_id']);
                            if (!empty($report)) {
                                $checklistInfo = json_decode($report->checklist_info);
                                $reportName = $checklistInfo->name;

                                $actionDone = $report['action_done'];
                                if (!$actionDone) {
                                    $actionDone = ['risk'];
                                } else {
                                    array_push($actionDone, 'risk');
                                    if (in_array('task', $actionDone)) {
                                        $data['status'] = 6; //all action are done
                                        $this->pushNotification($user['id'], $user['company_id'], 2, [$report['responsible']], 'report', 'Report', $riskAnalysisData['deviation_id'], $reportName, 'action_done');
                                    }
                                }
                                $data['action_done'] = $actionDone;
                                $report->update($data);
                            }
                        }
                    }
                }
                $riskAnalysisData->update($input);

                // update Security
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('risk-analysis', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('risk-analysis', $input, null);
                }

                return $this->responseSuccess($riskAnalysisData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $rules = RiskAnalysis::$updateRules;
            $input = $request -> all();

            $riskAnalysisData = RiskAnalysis::where("id",$id)->with(['deviation'])->first();
            if (empty($riskAnalysisData)) {
                return $this->responseException('Not found risk analysis', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk analysis',
                'objectItem' => $riskAnalysisData,
            ];

            if (!$user = $this->getAuthorizedUser('risk analysis', 'process', 'updateTask', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $riskAnalysisData->update($input);

                //Handle to update task
                $task = $input['tasks'];
                $oldTasks = Task::where('type', '=', 'Risk analysis')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                $this->processTaskByType('Risk analysis', $oldTasks, $task, $user);

//                foreach ($oldTasks as $oldTask) {
//                    $key = array_search($oldTask['id'], array_column($task, 'id'));
//                    if($key > -1){
//                        //update task
//                        $taskRules = Task::$updateRules;
//
//                        $taskData = Task::where("id", $oldTask -> id)->first();
//                        if (empty($taskData)) {
//                            return $this->responseException('Not found task', 404);
//                        }
//
//                        $taskValidator = Validator::make($task[$key], $taskRules);
//
//                        if ($taskValidator->fails()) {
//                            $errors = ValidateResponse::make($taskValidator);
//                            return $this->responseError($errors,400);
//                        }
//                        $taskData->update($task[$key]);
//                        $task[$key]['updated'] = true;
//
//                        $oldTaskAssignees = $oldTask->task_assignees;
//                        foreach ($oldTaskAssignees as $taskAssignee) {
//                            $keyTaskAssignee = array_search($taskAssignee['user_id'], $task[$key]['taskAssignees']);
//                            if ($keyTaskAssignee > -1) {
//                                $keyResponsible = array_search($taskAssignee['user_id'], $task[$key]['responsiblePerson']);
//                                $data = array();
//                                if ($keyResponsible > -1 && $user['id'] == $taskAssignee['user_id'] && $task[$key]['id'] == $taskAssignee['task_id']) {
//                                    if ($task[$key]['checkDone']) {
//                                        $status = 3;
//                                    } else {
//                                        $status = 2;
//                                    }
//                                    $data['status'] = $status;
//                                }
//                                $taskAssignee->update($data);
//                            }
//                        }
//                    }
//                }
                $getTaskByRiskAnalysis = Task::where('type', '=', 'Risk analysis')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                if (!empty($getTaskByRiskAnalysis)) {
                    foreach ($getTaskByRiskAnalysis as $task) {
                        if ($task['task_assignees']) {
                            foreach ($task['task_assignees'] as $assignee) {
                                if ($assignee->responsible == 1 && $assignee->status != 3) {
                                    return $this->responseSuccess($riskAnalysisData, 201);
                                }
                            }
                        }
                    }
                }
                $riskAnalysisData->update(['status' => 3]);
                if ($riskAnalysisData->deviation) {
                    $riskAnalysisData->deviation->update(['status' => 3]);
                }
                return $this->responseSuccess($riskAnalysisData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/riskAnalysis/{id}",
     *     tags={"RiskAnalysis"},
     *     summary="Delete riskAnalysis API",
     *     description="Delete riskAnalysis API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteRiskAnalysisAPI",
     *     @OA\Parameter(
     *         description="riskAnalysis id",
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
            $taskData = RiskAnalysis::where("id",$id)->first();
            if (empty($taskData)) {
                return $this->responseException('Not found task', 404);
            }
            RiskAnalysis::destroy($id);
            return $this->responseSuccess("Delete risk analysis success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
