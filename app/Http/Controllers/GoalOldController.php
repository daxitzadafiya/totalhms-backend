<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\SubGoal;
use App\Models\TaskAssignee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Goals",
 *     description="Goal APIs",
 * )
 **/
class GoalOldController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/goals",
     *     tags={"Goals"},
     *     summary="Get goals",
     *     description="Get goals list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getGoals",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('goal', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $getByProjectID = $request->getByProjectID;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = Goal::leftJoin('projects', 'goals.project_id', '=', 'projects.id')
                    -> leftJoin('departments', 'goals.department_id', '=', 'departments.id')
                    -> join('users', 'goals.added_by', '=', 'users.id')
                    -> leftJoin('categories', 'goals.category_id', '=', 'categories.id')
                    -> where('goals.delete_status', 0);
                if (!empty($request->startDate) && !empty($request->endDate) && $request->startDate == $request->endDate) {
                    $result = $result->whereDate('goals.start_time','<=',  $request->startDate)
                        ->whereDate('goals.deadline','>=',  $request->endDate);
                } else {
                    if (!empty($request->startDate)) {
                        $result = $result->whereDate('goals.start_time','>=',  $request->startDate);
                    }
                    if (!empty($request->endDate)) {
                        $result = $result->whereDate('goals.deadline','<=',  $request->endDate);
                    }
                }
                $result = $result-> where (function ($q) use ($user, $getByProjectID) {
                        if ($user->role_id > 1) {
                            $q->whereRaw('FIND_IN_SET(?, goals.industry_id)', [$user['company']['industry_id']])
                                ->where(function ($query) use ($user) {
                                    $query->where('goals.company_id', $user['company_id'])
                                        ->orWhere('goals.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q-> where('goals.added_by', 1);
                        }
                        if ($getByProjectID) {
                            $q->where('project_id', $getByProjectID);
                        }
                    })
                    -> select('goals.*','projects.name as project_name','departments.name as department_name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name')
                    -> get();
                if($result){
                    $result = $this->filterViewList('goal', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $item) {
                        // responsible person
                        if ($item['responsible_id']) {
                            $responsible = User::find($item['responsible_id']);
                            $item['responsible_id_name'] = $responsible['first_name'] . ' ' . $responsible['last_name'];
                        }

                        // start time
                        if (!empty($item['start_time'])) {
                            $item['start_time'] = date("Y-m-d", $item['start_time']);
                        }

                        // due date
                        if (!empty($item['deadline'])) {
                            $item['deadline'] = date("Y-m-d", $item['deadline']);
                        }

                        $item->count_related_object = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = Goal::where('parent_id', $item['id'])->count();

                            if ($countRelatedObject > 0) {
                                $item->count_related_object = $countRelatedObject;
                            }
                        } else {
                            $tasks = Task::where('type_main_id', $item->id)
                                ->with(['task_assignees'])
                                ->get();

                            $assigneeItem = [];
                            $assigneeItem['totalTask'] = 0;
                            $assigneeItem['doneTask'] = 0;
                            $assigneeItem['processingTask'] = 0;
                            $assigneeItem['newTask'] = 0;
                            $assigneeItem['rate'] = 0;

                            foreach ($tasks as $task) {
                                $totalTask = count($task->task_assignees);
                                if ($totalTask > 0) {
                                    $assigneeItem = $this->calRateItem($task, $assigneeItem, $user);
                                }
                            }
                            $item->rate = $assigneeItem['rate'];
                        }

                        // count sub-goal
                        $item->count_sub_goal = SubGoal::where('main_goal_id', $item['id'])->count();
                    }
//                    $this->calculateProgressRateByType($result);
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function calRateItem($task, $assigneeItem, $user) {
        $checkAssigneeRole = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));

        if ($task->added_by == $user['id'] || $user['filterBy'] == 'company admin') {
            $userTaskRole = 'creator';
        } elseif ($task->resposible_id == $user['id']) {
            if ($checkAssigneeRole > -1) {
                $userTaskRole = 'assignee';
            } else {
                $userTaskRole = 'responsible';
            }
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
                    if ($assignee['status'] == 3) {
                        $assigneeItem['doneTask'] += 1;
                    } elseif ($assignee['status'] == 2) {
                        $assigneeItem['processingTask'] += 1;
                    } else {
                        $assigneeItem['newTask'] += 1;
                    }
//                    if (empty($assigneeItem['filterBy']) && $assignee->user_id == $user['id']) {
//                        $assigneeItem['filterBy'] = 'user';
//                    }
                }
            }
        }
        $assigneeItem['rate'] = $assigneeItem['doneTask'] / $assigneeItem['totalTask'] * 100;

        return $assigneeItem;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/goals",
     *     tags={"Goals"},
     *     summary="Create new goal",
     *     description="Create new goal",
     *     security={{"bearerAuth":{}}},
     *     operationId="createGoal",
     *     @OA\RequestBody(
     *         description="Goal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Goal")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Goal $goal)
    {
        try {
            $input = $request->all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $rules = Goal::$rules;
                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $input['start_time'] = strtotime($input['start_time']);
                } else {
                    $input['start_time'] = strtotime("today");
                }
                if (!$input['is_activated']) {
                    $input['deadline'] = null;
                    $input['recurring'] = 'indefinite';
                } else {
                    if (!empty($input['deadline'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    } else {
                        $input['deadline'] = null;
                        $input['recurring'] = 'indefinite';
                    }
                }

                $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', 'goal', $input['name']));

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newGoal = Goal::create($input);
                if ($newGoal && $user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Goal', $newGoal['id'], $newGoal['name'],'create');
                }

                $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible_id']], 'goal', 'Goal', $newGoal['id'], $newGoal['name'], 'responsible');

                // create Sub-goal
                $subGoals = $input['sub_goals'];
                if (!empty($subGoals)) {
                    foreach ($subGoals as $item) {
                        $this->addGoalTask($newGoal, $item, $input, $user['id'], $user['company_id']);
                    }
                }

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newGoal, $input);

                return $this->responseSuccess($newGoal);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Get goal by id",
     *     description="Get goal by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getGoalByIdAPI",
     *     @OA\Parameter(
     *         description="goal id",
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
    public function show(Request $request, $id) {
        try {
            $goalData = Goal::leftJoin('projects', 'goals.project_id', '=', 'projects.id')
                ->leftJoin('departments', 'goals.department_id', '=', 'departments.id')
                ->leftJoin('job_titles', 'goals.job_title_id', '=', 'job_titles.id')
                ->where('goals.id', $id)
                ->with(['sub_goals' => function ($query) {
                    $query->with(['tasks' => function ($q) {
                        $q->with(['task_assignees']);
                    }]);
                }])
                ->select('goals.*', 'projects.name as project_name', 'departments.name as department_name', 'job_titles.name as job_title_name')
                ->first();
            if (empty($goalData)) {
                return $this->responseException('Not found goal', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'goal',
                'objectItem' => $goalData,
            ];
            if (!$user = $this->getAuthorizedUser('goal', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $updateHistory = json_decode($goalData['update_history']);

                $goalHistory = $this->getUpdateHistory($updateHistory);

                foreach ($goalData->sub_goals as $key => $subGoals) {
                    foreach ($subGoals->tasks as $keyTask => $task) {
                        if ($task->added_by > 1 && $task->status == 1) {
                            $task->update(['status' => 2]);
                        }
                        $task->remaining_time = '';
                        if ($task->deadline) {
                            $task->remaining_time = $this->calRemainingTime($task->deadline);
                        }
//                        $goalData->responsible_id = $task->responsible_id;
//                        $goalData->deadline = $task->deadline;

                        $taskUpdateHistory = json_decode($task['update_history']);

//                        return $this->responseSuccess($taskUpdateHistory, 201);
                        $taskHistory = $this->getUpdateHistory($taskUpdateHistory);

                        $goalHistory = array_merge($goalHistory, $taskHistory);
                    }
                }

                $goalData->count_related_object = 0;
                $goalData->related_objects = '';
                if ($goalData['is_template']) {
                    $relatedObject = Goal::leftJoin('users', 'goals.added_by','=', 'users.id')
                        ->leftJoin('companies', 'goals.company_id','=', 'companies.id')
                        ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('goals.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select('goals.id', 'goals.name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'companies.name as company_name')
                        ->get();

                    if (count($relatedObject) > 0) {
                        $goalData->count_related_object = count($relatedObject);
                        $goalData->related_objects = $relatedObject;
                    }
                }
                $goalData->editPermission = $user->editPermission;
                $goalData->history = $goalHistory;

                // get Security information
                $this->getSecurityObject('goal', $goalData);
                // get Reminder / start date - due date information
                $goalData = $this->getReminderObject($goalData);

                return $this->responseSuccess($goalData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Update goal API",
     *     description="Update goal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateGoalAPI",
     *     @OA\Parameter(
     *         description="goal id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Goal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Goal")
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
            $rules = Goal::$updateRules;
            $input = $request->all();
            $resetTask = false;
            if ($input['requestEdit']) {
                $resetTask = true;
            }

            $goalData = Goal::find($id);
            if (empty($goalData)) {
                return $this->responseException('Not found goal', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'goal',
                'objectItem' => $goalData,
            ];

            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($goalData->is_template) {
//                    unset($input['department_id']);
//                    unset($input['project_id']);
//                    unset($input['job_title_id']);
                    $input['department_id'] = null;
                    $input['project_id'] = null;
                    $input['job_title_id'] = null;
                }

                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $input['start_time'] = strtotime($input['start_time']);
                } else {
                    $input['start_time'] = strtotime("today");
                }
                if (!$input['is_activated']) {
                    $input['deadline'] = null;
                    $input['recurring'] = 'indefinite';
                } else {
                    if (!empty($input['deadline'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    } else {
                        $input['deadline'] = null;
                        $input['recurring'] = 'indefinite';
                    }
                }

                $historyArray = json_decode($goalData->update_history);

                if ($goalData['name'] != $input['name']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'name', 'name', $goalData['name'], $input['name']);
                }
                if ($goalData['start_time'] != $input['start_time']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'start_time', 'start date', $goalData['start_time'], $input['start_time']);
                }
                if ($goalData['deadline'] != $input['deadline']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'deadline', 'due date', $goalData['deadline'], $input['deadline']);
                }
                if ($goalData['responsible_id'] != $input['responsible_id']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'responsible_id', 'responsible person', $goalData['responsible_id'], $input['responsible_id']);
                }
                if ($goalData['is_public'] != $input['is_public']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'is_public', 'share with company', $goalData['is_public'], $input['is_public']);
                }
                if ($goalData['job_title_id'] != $input['job_title_id']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'job_title_id', 'connect to job title', $goalData['job_title_id'], $input['job_title_id']);
                }
                if ($goalData['department_id'] != $input['department_id']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'department_id', 'connect to department', $goalData['department_id'], $input['department_id']);
                }
                if ($goalData['description'] != $input['description']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'description', 'description', $goalData['description'], $input['description']);
                }

                // update SUB-GOAL
                $subGoals = $input['sub_goals'];
                $oldSubGoals = SubGoal::where('main_goal_id', $goalData->id)->pluck('id')->toArray();
                $subGoalDiff = array_diff($oldSubGoals, array_column($subGoals, 'id'));
//                return $this->responseSuccess($subGoalDiff, 201);
                //delete SUB-GOAL
//                SubGoal::whereIn("id", $subGoalDiff)->delete();
                $getSubGoalDiff = SubGoal::whereIn("id", $subGoalDiff)->get();
                if (!empty($getSubGoalDiff)) {
                    foreach ($getSubGoalDiff as $item) {
                        //update history
                        $historyArray = $this->setUpdateHistory('deleted', $user['id'], $historyArray, 'name', 'sub-goal', $item['name']);
                        //delete task of sub-goal
                        Task::where('type', 'Goal')->where("type_id", $item['id'])->delete();
                        //delete sub goal
                        $item->delete();
                    }
                }

                foreach ($subGoals as $subGoal) {
                    if (isset($subGoal['id'])) {
                        $oldTasks = Task::where('type', '=', 'Goal')
                            ->where('type_id', $subGoal['id'])->pluck('id')->toArray();
                        //update sub goal name
                        $subGoalData = SubGoal::find($subGoal['id']);
                        //update history
                        if ($subGoalData['name'] != $subGoal['name']) {
                            $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'name', 'name of sub-goal', $subGoalData['name'], $subGoal['name']);
                        }
                        $subGoalData->update(['name' => $subGoal['name']]);

                        $subGoalData['is_template'] = $input['is_template'];
                        $subGoals['industry_id'] = $input['industry_id'];
                        $subGoals['department_id'] = $input['department_id'];
                        $subGoals['project_id'] = $input['project_id'];
                        $subGoals['job_title_id'] = $input['job_title_id'];
                        $subGoals['responsible_id'] = $input['responsible_id'];
                        $subGoals['start_time'] = $input['start_time'];
                        $subGoals['deadline'] = $input['deadline'];
                        $subGoals['recurring'] = $input['recurring'];
                        $subGoals['is_public'] = $input['is_public'];
                        $this->updateTaskByType('Goal', $subGoals, $oldTasks, $subGoal['tasks'], $user, $subGoalData, $resetTask, $historyArray);
                    } else {
                        $historyArray = $this->addGoalTask($goalData, $subGoal, $input, $user['id'], $user['company_id'], $historyArray);
                    }
                }

                $input['update_history'] = json_encode($historyArray);

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $goalData->update($input);
                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Goal', $goalData['id'], $goalData['name'],'update');
                }

                // update Security & Reminder
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('goal', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('goal', $input, null);
                }

                return $this->responseSuccess($goalData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function addGoalTask($mainGoal, $subGoal, $input, $userID, $companyID, $historyArray = []) {
        $subGoalsRules = SubGoal::$rules;
        $subGoal['main_goal_id'] = $mainGoal->id;
        $input['type_main_id'] = $mainGoal->id;
        $subGoalsValidator = Validator::make($subGoal, $subGoalsRules);
        if ($subGoalsValidator->fails()) {
            $errors = ValidateResponse::make($subGoalsValidator);
            return $this->responseError($errors,400);
        }
        $newSubGoal = SubGoal::create($subGoal);
        if ($newSubGoal) {
            $historyArray = $this->setUpdateHistory('created', $userID, $historyArray, 'name', 'sub-goal', $newSubGoal['name']);
        }
        $tasks = $subGoal['tasks'];
        if (!empty($tasks)) {
            foreach ($tasks as $taskItem) {
                $newTask = $this->addTasksByType($taskItem, $input, $userID, $companyID, 'Goal', $newSubGoal->id, true);

//                if ($newTask) {
//                    $historyArray = $this->setUpdateHistory('created', $userID, $historyArray, 'task', $newTask['name']);
//                }
            }
        }

        return $historyArray;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Delete goal API",
     *     description="Delete goal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteGoalAPI",
     *     @OA\Parameter(
     *         description="goal id",
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
            $goalData = Goal::find($id);
            if (empty($goalData)) {
                return $this->responseException('Not found goal', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'goal',
                'objectItem' => $goalData,
            ];
            if ($goalData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($goalData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Goal', $goalData->id, $goalData->name)) {
                    $goalData->update(['delete_status' => 1]);

//                Goal::destroy($id);
                    return $this->responseSuccess("Delete goal success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
