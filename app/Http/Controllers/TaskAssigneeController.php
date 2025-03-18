<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Mail\FinishedTaskMail;
use App\Models\Company;
use App\Models\Deviation;
use App\Models\Report;
use App\Models\RiskAnalysis;
use App\Models\TaskAssignee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="TaskAssignees",
 *     description="Task assignee APIs",
 * )
 **/
class TaskAssigneeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/taskAssignees",
     *     tags={"TaskAssignees"},
     *     summary="Get TaskAssignees",
     *     description="Get TaskAssignees list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTaskAssignees",
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
                $checkPermission = $request->checkPermission;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;

                $result = TaskAssignee::leftJoin('tasks', 'task_assignees.task_id', 'tasks.id')
                    ->where ('task_assignees.company_id', $user->company_id);
                if ($checkPermission != 'allow') {
                    $result = $result->where('user_id', $user->id);
                }
                if ($orderBy) {
                    if ($orderBy == 'latest') {
                        $result = $result->latest();
                    } elseif ($orderBy == 'oldest') {
                        $result = $result->oldest();
                    }
                }
                if ($limit) {
                    $result = $result->limit($limit);
                }
                $result = $result->select('task_assignees.*', 'tasks.name', 'tasks.added_by', 'tasks.type', 'tasks.type_id')
                    ->get();

                if($result){
                    foreach ($result as $task_assignee){
                        $assigneeUser = User::find($task_assignee->user_id);
                        $task_assignee->assignee_name = $assigneeUser->first_name . " " . $assigneeUser->last_name;
                        $addedByUser = User::find($task_assignee->added_by);
                        $task_assignee->added_by_name = $addedByUser->first_name . " " . $addedByUser->last_name;
                    }
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
     *     path="/api/v1/taskAssignees",
     *     tags={"TaskAssignees"},
     *     summary="Create new TaskAssignees",
     *     description="Create new TaskAssignees",
     *     security={{"bearerAuth":{}}},
     *     operationId="createTaskAssignees",
     *     @OA\RequestBody(
     *         description="TaskAssignees schemas",
     *         @OA\JsonContent(ref="#/components/schemas/TaskAssignees")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, TaskAssignee $taskAssignee)
    {
        //code here
    }

    /**
     * @OA\Get(
     *     path="/api/v1/taskAssignees/{id}",
     *     tags={"TaskAssignees"},
     *     summary="Get TaskAssignees by id",
     *     description="Get TaskAssignees by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTaskAssigneesByIdAPI",
     *     @OA\Parameter(
     *         description="TaskAssignees id",
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
            $taskData = TaskAssignee::where("id",$id)->first();
            if (empty($taskData)) {
                return $this->responseException('Not found item', 404);
            }

            return $this->responseSuccess($taskData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/taskAssignees/{id}",
     *     tags={"TaskAssignees"},
     *     summary="Update TaskAssignees API",
     *     description="Update TaskAssignees API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateTaskAssigneesAPI",
     *     @OA\Parameter(
     *         description="TaskAssignees id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="TaskAssignees schemas",
     *         @OA\JsonContent(ref="#/components/schemas/TaskAssignees")
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
                $taskAssignee = TaskAssignee::where('task_id', $id)
                    ->where('user_id', $user['id'])
                    ->first();
                if (empty($taskAssignee)) {
                    return $this->responseException('Not found taskAssignee', 404);
                }
                if ($user['id'] !== $taskAssignee['user_id']) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $input = $request->all();
//                $rules = TaskAssignee::$updateRules;
//
//                $validator = Validator::make($input, $rules);
//
//                if ($validator->fails()) {
//                    $errors = ValidateResponse::make($validator);
//                    return $this->responseError($errors,400);
//                }
                $checkPending = false;
                if ($input['requestNewStatus']) {
                    if ($taskAssignee['status'] == 4 && $input['requestNewStatus'] != 1) {
                        return $this->responseException('Update failed! Task is pending approval', 404);
                    }
                    if ($input['requestNewStatus'] == 1) {
                        if ($taskAssignee['status'] == 4) {
                            $taskAssignee->update(['status' => $input['requestNewStatus']]);
                            $getPendingAssigneeOfTask = TaskAssignee::where('task_id', $id)->where('status', 4)->count();
                            if (!empty($getPendingAssigneeOfTask)) {
                                $checkPending = true;
                            }
                        }
                    } elseif ($input['requestNewStatus'] == 2 || $input['requestNewStatus'] == 3) {
                        $taskAssignee->update(['status' => $input['requestNewStatus']]);
                        $task = Task::find($id);
                        if ($task->status == 1) {
                            $task->update(['status' => 2]);
                        }
                        if ($input['requestNewStatus'] == 3) {
                            $assigneeByTask = TaskAssignee::where('task_id', $id)
                                ->whereIn('status', [1,2])
                                ->get();

                            if (count($assigneeByTask) == 0) {
                                $task->update(['status' => 6]); //all assignees are done
                                $this->pushNotification($user['id'], $user['company_id'], 2, [$task['responsible_id']], 'task', $task['type'], $task['type_id'], $task['name'], 'assignee_done');
                            }
                        }
                    }
                }
                $taskAssignee->checkPending = $checkPending;
                return $this->responseSuccess($taskAssignee, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/taskAssignees/{id}",
     *     tags={"TaskAssignees"},
     *     summary="Delete TaskAssignees API",
     *     description="Delete TaskAssignees API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteTaskAssigneesAPI",
     *     @OA\Parameter(
     *         description="TaskAssignees id",
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
        //code here
    }
}
