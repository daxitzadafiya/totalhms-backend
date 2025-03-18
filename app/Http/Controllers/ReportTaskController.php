<?php


namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\AssignedTaskMail;
use App\Models\TaskAssignee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\ReportTask;
use App\Models\Task;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="ReportTasks",
 *     description="ReportTask APIs",
 * )
 **/
class ReportTaskController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/reportTasks",
     *     tags={"ReportTasks"},
     *     summary="Create new reportTask",
     *     description="Create new reportTask",
     *     security={{"bearerAuth":{}}},
     *     operationId="createReportTask",
     *     @OA\RequestBody(
     *         description="ReportTask schemas",
     *         @OA\JsonContent(ref="#/components/schemas/ReportTask")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, ReportTask $reportTask)
    {

    }

    /**
     * @OA\Get(
     *     path="/api/v1/reportTasks/{id}",
     *     tags={"reportTasks"},
     *     summary="Get reportTasks by id",
     *     description="Get reportTasks by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getReportTasksByIdAPI",
     *     @OA\Parameter(
     *         description="reportTasks id",
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
            $reportTaskData = ReportTask::where('id', $id)
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                ->first();
            if (empty($reportTaskData)) {
                return $this->responseException('Not found reportTask', 404);
            }
            foreach ($reportTaskData->tasks as $key => $task) {
                if ($task->status == 1) {
                    $task->update(['status' => 2]);
                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
            }

            return $this->responseSuccess($reportTaskData,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/user/reportTasks/{id}",
     *     tags={"reportTasks"},
     *     summary="Get reportTasks by id",
     *     description="Get reportTasks by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getReportTasksByIdAPI",
     *     @OA\Parameter(
     *         description="reportTasks id",
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
            $reportTaskData = ReportTask::where('id', $id)
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                ->first();
            if (empty($reportTaskData)) {
                return $this->responseException('Not found reportTask', 404);
            }
            if ($reportTaskData->status == 1) {
                $reportTaskData->update(['status' => 2]);
            }
            foreach ($reportTaskData->tasks as $key => $task) {
                if ($task->status == 1) {
                    $task->update(['status' => 2]);
                }
                $checkExistAssignee = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));
                if ($checkExistAssignee === false) {
                    unset($reportTaskData->tasks[$key]);
                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
            }

            return $this->responseSuccess($reportTaskData,201);
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
                $rules = ReportTask::$updateRules;
                $input = $request -> all();

                $reportTaskData = ReportTask::where("id", $id)->first();
                if (empty($reportTaskData)) {
                    return $this->responseException('Not found reportTask', 404);
                }
                $input['type'] = $reportTaskData->type;
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $reportTaskData->update($input);

                //Handle to update task
                $task = $input['tasks'];
                $oldTasks = Task::where('type', '=', 'Report')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                $this->processTaskByType('Report', $oldTasks, $task, $user);

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

                $getTaskByReportTask = Task::where('type', '=', 'Report')
                    ->where('type_id', $id)->with(['task_assignees'])->get();

                if (!empty($getTaskByReportTask)) {
                    foreach ($getTaskByReportTask as $task) {
                        if ($task['task_assignees']) {
                            foreach ($task['task_assignees'] as $assignee) {
                                if ($assignee->responsible == 1 && $assignee->status != 3) {
                                    return $this->responseSuccess($reportTaskData,201);
                                }
                            }
                        }
                    }
                }
                $reportTaskData->update(['status' => 3]);

                return $this->responseSuccess($reportTaskData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
