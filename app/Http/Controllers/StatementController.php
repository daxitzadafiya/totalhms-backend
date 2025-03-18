<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\Contact;
use App\Models\Deviation;
use App\Models\Document;
use App\Models\DocumentNew;
use App\Models\Goal;
use App\Models\Instruction;
use App\Models\Report;
use App\Models\RiskAnalysis;
use App\Models\RiskElementSource;
use App\Models\Routine;
use App\Models\Statement;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\In;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Statements",
 *     description="Statement APIs",
 * )
 **/
class StatementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/statements",
     *     tags={"Statements"},
     *     summary="Get statements",
     *     description="Get statements list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getStatements",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('statement', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (!empty($request->param)) {
                    $result = [];
                    if ($request->param == 'suggestion') {
                        //Deviation
                        $deviations = Deviation::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'subject as name', 'url')
                            ->get();

                        $item['type'] = 'Deviation';
                        $item['data'] = $deviations;

                        array_push($result, $item);

                        //Task
                        $tasks = Task::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Task';
                        $item['data'] = $tasks;

                        array_push($result, $item);

                        //Goal
                        $goals = Goal::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Goal';
                        $item['data'] = $goals;

                        array_push($result, $item);

                        //Routine (with format)
                        $routines = Routine::leftJoin('users', 'routines.responsible_id', '=', 'users.id')
                            ->where('routines.company_id', $user['company_id'])
                            ->where('routines.is_suggestion', 1)
                            ->select('routines.id', 'routines.name', 'routines.url', 'routines.deadline', 'routines.recurring',
                                'routines.responsible_id','users.first_name as first_name','users.last_name as last_name')
                            ->get();

                        $item['type'] = 'Routine';
                        $item['data'] = $routines;

                        array_push($result, $item);

                        //Instruction
                        $instructions = Instruction::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Instruction';
                        $item['data'] = $instructions;

                        array_push($result, $item);

                        //Checklist
                        $checklists = Checklist::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Checklist';
                        $item['data'] = $checklists;

                        array_push($result, $item);

                        //Contact
                        $contacts = Contact::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Contact';
                        $item['data'] = $contacts;

                        array_push($result, $item);

                        //Risk area
                        $riskElementSources = RiskElementSource::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Risk area';
                        $item['data'] = $riskElementSources;

                        array_push($result, $item);

                        //Report checklist
                        $reports = Report::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'url')
                            ->get();

                        if (!empty($reports)) {
                            foreach ($reports as $report) {
                                $checklistInfo = json_decode($report['checklist_info']);
                                $report->name = $checklistInfo->name;
                            }
                        }

                        $item['type'] = 'Report checklist';
                        $item['data'] = $reports;

                        array_push($result, $item);

                        //Report analysis
                        $riskAnalysis = RiskAnalysis::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Risk analysis';
                        $item['data'] = $riskAnalysis;

                        array_push($result, $item);

                        //Documents
                        $documents = DocumentNew::where('company_id', $user['company_id'])
                            ->where('is_suggestion', 1)
                            ->select('id', 'name', 'url')
                            ->get();

                        $item['type'] = 'Document';
                        $item['data'] = $documents;

                        array_push($result, $item);

                    } elseif ($request->param == 'calendar') {
                       $startDate = strtotime($request->startDate);
                       $endDate = strtotime($request->endDate);

                        //Task assignee
                        $assignees = TaskAssignee::leftJoin('tasks', 'task_assignees.task_id', 'tasks.id')
                            ->where('task_assignees.company_id', $user['company_id'])
                            ->where('task_assignees.user_id', $user['id']);
                        if ($request->startDate == $endDate) {
                            $assignees = $assignees->whereDate('start_time','<=', $startDate)
                                ->whereDate('deadline','>=', $endDate);
                        } else {
                            $assignees = $assignees->whereDate('start_time','>=', $startDate)
                                ->whereDate('deadline','<=', $endDate);
                        }
                        $assignees = $assignees->select('task_assignees.*', 'tasks.name', 'tasks.added_by', 'tasks.type', 'tasks.type_id', 'tasks.start_time', 'tasks.deadline')
                            ->get();

                        $item['type'] = 'Task Assignee';
                        $item['data'] = $assignees;

                        array_push($result, $item);

                        //Responsible person
                        $tasks = Task::where('company_id', $user['company_id'])
                            ->where('responsible_id', $user['id']);
                        if ($startDate ==$endDate) {
                            $tasks = $tasks->whereDate('start_time','<=', $startDate)
                                ->whereDate('deadline','>=', $endDate);
                        } else {
                            $tasks = $tasks->whereDate('start_time','>=', $startDate)
                                ->whereDate('deadline','<=', $endDate);
                        }
                        $tasks = $tasks->select('tasks.name', 'tasks.added_by', 'tasks.added_by', 'tasks.type', 'tasks.type_id', 'tasks.start_time', 'tasks.deadline')
                            ->get()->toArray();


                        $userTasks = UserTask::where('responsible_id', $user->id)->get();

                        foreach ($userTasks as $userTask) {
                            $key = array_search($userTask['id'], array_column($tasks, 'type_id'));
                            if ($key > -1 && $tasks[$key]->type == 'User') {
                                continue;
                            } else {
                                $userTask->type = 'User';
                                $userTask->type_id = $userTask['id'];
                                array_unshift($tasks, $userTask);
                            }
                        }

                        $item['type'] = 'Responsible Person';
                        $item['data'] = $tasks;

                        array_push($result, $item);

                        //Goal
                        $goals = Goal::where ('company_id', $user['company_id']);
                        if ($startDate ==$endDate) {
                            $goals = $goals->whereDate('start_time','<=', $startDate)
                                ->whereDate('deadline','>=', $endDate);
                        } else {
                            $goals = $goals->whereDate('start_time','>=', $startDate)
                                ->whereDate('deadline','<=', $endDate);
                        }
                        $goals = $goals->select('id','name', 'added_by', 'start_time', 'deadline')
                            ->get();

                        $goals = $this->filterViewList('goal', $user, $user->filterBy, $goals);

                        $item['type'] = 'Goal';
                        $item['data'] = $goals;

                        array_push($result, $item);

                        //Routine
                        $routines = Routine::where ('company_id', $user['company_id']);
                        if ($startDate ==$endDate) {
                            $routines = $routines->whereDate('created_at','<=', $startDate)
                                ->whereDate('deadline','>=', $endDate);
                        } else {
                            $routines = $routines->whereDate('created_at','>=', $startDate)
                                ->whereDate('deadline','<=', $endDate);
                        }
                        $routines = $routines->select('id', 'name', 'added_by', 'created_at as start_time', 'deadline')
                            ->get();

                        $routines = $this->filterViewList('routine', $user, $user->filterBy, $routines);

                        $item['type'] = 'Routine';
                        $item['data'] = $routines;

                        array_push($result, $item);

                    } elseif ($request->param == 'dashboard') {
                        if (!empty($user['department'])) {
                            $userDepartment = $user['department'];
                        } else {
                            $userDepartment = 0;
                        }
                        //Deviation
                        if ($request->dashboardType == 'deviation') {
                            $result = Deviation::leftJoin('employees', 'deviations.added_by', 'employees.user_id')
                                ->where('deviations.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('deviations.id', 'deviations.subject as name', 'deviations.added_by', 'deviations.status', 'employees.department_id as userDepartment')
                                ->get();

//                        $result = Deviation::where('company_id', $user['company_id'])
//                            ->where(function ($q) use ($user, $userDepartment) {
//                                $q->where('added_by', $user['id'])
//                                    ->orWhere('department_id', $userDepartment);
//                            })
//                            ->select('deviations.*')
//                            ->get();
//                        $item['type'] = 'Deviation';
//                        $item['data'] = $deviations;
//
//                        array_push($result, $item);
                        } else if ($request->dashboardType == 'task') { //Task
                            $result = Task::leftJoin('employees', 'tasks.added_by', 'employees.user_id')
                                ->where('tasks.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('tasks.id', 'tasks.name', 'tasks.added_by', 'tasks.deadline', 'employees.department_id as userDepartment')
                                ->get();
                        } else if ($request->dashboardType == 'goal') { //Goal
                            $result = Goal::leftJoin('employees', 'goals.added_by', 'employees.user_id')
                                ->where('goals.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('goals.id', 'goals.name', 'goals.added_by', 'goals.deadline', 'goals.status', 'employees.department_id as userDepartment')
                                ->get();
                        } else if ($request->dashboardType == 'routine') { //Routine
                            $result = Routine::leftJoin('employees', 'routines.added_by', 'employees.user_id')
                                ->where('routines.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('routines.id', 'routines.name', 'routines.added_by', 'routines.deadline', 'employees.department_id as userDepartment')
                                ->get();

                        } else if ($request->dashboardType == 'instruction') { //Instruction
                            $result = Instruction::leftJoin('employees', 'instructions.added_by', 'employees.user_id')
                                ->where('instructions.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('instructions.id', 'instructions.name', 'instructions.added_by', 'employees.department_id as userDepartment')
                                ->get();

                        } else if ($request->dashboardType == 'checklist') { //Checklist
                            $result = Checklist::leftJoin('employees', 'checklists.added_by', 'employees.user_id')
                                ->where('checklists.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('checklists.id', 'checklists.name', 'checklists.added_by', 'employees.department_id as userDepartment')
                                ->get();

                        } else if ($request->dashboardType == 'contact') { //Contact
                            $result = Category::leftJoin(DB::raw('(SELECT contacts.category_id, COUNT(*) AS countByCategory FROM contacts WHERE contacts.company_id = '. $user['company_id'] .' GROUP BY contacts.category_id) AS CBC'), 'CBC.category_id', 'categories.id')
                                ->where('categories.company_id', $user['company_id'])
                                ->where('categories.type', 'contact')
                                ->select('categories.id', 'categories.name', 'CBC.countByCategory')
                                ->get();

                        } else if ($request->dashboardType == 'risk element') { //Risk area
                            $result = RiskElementSource::leftJoin('employees', 'risk_element_sources.added_by', 'employees.user_id')
                                ->where('risk_element_sources.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('risk_element_sources.id', 'risk_element_sources.name', 'risk_element_sources.added_by', 'employees.department_id as userDepartment')
                                ->get();

                        } else if ($request->dashboardType == 'report checklist') { //Report checklist
                            $result = Report::leftJoin('employees', 'reports.added_by', 'employees.user_id')
                                ->where('reports.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('reports.id', 'reports.added_by', 'reports.checklist_info', 'reports.status', 'employees.department_id as userDepartment')
                                ->get();

                            if (!empty($result)) {
                                foreach ($result as $report) {
                                    $checklistInfo = json_decode($report['checklist_info']);
                                    $report->name = $checklistInfo->name;
                                }
                            }
                        } else if ($request->dashboardType == 'risk analysis') { //Report analysis
                            $result = RiskAnalysis::leftJoin('employees', 'risk_analysis.added_by', 'employees.user_id')
                                ->where('risk_analysis.company_id', $user['company_id']);
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('risk_analysis.id', 'risk_analysis.name', 'risk_analysis.added_by', 'risk_analysis.status', 'employees.department_id as userDepartment')
                                ->get();
                        } else if ($request->dashboardType == 'document') { //Documents
                            $result = DocumentNew::leftJoin('employees', 'documents_new.added_by', 'employees.user_id')
                                ->where('documents_new.company_id', $user['company_id'])
                                ->where('documents_new.type', 'document');
                            if ($user['filterBy'] != 'company admin') {
                                $result = $result->where('employees.department_id', $userDepartment);
                            }
                            $result = $result->select('documents_new.id', 'documents_new.name', 'documents_new.added_by', 'employees.department_id as userDepartment')
                                ->get();
                        }
                    }
                } else {
                    $result = Statement::where(function ($q) use ($user) {
                        if ($user->role_id > 1) {
                            $q->where('company_id', $user['company_id'])
                                ->orWhere('added_by', 1);
                        } else if ($user->role_id == 1) {
                            $q->where('added_by', 1);
                        }
                    })
                        ->where('delete_status', 0)
                        ->with(['user_added'])
                        ->get();
                }

                if($result){
                    $result = $this->filterViewList('statement', $user, $user->filterBy, $result);
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
     *     path="/api/v1/statements",
     *     tags={"Statements"},
     *     summary="Create new statement",
     *     description="Create new statement",
     *     security={{"bearerAuth":{}}},
     *     operationId="createStatement",
     *     @OA\RequestBody(
     *         description="Statement schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Statement")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Statement $statement)
    {
        try {
            $input = $request->all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('statement', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Statement::$rules;
                if ($user['role_id'] > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
                $input['added_by'] = $user['id'];
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
            }
            $newStatement = Statement::create($input);

            return $this->responseSuccess($newStatement);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/statements/{id}",
     *     tags={"Statements"},
     *     summary="Get statement by id",
     *     description="Get statement by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getStatementByIdAPI",
     *     @OA\Parameter(
     *         description="statement id",
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
            $statementData = Statement::where("id",$id)->first();
            if (empty($statementData)) {
                return $this->responseException('Not found statement', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'statement',
                'objectItem' => $statementData,
            ];
            if (!$user = $this->getAuthorizedUser('statement', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                return $this->responseSuccess($statementData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/statements/{id}",
     *     tags={"Statements"},
     *     summary="Update statement API",
     *     description="Update statement API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateStatementAPI",
     *     @OA\Parameter(
     *         description="statement id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Statement schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Statement")
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
            $rules = Statement::$updateRules;
            $input = $request -> all();

            $statementData = Statement::where("id",$id)->first();
            if (empty($statementData)) {
                return $this->responseException('Not found statement', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'statement',
                'objectItem' => $statementData,
            ];
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('statement', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $statementData->update($input);

                return $this->responseSuccess($statementData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/statements/{id}",
     *     tags={"Statements"},
     *     summary="Delete statement API",
     *     description="Delete statement API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteStatementAPI",
     *     @OA\Parameter(
     *         description="statement id",
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
            $statementData = Statement::where("id",$id)->first();
            if (empty($statementData)) {
                return $this->responseException('Not found statement', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'statement',
                'objectItem' => $statementData,
            ];
            if (!empty($statementData->is_template)) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('statement', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Statement', $statementData->id, $statementData->title)) {
                    $statementData->update(['delete_status' => 1]);

//                    Statement::destroy($id);
                    return $this->responseSuccess("Delete statement success", 200);
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
