<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Mail\FinishedTaskMail;
use App\Models\Deviation;
use App\Models\Document;
use App\Models\DocumentNew;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\SubGoal;
use App\Models\Report;
use App\Models\RiskAnalysis;
use App\Models\TaskAssignee;
use App\Models\User;
use App\Models\UserTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task APIs",
 * )
 **/
class TaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/tasks",
     *     tags={"Tasks"},
     *     summary="Get tasks",
     *     description="Get tasks list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTasks",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('task', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $getBy = $request->getBy;
                $result = Task::where('company_id', $user->company_id);

                if (!empty($request->startDate) && !empty($request->endDate) && $request->startDate == $request->endDate) {
                    $result = $result->whereDate('start_time','<=',  $request->startDate)
                        ->whereDate('deadline','>=',  $request->endDate);
                } else {
                    if (!empty($request->startDate)) {
                        $result = $result->whereDate('start_time','>=',  $request->startDate);
                    }
                    if (!empty($request->endDate)) {
                        $result = $result->whereDate('deadline','<=',  $request->endDate);
                    }
                }
                $result = $result->with(['task_assignees'])->get();
                $result = $this->filterViewList('task', $user, $user->filterBy, $result);
                return $this->filterTasks($result, $getBy, $user);
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/admin",
     *     tags={"Tasks"},
     *     summary="Get tasks",
     *     description="Get tasks list by admin",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTasks",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function indexAdmin(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $getBy = $request->getBy;
                $result = Task::where('company_id', $user->company_id)
                    ->with(['task_assignees'])
                    ->get();

                return $this->filterTasks($result, $getBy);
            }
        } catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function filterTasks($result, $getBy, $user = null) {
        if($result){
            $resultGetAddedBy = [];
            $countTaskOfGoal = 0;
            $countTaskOfDeviation = 0;
            $countTaskOfReport = 0;
            $countTaskOfRiskAnalysis = 0;
            $countTaskOfAttachment = 0;
            $countTaskOfUserTask = 0;
            $userTaskAssigneeArr = [];
           
            if (!empty($getBy) && $getBy == 'type') {
                $goalIdArr = [];
                $goalAssigneeArr = [];
                $subGoalIdArr = [];
                $subGoalAssigneeArr = [];
                $deviationIdArr = [];
                $deviationAssigneeArr = [];
                $reportIdArr = [];
                $reportAssigneeArr = [];
                $riskAnalysisIdArr = [];
                $riskAnalysisAssigneeArr = [];
                $attachmentIdArr = [];
                $attachmentAssigneeArr = [];
                $userTaskIdArr = [];
                foreach ($result as $task) {
                    if ($task->type == 'Goal') {
                        $countTaskOfGoal += 1;
                        $subGoal = SubGoal::find($task->type_id);
                        if(empty($subGoal)){
                            continue;
                        }
                        $goal = Goal::find($subGoal->main_goal_id);
                        if (in_array($goal->id, $goalIdArr)) {
                            $key = array_search($goal->id, array_column($goalAssigneeArr, 'type_main_id'));
                            if ($key > -1) {
                                $goalAssigneeArr[$key] = $this->updateAssigneeArr($goalAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            if ($goal->is_template == 0) {
                                array_push($goalIdArr, $goal->id);
                                array_push($resultGetAddedBy, $task);
                                $goalAssigneeItem = $this->initAssigneeItem($task, $user);
                                array_push($goalAssigneeArr, $goalAssigneeItem);
                            }
                        }
                    } elseif ($task->type == 'Sub goal') {
                        $subGoal = SubGoal::find($task->type_id);
                        if(empty($subGoal)){
                            continue;
                        }
                        $goal = Goal::find($subGoal->main_goal_id);
                        if (in_array($task->type_id, $subGoalIdArr)) {
                            $key = array_search($task->type_id, array_column($subGoalAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $subGoalAssigneeArr[$key] = $this->updateAssigneeArr($subGoalAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            if ($goal->is_template == 0) {
                                array_push($subGoalIdArr, $task->type_id);
                                array_push($resultGetAddedBy, $task);
                                $subGoalAssigneeItem = $this->initAssigneeItem($task, $user);
                                array_push($subGoalAssigneeArr, $subGoalAssigneeItem);
                            }
                        }
                    } elseif ($task->type == 'Deviation') {
                        $countTaskOfDeviation += 1;
                        if (in_array($task->type_id, $deviationIdArr)) {
                            $key = array_search($task->type_id, array_column($deviationAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $deviationAssigneeArr[$key] = $this->updateAssigneeArr($deviationAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            array_push($deviationIdArr, $task->type_id);
                            array_push($resultGetAddedBy, $task);
                            $deviationAssigneeItem = $this->initAssigneeItem($task, $user);
                            array_push($deviationAssigneeArr, $deviationAssigneeItem);
                        }
                    } elseif ($task->type == 'Report') {
                        $countTaskOfReport += 1;
                        if (in_array($task->type_id, $reportIdArr)) {
                            $key = array_search($task->type_id, array_column($reportAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $reportAssigneeArr[$key] = $this->updateAssigneeArr($reportAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            array_push($reportIdArr, $task->type_id);
                            array_push($resultGetAddedBy, $task);
                            $reportAssigneeItem = $this->initAssigneeItem($task, $user);
                            array_push($reportAssigneeArr, $reportAssigneeItem);
                        }
                    } elseif ($task->type == 'Risk analysis') {
                        $countTaskOfRiskAnalysis += 1;
                        if (in_array($task->type_id, $riskAnalysisIdArr)) {
                            $key = array_search($task->type_id, array_column($riskAnalysisAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $riskAnalysisAssigneeArr[$key] = $this->updateAssigneeArr($riskAnalysisAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            array_push($riskAnalysisIdArr, $task->type_id);
                            array_push($resultGetAddedBy, $task);
                            $riskAnalysisAssigneeItem = $this->initAssigneeItem($task, $user);
                            array_push($riskAnalysisAssigneeArr, $riskAnalysisAssigneeItem);
                        }
                    } elseif ($task->type == 'Attachment') { // type 'Attachment'
                        $countTaskOfAttachment += 1;
                        if (in_array($task->type_id, $attachmentIdArr)) {
                            $key = array_search($task->type_id, array_column($attachmentAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $attachmentAssigneeArr[$key] = $this->updateAssigneeArr($attachmentAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            array_push($attachmentIdArr, $task->type_id);
                            array_push($resultGetAddedBy, $task);
                            $attachmentAssigneeItem = $this->initAssigneeItem($task, $user);
                            array_push($attachmentAssigneeArr, $attachmentAssigneeItem);
                        }
                    } elseif ($task->type == 'User') {
                        $countTaskOfUserTask += 1;
                        if (in_array($task->type_id, $userTaskIdArr)) {
                            $key = array_search($task->type_id, array_column($userTaskAssigneeArr, 'type_id'));
                            if ($key > -1) {
                                $userTaskAssigneeArr[$key] = $this->updateAssigneeArr($userTaskAssigneeArr[$key], $task, $user);
                            }
                        } else {
                            array_push($userTaskIdArr, $task->type_id);
                            array_push($resultGetAddedBy, $task);
                            $userTaskAssigneeItem = $this->initAssigneeItem($task, $user);
                            array_push($userTaskAssigneeArr, $userTaskAssigneeItem);
                        }
                    }
                }
            } else {
                $resultGetAddedBy = $result;
            }
           
            if ($resultGetAddedBy) {
                foreach ($resultGetAddedBy as $task){
                    if ($task->type == 'Goal') {
//                        $goal = Goal::find($task->type_id);
                        $subGoal = SubGoal::find($task->type_id);
                        $goal = Goal::where('goals.id', $subGoal->main_goal_id)
                            ->leftJoin('categories', 'goals.category_id', 'categories.id')
                            ->select('goals.*', 'categories.name as categoryName')
                            ->first();
                        $task->count_task = $countTaskOfGoal;
                        if (empty($goal)) {
                            return $this->responseException('Not found goal', 404);
                        } else {
                            $this->getTaskItemInfo($task, $goal, $goalAssigneeArr);
                        }
                    } elseif ($task->type == 'Sub goal') {
                        $sub_goal = SubGoal::find($task->type_id);
                        if (empty($sub_goal)) {
                            return $this->responseException('Not found sub goal', 404);
                        } else {
                            $this->getTaskItemInfo($task, $sub_goal, $subGoalAssigneeArr);
                        }
                    } elseif ($task->type == 'Risk analysis') {
                        $risk_analysis = RiskAnalysis::find($task->type_id);
//                        $risk_analysis = RiskAnalysis::where('risk_analysis.id', $task->type_id)
//                            ->leftJoin('categories', 'risk_analysis.category_id', 'categories.id')
//                            ->select('risk_analysis.*', 'categories.name as categoryName')
//                            ->first();
                        $task->count_task = $countTaskOfRiskAnalysis;
                        if (empty($risk_analysis)) {
                            return $this->responseException('Not found risk analysis', 404);
                        } else {
                            $this->getTaskItemInfo($task, $risk_analysis, $riskAnalysisAssigneeArr);
                        }
                    } elseif ($task->type == 'Deviation') {
                        $task->count_task = $countTaskOfDeviation;
                        $deviation = Deviation::where('deviations.id', $task->type_id)
                            ->leftJoin('categories', 'deviations.category_id', 'categories.id')
                            ->select('deviations.*', 'categories.name as categoryName')
                            ->first();
                        if (empty($deviation)) {
                            return $this->responseException('Not found deviation task', 404);
                        } else {
                            $this->getTaskItemInfo($task, $deviation, $deviationAssigneeArr);
                        }
                    } elseif ($task->type == 'Report') {
                        $report_task = Report::where('reports.id', $task->type_id)
                            ->leftJoin('categories', 'reports.category_id', 'categories.id')
                            ->select('reports.*', 'categories.name as categoryName')
                            ->first();
                        $task->count_task = $countTaskOfReport;
                        if (empty($report_task)) {
                            return $this->responseException('Not found report task', 404);
                        } else {
                            $this->getTaskItemInfo($task, $report_task, $reportAssigneeArr);
                        }

//                        $task->remaining_time = '';
//                        if ($task->deadline) {
//                            $task->remaining_time = $this->calRemainingTime($task->deadline);
//                        }

//                        $answers = json_decode($task->report_task->answer);
//                        $checklistInfo = json_decode($task->report_task->checklist_info);
//                        $topics = $checklistInfo->topics;
//                        $keyAnswer = array_search($task->id, array_column($answers, 'task_id'));
//                        if ($keyAnswer > -1) {
//                            $keyTopic = array_search($answers[$keyAnswer]->topic_id, array_column($topics, 'id'));
//                            if ($keyTopic > -1) {
//                                $keyQuestion = array_search($answers[$keyAnswer]->question_id, array_column($topics[$keyTopic]->questions, 'id'));
//                                if ($keyQuestion > -1) {
//                                    $task->checkpointName = $topics[$keyTopic]->questions[$keyQuestion]->name;
//                                }
//                            }
//                        }
                    } elseif ($task->type == 'Document') {
                        $task->count_task = $countTaskOfAttachment;
                        $document = DocumentNew::where('documents_new.id', $task->type_id)
                            ->leftJoin('categories', 'documents_new.category_id', 'categories.id')
                            ->select('documents_new.*', 'categories.name as categoryName')
                            ->first();
                        if (empty($document)) {
                            return $this->responseException('Not found document', 404);
                        } else {
                            $this->getTaskItemInfo($task, $document, $attachmentAssigneeArr);
                        }
                    } elseif ($task->type == 'User') {
                        $task->count_task = $countTaskOfUserTask;
                        $user_task = UserTask::where('user_tasks.id', $task->type_id)
                            ->leftJoin('categories', 'user_tasks.category_id', 'categories.id')
                            ->select('user_tasks.*', 'categories.name as categoryName')
                            ->first();
                        if (empty($user_task)) {
                            return $this->responseException('Not found user task', 404);
                        } else {
                            $this->getTaskItemInfo($task, $user_task, $userTaskAssigneeArr);
                            $task->count_task = Task::where('type', 'User')
                                ->where('type_id', $task->type_id)->count();
                        }
                    }
                    if ($task['start_time']) {
                        $task['start_time'] = date("Y-m-d", $task['start_time']);
                    }
                    if ($task['deadline']) {
                        $task['deadline'] = date("Y-m-d", $task['deadline']);
                        $task['deadlineByType'] = $task['deadline'];
                    }

                    $task->remainingTime = $this->calRemainingTime($task->deadlineByType, 'day');
                    $addedByUser = User::find($task->added_by);
                    $task->added_by_name = $addedByUser['first_name'] . " " . $addedByUser['last_name'];
                }
            }

            $userTasks = UserTask::leftJoin('categories', 'user_tasks.category_id', 'categories.id')
                ->where('user_tasks.added_by', $user->id)
                ->orWhere('user_tasks.responsible_id', $user->id)
                ->select('user_tasks.*', 'categories.name as categoryName')
                ->get();

            foreach ($userTasks as $item) {
                $key = array_search($item['id'], array_column($resultGetAddedBy, 'type_id'));
                if ($key > -1 && $resultGetAddedBy[$key]->type == 'User') {
                    continue;
                } else {
                    $item->type = 'User';
                    $item->type_id = $item['id'];
                    $item->count_task = 0;
                    if ($item['start_time']) {
                        $item['start_time'] = date("Y-m-d", $item['start_time']);
                    }
                    if ($item['deadline']) {
                        $item['deadline'] = date("Y-m-d", $item['deadline']);
                        $item['deadlineByType'] = $item['deadline'];
                    }

                    $item->remainingTime = $this->calRemainingTime($item['deadlineByType'], 'day');
                    $addedByUser = User::find($item->added_by);
                    $item->added_by_name = $addedByUser['first_name'] . " " . $addedByUser['last_name'];
                    $item->rate = 0;
                    array_unshift($resultGetAddedBy, $item);
                }
            }

            return $this->responseSuccess($resultGetAddedBy);
        } else {
            return $this->responseSuccess([]);
        }
    }

    public function getTaskItemInfo($task, $infoFromType, $assigneeArr) {
        if ($task->type == 'Deviation') {
            $task->name = $infoFromType->subject;
            $task->added_by = $infoFromType->added_by;
        } else if ($task->type == 'Report') {
            $reportInfo = json_decode($infoFromType->checklist_info);
            $task->name = $reportInfo->name;
            $task->added_by = $infoFromType->added_by;
        } else if ($task->type == 'Sub goal') {
            $goal = Goal::find($infoFromType->main_goal_id);
            $task->name = $infoFromType->name;
            $task->added_by = $goal->added_by;
        } else {
            $task->name = $infoFromType->name;
            $task->added_by = $infoFromType->added_by;
        }
        if ($task->type != 'Document') {
            $task->status = $infoFromType->status;
        }
        if ($task->type != 'Risk analysis') {
            $task->categoryName = $infoFromType->categoryName;
        } else {
            $task->categoryName = '';
        }
        $key = array_search($task->type_id, array_column($assigneeArr, 'type_id'));
        $task->rate = $assigneeArr[$key]['rate'];
        $task->deadlineByType = $assigneeArr[$key]['deadlineByType'];
        $task->filterBy = $assigneeArr[$key]['filterBy'];
        return $task;
    }

    public function initAssigneeItem ($task, $user) {
        $assigneeItem = [];
        $assigneeItem['type_id'] = $task->type_id;
        $assigneeItem['totalTask'] = 0;
        $assigneeItem['doneTask'] = 0;
        $assigneeItem['processingTask'] = 0;
        $assigneeItem['newTask'] = 0;
        $assigneeItem['rate'] = 0;
        $assigneeItem['filterBy'] = '';
        if ($task->deadline) {
            $assigneeItem['deadlineByType'] = $task->deadline;
        } else {
            $assigneeItem['deadlineByType'] = '';
        }
        if ($task->type_main_id) {
            $assigneeItem['type_main_id'] = $task->type_main_id;
        } else {
            $assigneeItem['type_main_id'] = 0;
        }
        $totalTask = count($task->task_assignees);
        if ($totalTask > 0) {
            $assigneeItem = $this->calRateItem($task, $assigneeItem, $user);
        }
        return $assigneeItem;
    }

    public function updateAssigneeArr($assigneeItemKey, $task, $user) {
        if ($task->deadline) {
            if ($assigneeItemKey['deadlineByType']) {
                if (strtotime($task->deadline) < strtotime($assigneeItemKey['deadlineByType'])) {
                    $assigneeItemKey['deadlineByType'] = $task->deadline;
                }
            } else {
                $assigneeItemKey['deadlineByType'] = $task->deadline;
            }
        }
        $totalTask = count($task->task_assignees);
        if ($totalTask > 0) {
            $assigneeItemKey = $this->calRateItem($task, $assigneeItemKey, $user);
        }
        return $assigneeItemKey;
    }

    public function calRateItem($task, $assigneeItem, $user) {
        $checkAssigneeRole = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));

        if ($task->added_by == $user['id'] || $user['filterBy'] == 'company admin') {
            $userTaskRole = 'creator';
        } elseif ($task->responsible_id == $user['id']) {
//            if ($checkAssigneeRole > -1) {
//                $userTaskRole = 'assignee';
//            } else {
                $userTaskRole = 'responsible';
//            }
        } elseif ($checkAssigneeRole > -1) {
            $userTaskRole = 'assignee';
        } else {
            $userTaskRole = '';
        }

        if ($userTaskRole == 'creator' || $userTaskRole == 'responsible') {
            $assigneeItem = $this->calRateByRole($task, $assigneeItem);
        } elseif ($userTaskRole == 'assignee') {
            $assigneeItem = $this->calRateByRole($task, $assigneeItem, $user['id']);
        }

        return $assigneeItem;
    }

    public function calRateByRole($task, $assigneeItem, $userId = null) {
        $taskAssigneesArr = $task->task_assignees->toArray();
        if ($task->status == 3 || $task->status == 4 || $task->status == 5 || $task->status == 6) {
            if ($userId) {
                $assigneeItem['totalTask'] += array_count_values(array_column($taskAssigneesArr, 'user_id'))[$userId];
                $assigneeItem['doneTask'] += array_count_values(array_column($taskAssigneesArr, 'user_id'))[$userId];
            } else {
                $assigneeItem['totalTask'] += count($task->task_assignees);
                $assigneeItem['doneTask'] += count($task->task_assignees);
            }
        } else {
            foreach ($taskAssigneesArr as $assignee) {
                if (!$userId || $userId == $assignee['user_id']) {
                    $assigneeItem['totalTask'] += 1;
                    if ($assignee['status'] == 3) { //done
                        $assigneeItem['doneTask'] += 1;
                    } elseif ($assignee['status'] == 2) { //ongoing
                        $assigneeItem['processingTask'] += 1;
                    } else { //new
                        $assigneeItem['newTask'] += 1;
                    }
//                    if (empty($assigneeItem['filterBy']) && $assignee->user_id == $user['id']) {
//                        $assigneeItem['filterBy'] = 'user';
//                    }
                }
            }
        }
//        old logic:
//        $assigneeItem['rate'] = $assigneeItem['doneTask'] / $assigneeItem['totalTask'] * 100;

//        new logic:
        $inProgressRate = ($assigneeItem['processingTask'] / $assigneeItem['totalTask']) * 0.5 * 100;
        $doneRate = $assigneeItem['doneTask'] / $assigneeItem['totalTask'] * 100;
        $assigneeItem['rate'] = $doneRate + $inProgressRate;


        return $assigneeItem;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tasks",
     *     tags={"Tasks"},
     *     summary="Create new task",
     *     description="Create new task",
     *     security={{"bearerAuth":{}}},
     *     operationId="createTask",
     *     @OA\RequestBody(
     *         description="Task schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Task $task)
    {
        try {
            if (!$user = $this->getAuthorizedUser('task', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = Task::$rules;
                $input = $request -> all();
                if (empty($input['industry_id'])) {
                    $input['industry_id'] = $user['company']['industry_id'];
                }
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user['company_id'];

                $validator = Validator::make($input, $rules);

                
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newTask = Task::create($input);

                $this->createTaskAssignee($input['taskAssignees'], $input['employee_array'], $user['id'], $newTask->id, $user['company_id']);

                return $this->responseSuccess($newTask);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function createTaskAssignee($taskAssignees, $responsiblePerson, $user_id, $task_id, $company_id) {
        if (empty($taskAssignees) || empty($responsiblePerson) || empty($user_id) || empty($task_id) || empty($company_id)) return false;
        else {
            $rulesTaskAssignee = TaskAssignee::$rules;
            foreach ($taskAssignees as $assignee) {
                $inputTaskAssignee['company_id'] = $company_id;
                $inputTaskAssignee['task_id'] = $task_id;
                $inputTaskAssignee['user_id'] = $assignee;
//                if ($newTask->responsible_type == 1) { //responsible_type == 1: All
//                    $inputTaskAssignee['responsible'] = 1;
//                } else {
//                    if (in_array($assignee, $responsiblePerson)) {
//                        $inputTaskAssignee['responsible'] = 1;
//                    }
//                }

                if (in_array($assignee, $responsiblePerson)) {
                    $inputTaskAssignee['responsible'] = 1;
                } else {
                    $inputTaskAssignee['responsible'] = 0;
                }

                $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);

                if ($validatorTaskAssignee->fails()) {
                    $errors = ValidateResponse::make($validatorTaskAssignee);
                    return $this->responseError($errors,400);
                }
                TaskAssignee::create($inputTaskAssignee);

//                        $email = $newTaskAssignee->user->email;
//                        $data = array(
//                            'name' => $newTaskAssignee->user->first_name . ' ' . $newTaskAssignee->user->last_name,
//                            'assigned_by' => $user->first_name . ' ' . $user->last_name,
//                            'deadline' => $newTask->deadline,
//                            'url' => config('app.site_url') . '/employee/tasks',
//                        );
//
//                        Mail::to($email)->send(new AssignedTaskMail($data));
            }
            $this->pushNotification($user_id, $company_id, 2, $taskAssignees, 'task');
            return true;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Get task by id",
     *     description="Get task by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTaskByIdAPI",
     *     @OA\Parameter(
     *         description="task id",
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
            $taskData = Task::where("id",$id)
                ->with(['task_assignees'])
                ->first();
            if (empty($taskData)) {
                return $this->responseException('Not found task', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'task',
                'objectItem' => $taskData,
            ];
            if (!$user = $this->getAuthorizedUser('task', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $taskData->editPermission = $user->editPermission;

                return $this->responseSuccess($taskData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Update task API",
     *     description="Update task API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateTaskAPI",
     *     @OA\Parameter(
     *         description="task id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Task schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
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
//            $taskData = Task::where("id", $id)->with(['task_assignees'])->first();
            $taskData = Task::find($id);
            if (empty($taskData)) {
                return $this->responseException('Not found task', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'task',
                'objectItem' => $taskData,
            ];
            if (!$user = $this->getAuthorizedUser('task', 'process', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
//                $rules = Task::$updateRules;
                $input = $request->all();

//                $validator = Validator::make($input, $rules);
//
//                if ($validator->fails()) {
//                    $errors = ValidateResponse::make($validator);
//                    return $this->responseError($errors, 400);
//                }

                if ($input['requestNewStatus'] && ($input['requestNewStatus'] == 3 || $input['requestNewStatus'] == 4)) {
                    $inputUpdate['status'] = $input['requestNewStatus'];
                    $inputUpdate['completed_time'] = Carbon::now()->format('Y-m-d H:i:s');
                    $inputUpdate['completed_by'] = $user['id'];
                    if (!empty($input['description'])) {
                        $inputUpdate['description'] = $input['description'];
                    }

                    $taskData->update($inputUpdate);

                    $tasks = Task::where('type', $taskData['type'])
                        ->where('type_id', $id)
                        ->whereIn('status', [1,2])->get();
                    if (count($tasks) == 0) {
                        if ($taskData['type'] == 'Risk analysis') {
                            $risk = RiskAnalysis::find($taskData['type_id']);
                            if (!empty($risk)) {
                                $risk->update(['status' => 6]); //all action are done
                                $this->pushNotification($user['id'], $user['company_id'], 2, [$risk['responsible']], 'risk', $taskData['type'], $taskData['type_id'], $risk['name'], 'action_done');
                            }
                        } elseif ($taskData['type'] == 'Deviation') {
                            $deviation = Deviation::find($taskData['type_id']);
                            if (!empty($deviation)) {
                                $deviation->update(['status' => 6]); //all action are done
                                $this->pushNotification($user['id'], $user['company_id'], 2, [$deviation['responsible']], 'deviation', $taskData['type'], $taskData['type_id'], $deviation['subject'], 'action_done');
                            }
                        } elseif ($taskData['type'] == 'Report') {
                            $report = Report::find($taskData['type_id']);
                            if (!empty($report)) {
                                $checklistInfo = json_decode($report['checklist_info']);
                                $reportName = $checklistInfo->name;

                                $actionDone = $report['action_done'];
                                if (!$actionDone) {
                                    $actionDone = ['task'];
                                } else {
                                    array_push($actionDone, 'task');
                                    if (in_array('risk', $actionDone)) {
                                        $data['status'] = 6; //all action are done
                                        $this->pushNotification($user['id'], $user['company_id'], 2, [$report['responsible']], 'report', $taskData['type'], $taskData['type_id'], $reportName, 'action_done');
                                    }
                                }
                                $data['action_done'] = $actionDone;
                                $report->update($data);
                            }
                        }
                    }
                }

                return $this->responseSuccess($taskData);
//                if ($input['requestNewStatus'] == 3) {

//                    $email = $taskData->user_added_by->email;
//
//                    $data = array(
//                        'name' => $taskData->user_added_by->first_name . ' ' . $taskData->user_added_by->last_name,
//                        'assignee' => $user->first_name . ' ' . $user->last_name,
//                        'deadline' => $taskData->deadline,
//                        'url' => config('app.site_url') . '/employee/tasks',
//                    );
//
//                    Mail::to($email)->send(new FinishedTaskMail($data));
//                }

//                $taskData->update($input);

//                $oldTaskAssignees = $taskData->task_assignees;
//                foreach ($oldTaskAssignees as $taskAssignee) {
//                    $keyTaskAssignee = array_search($taskAssignee['user_id'], $input['taskAssignees']);
//                    if ($keyTaskAssignee > -1) {
//                        $keyResponsible = array_search($taskAssignee['user_id'], $input['responsiblePerson']);
//                        $data = array();
//                        if ($keyResponsible > -1) {
//                            $responsible = 1;
//                            $data['status'] = $this->updateTaskStatus($user['id'], $taskAssignee, $input);
////                            if ($user['id'] == $taskAssignee['user_id'] && $input['id'] == $taskAssignee['task_id']) {
////                                if (!empty($input['checkDone']) && $input['checkDone']) {
////                                    $status = 3;
////                                } else {
////                                    $status = 2;
////                                }
////                                $data['status'] = $status;
////                            }
//                        } else {
//                            $responsible = null;
//                        }
//                        if ($taskAssignee['user_id'] != $responsible) {
//                            $data['responsible'] = $responsible;
//
//                        }
//                        $taskAssignee->update($data);
//                    } else {
//                        TaskAssignee::destroy($taskAssignee->id);
//                    }
//                    unset($input['taskAssignees'][$keyTaskAssignee]);
//                }
//                foreach ($input['taskAssignees'] as $newAssignee) {
//                    $this->createNewAssignee($newAssignee, $user['company_id'], $input['id'], $input['responsiblePerson']);
//                    $inputTaskAssignee['company_id'] = $user['company_id'];
//                    $inputTaskAssignee['task_id'] = $input['id'];
//                    $inputTaskAssignee['user_id'] = $newAssignee;
//                    $inputTaskAssignee['responsible'] = null;
//                    if (in_array($newAssignee, $input['responsiblePerson'])) {
//                        $inputTaskAssignee['responsible'] = 1;
//                    }
//
//                    $rulesTaskAssignee = TaskAssignee::$rules;
//                    $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);
//
//                    if ($validatorTaskAssignee->fails()) {
//                        $errors = ValidateResponse::make($validatorTaskAssignee);
//                        return $this->responseError($errors,400);
//                    }
//                    $newTaskAssignee = TaskAssignee::create($inputTaskAssignee);
//                }

//                if (!empty($input['type_id'])) { // update deviation info
//                    $deviationRules = Deviation::$updateRules;
//                    $deviationData = Deviation::where("id", $input['type_id'])->first();
//                    $deviation['responsible'] = $input['assignee'];
//                    $deviation['deadline'] = $input['deadline'];
//                    if (empty($deviationData)) {
//                        return $this->responseException('Not found deviation', 404);
//                    }
//
//                    $deviationValidator = Validator::make($deviation, $deviationRules);
//
//                    if ($deviationValidator->fails()) {
//                        $errors = ValidateResponse::make($deviationValidator);
//                        return $this->responseError($errors, 400);
//                    }
//                    $deviationData->update($deviation);
//                } else if (!empty($input['report_id']) && $input['status'] == 3) {
//                    $reportData = Report::where("id", $input['report_id'])->first();
//                    if (empty($reportData)) {
//                        return $this->responseException('Not found report', 404);
//                    }
//                    $answers = json_decode($reportData->answer);
//                    $checkReportStatus = true;
//                    foreach ($answers as $answer) {
//                        if ($answer->action == 'task' && $answer->task_id == $id) {
//                            $answer->checkAnswerYes = true;
//                        }
//                        if (!$answer->checkAnswerYes) {
//                            $checkReportStatus = false;
//                        }
//                    }
//                    $reportData->answer = json_encode($answers);
//                    $data = array(
//                        'answer' => $reportData->answer,
//                    );
//                    if ($checkReportStatus) {
//                        $data['status'] = 'Closed';
//                    }
//                    $reportData->update($data);
//                } else if (!empty($input['risk_analysis_id']) && $input['status'] == 3) {
//                    $riskAnalysisData = RiskAnalysis::where("id", $input['risk_analysis_id'])->first();
//                    if (empty($riskAnalysisData)) {
//                        return $this->responseException('Not found risk analysis', 404);
//                    }
//
//                    $riskAnalysisData->update(array('status' => 'Done'));
//                }
//                return $this->responseSuccess($taskData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateProgressOfTask(Request $request, $id)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $input = $request -> all();

                $taskData = Task::where("id",$id)->with(['task_assignees'])->first();
                if (empty($taskData)) {
                    return $this->responseException('Not found task', 404);
                }

                //Handle to update task
                $task = $input['tasks'];

                if (empty($taskData)) {
                    return $this->responseException('Not found task', 404);
                }

                $key = array_search($id, array_column($task, 'id'));
                if ($key > -1) {
                    $oldTaskAssignees = $taskData->task_assignees;
                    foreach ($oldTaskAssignees as $taskAssignee) {
                        $keyTaskAssignee = array_search($taskAssignee['user_id'], $task[$key]['taskAssignees']);
                        if ($keyTaskAssignee > -1) {
                            $keyResponsible = array_search($taskAssignee['user_id'], $task[$key]['responsiblePerson']);
                            $data = array();
                            if ($keyResponsible > -1 && $user['id'] == $taskAssignee['user_id'] && $task[$key]['id'] == $taskAssignee['task_id']) {
                                if ($task[$key]['checkDone']) {
                                    $status = 3;
                                } else {
                                    $status = 2;
                                }
                                $data['status'] = $status;
                            }
                            $taskAssignee->update($data);
                        }
                    }
                }

                return $this->responseSuccess($taskData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Delete task API",
     *     description="Delete task API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteTaskAPI",
     *     @OA\Parameter(
     *         description="task id",
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
            $taskData = Task::where("id",$id)->first();
            if (empty($taskData)) {
                return $this->responseException('Not found task', 404);
            }
            Task::destroy($id);
            return $this->responseSuccess("Delete task success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
