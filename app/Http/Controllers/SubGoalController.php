<?php


namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\SubGoal;
use App\Models\TaskAssignee;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="SubGoals",
 *     description="SubGoal APIs",
 * )
 **/
class SubGoalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/subGoals/{id}",
     *     tags={"Goals"},
     *     summary="Get subGoal by id",
     *     description="Get subGoal by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getGoalByIdAPI",
     *     @OA\Parameter(
     *         description="subGoal id",
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
            $subGoal = SubGoal::where('id', $id)->first();
            $goalData = Goal::leftJoin('departments','goals.department_id','=','departments.id')
                ->leftJoin('job_titles','goals.job_title_id','=','job_titles.id')
                ->where('goals.id', $subGoal->main_goal_id)
                ->with(['sub_goals' => function($query) {
                    $query->with(['tasks' => function($q) {
                        $q->with(['task_assignees']);
                    }]);
                }])
                ->select('goals.*','departments.name as department_name','job_titles.name as job_title_name')
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
                foreach ($goalData->sub_goals as $key => $subGoals) {
                    foreach ($subGoals->tasks as $keyTask => $task) {
                        if ($task->status == 1) {
                            $task->update(['status' => 2]);
                        }
                        $task->remaining_time = '';
                        if ($task->deadline) {
                            $task->remaining_time = $this->calRemainingTime($task->deadline);
                        }
                        if ($task->assigned_company == 0) {
                            if ($task->assigned_department) {
                                $task->assigned_employee = json_decode($task->assigned_employee);
                                $task->assigned_department = json_decode($task->assigned_department);
                            } else {
                                $task->assigned_employee = TaskAssignee::where('task_id', $task->id)
                                    ->pluck('user_id')->toArray();
                            }
                        }
                        $goalData->responsible_id = $task->responsible_id;
                        $goalData->deadline = $task->deadline;
                    }
                }

                // get Reminder / start date - due date information
                $goalData = $this->getReminderObject($goalData);

                return $this->responseSuccess($goalData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
