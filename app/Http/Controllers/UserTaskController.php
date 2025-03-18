<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Models\TaskAssignee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\UserTask;
use App\Models\Task;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="UserTasks",
 *     description="UserTasks APIs",
 * )
 **/
class UserTaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/userTasks",
     *     tags={"userTasks"},
     *     summary="Get userTasks",
     *     description="Get userTasks list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUserTasks",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $result = UserTask::leftJoin('projects', 'user_tasks.project_id','=', 'projects.id')
                    ->leftJoin('departments','user_tasks.department_id','=','departments.id')
                    ->leftJoin('job_titles','user_tasks.job_title_id','=','job_titles.id')
                    ->join('users','user_tasks.added_by','=','users.id')
                    ->where(function ($q) use ($user) {
                        if ($user->role_id > 1) {
                            $companyData = Company::where("id", $user->company_id)->first();
                            $q-> whereRaw('FIND_IN_SET(?, user_tasks.industry_id)', [$companyData->industry_id])
                                -> where (function ($query) use ($user) {
                                    $query-> where('user_tasks.company_id', $user->company_id)
                                        -> orWhere('user_tasks.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q-> where('user_tasks.added_by', 1);
                        }
                    })
                    ->with(['tasks' => function($query) {
                        $query->with(['task_assignees']);
                    }])
                    ->select('user_tasks.*','projects.name as project_name','departments.name as department_name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'))
                    ->get();
                if($result){
                    $this->calculateProgressRateByType($result);
//                    foreach ($result as $user_task) {
//                        $totalTask = 0;
//                        $doneTask = 0;
//                        $rate = 0;
//                        if ($user_task->status == 1) {
//                            $rate = 0;
//                        } elseif ($user_task->status == 3) {
//                            $rate = 100;
//                        } else {
//                            if (!empty($user_task->tasks)) {
//                                foreach ($user_task->tasks as $task) {
//                                    if (!empty($task->task_assignees)) {
//                                        foreach ($task->task_assignees as $assignee) {
//                                            if ($assignee->responsible == 1) {
//                                                $totalTask += 1;
//                                                if ($assignee->status == 3) {
//                                                    $doneTask += 1;
//                                                }
//                                            }
//                                        }
//                                        $rate = $doneTask / $totalTask * 100;
//                                    }
//                                }
//                            }
//                        }
//                        $user_task->rate = $rate;
//                    }
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
     *     path="/api/v1/userTasks",
     *     tags={"userTasks"},
     *     summary="Create new userTasks",
     *     description="Create new userTasks",
     *     security={{"bearerAuth":{}}},
     *     operationId="createUserTasks",
     *     @OA\RequestBody(
     *         description="userTasks schemas",
     *         @OA\JsonContent(ref="#/components/schemas/userTasks")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, UserTask $user_task)
    {
        try {
            if (!$user = $this->getAuthorizedUser('task', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $rules = UserTask::$rules;
                $companyData = Company::where("id",$user['company_id'])->first();
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user['company_id'];
                if (empty($input['industry_id'])) {
                    $input['industry_id'] = $companyData['industry_id'];
                }

                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $input['start_time'] = strtotime($input['start_time']);
                } else {
                    $input['start_time'] = strtotime("today");
                }
                if (!$input['is_activated']) {
//                    $input['deadline'] = null;
                    $input['deadline'] = strtotime('+99 years', $input['start_time']);
                    $input['recurring'] = 'indefinite';
                } else {
                    if (!empty($input['deadline'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    } else {
//                        $input['deadline'] = null;
                        $input['deadline'] = strtotime('+99 years', $input['start_time']);
                        $input['recurring'] = 'indefinite';
                    }
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newUserTask = UserTask::create($input);

                if ($newUserTask && $newUserTask['responsible_id']) {
                    $this->pushNotification($user['id'], $user['company_id'], 2, [$newUserTask['responsible_id']], 'task', 'User', $newUserTask['id'], $newUserTask['name'], 'responsible');
                }

                if (!empty($input['tasks'])) {
                    $newTask = '';
                    foreach ($input['tasks'] as $task) {
                        $newTask = $this->addTasksByType($task, $input, $user['id'], $user['company_id'], 'User', $newUserTask->id);
                    }
//                    if ($newTask) {
//                        $this->pushNotification($user['id'], $user['company_id'], 2, [$newTask['responsible_id']], 'task', 'User', $newUserTask['id'], $newUserTask['name'], 'responsible');
//                    }
                }

                //Handle to create task
//                $tasks = $input['tasks'];
//                if (!empty($tasks)) {
//                    foreach ($tasks as $item) {
//                        $taskRules = Task::$rules;
//                        $item['added_by'] = $user['id'];
//                        $item['industry_id'] = $input['industry_id'];
//                        $item['type_id'] = $newUserTask->id;
//                        $item['company_id'] = $user['company_id'];
//                        $item['type'] = 'User';
//                        $item['status'] = 1;
//
//                        $taskValidator = Validator::make($item, $taskRules);
//
//                        if ($taskValidator->fails()) {
//                            $errors = ValidateResponse::make($taskValidator);
//                            return $this->responseError($errors,400);
//                        }
//                        $newTask = Task::create($item);
//                        $taskAssignees = $item['taskAssignees'];
//                        $responsiblePerson = $item['responsiblePerson'];
//                        if (!empty($taskAssignees) && !empty($responsiblePerson)) {
//                            $rulesTaskAssignee = TaskAssignee::$rules;
//                            foreach ($taskAssignees as $assignee) {
//                                $inputTaskAssignee['company_id'] = $newTask->company_id;
//                                $inputTaskAssignee['task_id'] = $newTask->id;
//                                $inputTaskAssignee['user_id'] = $assignee;
//                                $inputTaskAssignee['responsible'] = null;
//                                if (in_array($assignee, $responsiblePerson)) {
//                                    $inputTaskAssignee['responsible'] = 1;
//                                }
//
//                                $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);
//
//                                if ($validatorTaskAssignee->fails()) {
//                                    $errors = ValidateResponse::make($validatorTaskAssignee);
//                                    return $this->responseError($errors,400);
//                                }
//                                $newTaskAssignee = TaskAssignee::create($inputTaskAssignee);
//
////                                $email = $newTaskAssignee->user->email;
////                                $data = array(
////                                    'name' => $newTaskAssignee->user->first_name . ' ' . $newTaskAssignee->user->last_name,
////                                    'assigned_by' => $user->first_name . ' ' . $user->last_name,
////                                    'deadline' => $newTask->deadline,
////                                    'url' => config('app.site_url') . '/employee/tasks',
////                                );
////
////                                Mail::to($email)->send(new AssignedTaskMail($data));
//                            }
//                            $this->pushNotification($user['id'], $user['company_id'], 2, $taskAssignees, 'task');
//                        }
//                    }
//                }

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newUserTask, $input);

                return $this->responseSuccess($newUserTask);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/userTasks/{id}",
     *     tags={"userTasks"},
     *     summary="Get userTasks by id",
     *     description="Get userTasks by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUserTasksByIdAPI",
     *     @OA\Parameter(
     *         description="userTasks id",
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
            $userTaskData = UserTask::leftJoin('projects', 'user_tasks.project_id','=', 'projects.id')
                ->leftJoin('departments','user_tasks.department_id','=','departments.id')
                ->leftJoin('job_titles','user_tasks.job_title_id','=','job_titles.id')
                ->where('user_tasks.id', $id)
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                ->select('user_tasks.*','projects.name as project_name','departments.name as department_name','job_titles.name as job_title_name')
                ->first();
            if (empty($userTaskData)) {
                return $this->responseException('Not found userTask', 404);
            }
            foreach ($userTaskData->tasks as $key => $task) {
//                if ($task->status == 1) {
//                    $task->update(['status' => 2]);
//                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
                $task = $this->getReminderObject($task);
//                $userTaskData->responsible_id = $task->responsible_id;
            }

            // get Security information
            $this->getSecurityObject('task', $userTaskData);
            // get Reminder/ start date - due date information
            $userTaskData = $this->getReminderObject($userTaskData);

            return $this->responseSuccess($userTaskData);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/user/userTasks/{id}",
     *     tags={"userTasks"},
     *     summary="Get userTasks by id",
     *     description="Get userTasks by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUserTasksByIdAPI",
     *     @OA\Parameter(
     *         description="userTasks id",
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
            $userTaskData = UserTask::leftJoin('projects', 'user_tasks.project_id','=', 'projects.id')
                ->leftJoin('departments','user_tasks.department_id','=','departments.id')
                ->leftJoin('job_titles','user_tasks.job_title_id','=','job_titles.id')
                ->where('user_tasks.id', $id)
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                ->select('user_tasks.*','projects.name as project_name','departments.name as department_name','job_titles.name as job_title_name')
                ->first();
            if (empty($userTaskData)) {
                return $this->responseException('Not found userTask', 404);
            }
            if ($userTaskData->status == 1) {
                $userTaskData->update(['status' => 2]);
            }
            foreach ($userTaskData->tasks as $key => $task) {
                if ($task->status == 1) {
                    $task->update(['status' => 2]);
                }
                $checkExistAssignee = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));
                if ($checkExistAssignee === false) {
                    unset($userTaskData->tasks[$key]);
                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
            }

            return $this->responseSuccess($userTaskData,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/userTasks/{id}",
     *     tags={"userTasks"},
     *     summary="Update userTasks API",
     *     description="Update userTasks API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateUserTasksAPI",
     *     @OA\Parameter(
     *         description="userTasks id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="userTasks schemas",
     *         @OA\JsonContent(ref="#/components/schemas/userTasks")
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
            if (!$user = $this->getAuthorizedUser('task', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = UserTask::$updateRules;
                $input = $request->all();
                $resetTask = false;
                if ($input['requestEdit']) {
                    $resetTask = true;
                }
                $userTaskData = UserTask::find($input['id']);
                if (empty($userTaskData)) {
                    return $this->responseException('Not found userTask', 404);
                }

                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $input['start_time'] = strtotime($input['start_time']);
                } else {
                    $input['start_time'] = strtotime("today");
                }
                if (!$input['is_activated']) {
//                    $input['deadline'] = null;
                    $input['deadline'] = strtotime('+99 years', $input['start_time']);
                    $input['recurring'] = 'indefinite';
                } else {
                    if (!empty($input['deadline'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    } else {
//                        $input['deadline'] = null;
                        $input['deadline'] = strtotime('+99 years', $input['start_time']);
                        $input['recurring'] = 'indefinite';
                    }
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $userTaskData->update($input);

                if ($userTaskData['responsible_id']) {
                    $this->pushNotification($user['id'], $user['company_id'], 2, [$userTaskData['responsible_id']], 'task', 'User', $userTaskData['id'], $userTaskData['name'], 'responsible');
                }

                // update Security & Reminder
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('task', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('task', $input, null);
                }

                //Handle to update task
                $task = $input['tasks'];
                $oldTasks = Task::where('type', '=', 'User')
                    ->where('type_id', $input['id'])->pluck('id')->toArray();
                $this->updateTaskByType('User', $input, $oldTasks, $task, $user, $userTaskData, $resetTask, null);

                return $this->responseSuccess($userTaskData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = UserTask::$updateRules;
                $input = $request -> all();

                $userTaskData = UserTask::where("id",$id)->first();
                if (empty($userTaskData)) {
                    return $this->responseException('Not found userTask', 404);
                }
                $input['type'] = $userTaskData->type;
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $userTaskData->update($input);

                //Handle to update task
                $task = $input['tasks'];
                $oldTasks = Task::where('type', '=', 'User')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                $this->processTaskByType('User', $oldTasks, $task, $user);

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
                $getTaskByUserTask = Task::where('type', '=', 'User')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                if (!empty($getTaskByUserTask)) {
                    foreach ($getTaskByUserTask as $task) {
                        if ($task['task_assignees']) {
                            foreach ($task['task_assignees'] as $assignee) {
                                if ($assignee->responsible == 1 && $assignee->status != 3) {
                                    return $this->responseSuccess($userTaskData,201);
                                }
                            }
                        }
                    }
                }
                $userTaskData->update(['status' => 3]);

                return $this->responseSuccess($userTaskData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/userTasks/{id}",
     *     tags={"userTasks"},
     *     summary="Delete userTasks API",
     *     description="Delete userTasks API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteUserTasksAPI",
     *     @OA\Parameter(
     *         description="userTasks id",
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
            $userTaskData = UserTask::where("id",$id)->first();
            if (empty($userTaskData)) {
                return $this->responseException('Not found userTask', 404);
            }
            UserTask::destroy($id);
            return $this->responseSuccess("Delete userTask success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
