<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\FinishedTaskMail;
use App\Models\Company;
use App\Models\Deviation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use File;
use Illuminate\Support\Facades\Response;
use Config;
/**
 * @OA\Tag(
 *     name="Documents",
 *     description="Document APIs",
 * )
 **/
class DocumentController extends Controller
{
    /**
     * Check role persmission on access document
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('document.role');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents",
     *     tags={"Documents"},
     *     summary="Get documents",
     *     description="Get documents list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDocuments",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('document', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $type = $request->type;
                $added_from = $request->added_from;
                $project = $request->project;
                $contact = $request->contact;
                $employee = $request->employee;
                $result = Document::leftJoin('categories', 'documents.category_id','=', 'categories.id')
                    ->leftJoin('users','documents.added_by','=','users.id');
                if ($user->role_id === 1) {
                    $result = $result->where('documents.added_by', 1);
                } else {
                    $result = $result->whereRaw('FIND_IN_SET(?, documents.industry_id)', [$user['company']['industry_id']])
                        ->where (function ($q) use ($user) {
                            $q->where('documents.company_id', $user['company_id'])
                                ->orWhere('documents.added_by', 1);
                        })
                        ->where (function ($q) use ($user) {
                            $q->where('documents.added_by', '<>', $user->id)
                                ->where('documents.status', 1)
                                ->where('documents.is_public', '<>', 0);
                        });
                    if ($user->role_id !== 2) {
                        $result = $result->where(function ($q) use ($user) {
                            // ------share to all employees
                            $q->where('documents.security_department_array', null)
                                ->where('documents.security_employee_array', null)
                                // ------share to chosen employees / departments / both
                                // share to manager if document created by user (user choose to share)
                                ->orWhere(function ($q1) use ($user) {
                                    $q1->whereRaw('FIND_IN_SET(?, documents.security_department_array)', [$user->employee->department_id])
                                        ->orWhere(function ($q2) use ($user) {
                                            $q2->whereRaw('FIND_IN_SET(?, documents.security_employee_array)', [$user->id]);
                                        });
                                });
                            // ------ share to manager if document created by user (user choose to share)
//                                    ->orWhere(function ($q) use ($user) {
//                                        if ($user->role_id === 3) {
//                                            $q->where('documents.show_manager', 1)
//                                                ->where('documents.added_by_department_id', $user->employee->department_id);
//                                        }
//                                    });
                            })
                            ->orWhere('documents.added_by', $user->id)
                            ->orWhere('documents.is_public', null);
                    }


//                    $result = $result->whereRaw('FIND_IN_SET(?, documents.industry_id)', [$user['company']['industry_id']])
//                        ->where (function ($q) use ($user) {
//                            $q->where('documents.company_id', $user['company_id'])
//                                ->orWhere('documents.added_by', 1);
//                        })
//                        ->where (function ($q) use ($user) {
//                            $q->where('documents.added_by', '<>', $user->id)
//                                ->where('documents.status', 1)
//                                ->orWhere('documents.added_by', $user->id);
//                        });
//                    if ($user->role_id !== 2) {
//                        $result = $result->where('documents.is_public', 1)
//                            ->where(function ($q) use ($user) {
//                                // ------share to all employees
//                                $q->where('documents.security_department_array', null)
//                                    ->where('documents.security_employee_array', null)
//                                    // ------share to chosen employees / departments / both
//                                    ->orWhere(function ($q1) use ($user) {
//                                        $q1->whereRaw('FIND_IN_SET(?, documents.security_department_array)', [$user->employee->department_id])
//                                            ->orWhere(function ($q2) use ($user) {
//                                                $q2->whereRaw('FIND_IN_SET(?, documents.security_employee_array)', [$user->id]);
//                                            });
//                                    })
//                                    // ------ share to manager if document created by user (user choose to share)
//                                    ->orWhere(function ($q) use ($user) {
//                                        if ($user->role_id === 3) {
//                                            $q->where('documents.show_manager', 1)
//                                                ->where('documents.added_by_department_id', $user->employee->department_id);
//                                        }
//                                    });
//                            })
////                            ->orWhere('documents.added_by', $user->id)
//                            ->orWhere('documents.is_public', null);
//                    }
                }
                if ($employee){
                    $result = $result->where('documents.employee_id', $employee);
                }
                if ($contact){
                    $result = $result->where('documents.contact_id', $contact);
                }
                if ($type){
                    $result = $result->where('documents.type', $type);
                }
                if ($added_from){
                    $result = $result->where('documents.added_from', $added_from);
                    if ($added_from == 4) { // added from EMPLOYEE
                        $result = $result->where (function ($q) {
                            $q->where('documents.category_id', 9)
                                ->where('documents.uri', '<>', null)
                                ->orWhere('documents.category_id', '<>', 9);
                        });
                    }
                }
                if ($project){
                    $result = $result->where('documents.project_id', $project);
                }
                $result = $result->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                    ->where('documents.delete_status', 0)
                    ->select('documents.*', 'users.email as added_by_email',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name')
                    ->get();
//                $baseUrl = config('app.app_url');
//                foreach ($result as $item) {
//                    if (!empty($item['uri'])) {
//                        if ($item['category_id'] == 9) {
//                            $item['url'] = $baseUrl . "/api/v1/image/".  $item['uri'];
//                        } else {
//                            $item['url'] = $baseUrl . "/api/v1/uploads/".  $item['uri'];
//                        }
//                    }
//                }
                if($result){
                    $result = $this->filterViewList('document', $user, $user->filterBy, $result);
                    foreach ($result as $item) {
                        $item->count_related_object = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = Document::where('parent_id', $item['id'])->count();

                            if ($countRelatedObject > 0) {
                                $item->count_related_object = $countRelatedObject;
                            }
                        }
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
     *     path="/api/v1/documents",
     *     tags={"Documents"},
     *     summary="Create new document",
     *     description="Create new document",
     *     security={{"bearerAuth":{}}},
     *     operationId="createDocument",
     *     @OA\RequestBody(
     *         description="Document schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Document $document)
    {
        try {
            $input = $request->all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('document', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $rules = Document::$rules;
                $fileRules = Document::$fileRules;
                $companyData = Company::where("id",$user['company_id'])->first();
                if ($user->role_id > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
                if (empty($input['added_by'])) {
                    $input['added_by'] = $user['id'];
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                // REMINDER - check responsible employees
//                if (!empty($input['renewed_option'])) {
//                    if ($input['renewed_option'] == 1) { // Renewed option: 1. Only me
//                        $input['renewed_employee_array'] = $user['id'];
//                    } else if ($input['renewed_option'] == 2) { // Renewed option: 2. Manager
//                        $listUserInfo = User::whereRaw('FIND_IN_SET(?, users.company_id)', [$user['company_id']])
//                            ->whereRaw('FIND_IN_SET(?, users.role_id)', 3)
//                            ->get();
//                        foreach ($listUserInfo as $item_info) {
//                            $input['renewed_employee_array'] = '';
//                            $input['renewed_employee_array'] .= $item_info->id;
//                        }
//                    }
//                }

                // SECURITY - document sharing
                // normal user choose Shared to their manager department
                if (!empty($input['show_manager'])) {
                    $employees = Employee::where('employees.department_id', $user->employee->department_id)
                        ->with(['jobTitle'])
                        ->get();
                    if (!empty($employees)) {
                        $input['security_employee_array'] = null;
                        foreach ($employees as $employee) {
                            if ($employee->jobTitle->role_name == 'Manager') {
                                $input['security_employee_array'] .= $employee['user_id'];
                            }
                        }
                    }
                }

                // If upload with FILE
                if (!empty($request->file('file'))) {
                    $fileValidator = Validator::make($input, $fileRules);
                    if ($fileValidator->fails()) {
                        $errors = ValidateResponse::make($fileValidator);
                        return $this->responseError($errors);
                    }

                    if (!empty($input['private_document'])) {
                        $path = Storage::disk('private')->putFile('documents', $request->file('file'));
                    } else { // if avatar / logo -> save document in public folder
                        $path = Storage::disk('public')->putFile('', $request->file('file'));
                    }
                    $input['uri'] = $path;
                    if ($input['file_size']) {
                        $input['file_size'] = round($input['file_size'] / 1024,2); //convert byte to KB
                    }
                }

                // create new document with file applied from resource
                if (!empty($input['applied_document'])) {
                    $new_uri = 'documents/' . uniqid() . '_' . $input['original_file_name'];
                    File::copy(storage_path('app/uploads/' . $input['uri']), storage_path('app/uploads/' . $new_uri));
                    $input['uri'] = $new_uri;
                }

                // Check if exist document
                if (!empty($input['attachment_updated'])) {
                    $oldFilePath = null;
                    // check exist company logo in document list
                    if (!empty($input['category_name']) && $input['category_name'] == 'Logo') {
                        $newDocument = Document::where('company_id', $user['company_id'])
                            ->where('category_id', $input['category_id'])->first();
                        $oldFilePath = storage_path('app/uploads/' . $newDocument['uri']);
                    }
                    // check exist employee avatar in document list
                    else if(!empty($input['category_name']) && $input['category_name'] == 'Avatar') { // if update employee's avatar
                        $newDocument = Document::where('employee_id', $input['employee_id'])->where('category_id', $input['category_id'])->first();
                        $oldFilePath = public_path() . '/uploads/attachments/' . $newDocument['uri'];
                    }
                    // check exist attachments
                    else if(!empty($input['type_of_attachment'])) {
                        $newDocument = Document::find($input['id']);
                        // when edit -> if changed type from Attachment to Only note => update uri = null
                        if ($input['type_of_attachment'] == 2 && $newDocument['uri']) {
                            $input['uri'] = null;
                            $input['original_file_name'] = null;
                            $oldFilePath = storage_path('app/uploads/' . $newDocument['uri']);
                        }
                        if ($input['type_of_attachment'] == 1 && !empty($input['changed_file'])) {
                            $oldFilePath = storage_path('app/uploads/' . $newDocument['uri']);
                        }
                    }
                    else if ($input['type'] == 'help center') {
                        $newDocument = Document::find($input['id']);
                        $oldFilePath = public_path() . '/uploads/attachments/' . $newDocument['uri'];
                    }
                    else if (empty($newDocument)) {
                        return $this->responseException('Not found document', 404);
                    }
                    $newDocument->update($input);
                    if ($oldFilePath) {
                        File::delete($oldFilePath); // delete previous file in storage folder
                    }
                } else {
                    $newDocument = Document::create($input);
                    if ($newDocument && $user['role_id'] == 1 && $input['type'] != 'help center') {
                        $this->pushNotificationToAllCompanies('Document', $newDocument['id'], $newDocument['name'],'create');
                    }
                }

                // Create task for renewed attachment
//                $oldTask = Task::where('type', '=', 'Attachment')
//                    ->where('type_id', $newDocument->id)->with(['task_assignees'])->first();
                if (!empty($input['renewed_employee_array'])) {
                    $currentRenewedEmployeesResponsible = explode(',', $input['renewed_employee_array']);
                    $deadline = date_format(date_create($newDocument->deadline), 'd.m.Y');
                    $this->pushNotification($user['id'], $user['company_id'], 2, $currentRenewedEmployeesResponsible, 'document', 'Document', $newDocument['id'], $newDocument['name'], 'reminder', null, $deadline);
                } else {
                    $currentRenewedEmployeesResponsible = null;
                }
//                if (!empty($input['is_renewed']) && $input['is_renewed']) {
//                    if ($oldTask) {
//                        //update task
//                        if ($input['deadline'] != $oldTask['deadline']) {
//                            $oldTask->update(['deadline' => $input['deadline']]);
//                        }
//                        $oldTaskAssignees = $oldTask->task_assignees;
//
//                        foreach ($oldTaskAssignees as $taskAssignee) { // Check old task_assignee
//                            $keyTaskAssignee = array_search($taskAssignee['user_id'], $currentRenewedEmployeesResponsible);
//                            if ($keyTaskAssignee > -1 ) {
//                                unset($currentRenewedEmployeesResponsible[$keyTaskAssignee]);
//                            } else {
//                                TaskAssignee::destroy($taskAssignee->id);
//                            }
//                        }
//                        foreach ($currentRenewedEmployeesResponsible as $newAssignee) { // Add new task_assignee
//                            $inputTaskAssignee['company_id'] = $user['company_id'];
//                            $inputTaskAssignee['task_id'] = $oldTask['id'];
//                            $inputTaskAssignee['user_id'] = $newAssignee;
//                            $inputTaskAssignee['responsible'] = 1;
//
//                            $rulesTaskAssignee = TaskAssignee::$rules;
//                            $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);
//
//                            if ($validatorTaskAssignee->fails()) {
//                                $errors = ValidateResponse::make($validatorTaskAssignee);
//                                return $this->responseError($errors,400);
//                            }
//                            $newTaskAssignee = TaskAssignee::create($inputTaskAssignee);
//                        }
//                    } else {
//                        $taskRules = Task::$rules;
//                        $task['added_by'] = $user['id'];
//                        $task['deadline'] = $input['deadline'];
//                        //Handle to create task
//                        $task['name'] = "Renew attachment '" . $input['name'] . "'";
//                        $task['industry_id'] = $user['company']['industry_id'];
//                        $task['company_id'] = $input['company_id'];
//                        $task['type'] = 'Attachment';
//                        $task['type_id'] = $newDocument->id;
//                        $task['status'] = 1;
//                        $taskValidator = Validator::make($task, $taskRules);
//
//                        if ($taskValidator->fails()) {
//                            $errors = ValidateResponse::make($taskValidator);
//                            return $this->responseError($errors,400);
//                        }
//                        $newTask = Task::create($task);
//                        $taskAssignees = $currentRenewedEmployeesResponsible;
//
//                        if (!empty($taskAssignees)) {
//                            $rulesTaskAssignee = TaskAssignee::$rules;
//                            foreach ($taskAssignees as $assignee) {
//                                $inputTaskAssignee['company_id'] = $newTask->company_id;
//                                $inputTaskAssignee['task_id'] = $newTask->id;
//                                $inputTaskAssignee['user_id'] = $assignee;
//                                $inputTaskAssignee['responsible'] = 1;
//
//                                $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);
//
//                                if ($validatorTaskAssignee->fails()) {
//                                    $errors = ValidateResponse::make($validatorTaskAssignee);
//                                    return $this->responseError($errors,400);
//                                }
//                                TaskAssignee::create($inputTaskAssignee);
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
//                } else {
//                    if ($oldTask) {
//                        Task::destroy($oldTask->id);
//                    }
//                }

                $baseUrl = config('app.app_url');
                if (!empty($request->file('file')) && !empty($input['is_public'])) {
                    $newDocument['url'] = $baseUrl. "/api/v1/uploads/".  $input['uri']; // url for other documents uploaded with file
                } else if (!empty($request->file('file')) && empty($input['is_public'])) {
                    $newDocument['url'] = $baseUrl. "/api/v1/image/".  $input['uri']; // url for show avatar & logo
                }

                // update company's logo url
                if (!empty($input['category_name']) && $input['category_name'] == 'Logo') {
                    if (empty($companyData)) {
                        return $this->responseException('Not found user', 404);
                    }
                    $companyData->update(['logo' => $newDocument['url']]);
                }

                // update user's avatar url
                if (!empty($input['category_name']) && $input['category_name'] == 'Avatar') {
                    $userData = User::where("id", $input['employee_id'])->first();
                    if (empty($userData)) {
                        return $this->responseException('Not found user', 404);
                    }
                    $userData->update(['avatar' => $newDocument['url']]);
                }

                // update deviation's attachment url
                if (!empty($input['deviation_id'])) {
                    $deviationData = Deviation::where("id", $input['deviation_id'])->first();
                    if (empty($deviationData)) {
                        return $this->responseException('Not found deviation', 404);
                    }
                    $deviationData->update(['attachment' => $newDocument['url']]);
                }

                if (!empty($input['project_id'])) {
                    $projectData = Project::where("id", $input['project_id'])->first();
                    if (empty($projectData)) {
                        return $this->responseException('Not found project', 404);
                    }
                    $projectData->update(['thumbnail' => $newDocument['url']]);
                }

                return $this->responseSuccess($newDocument);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/{id}",
     *     tags={"Documents"},
     *     summary="Get document by id",
     *     description="Get document by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDocumentByIdAPI",
     *     @OA\Parameter(
     *         description="document id",
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
            $documentData = Document::where("id",$id)
            ->leftJoin('categories_new', 'documents.category_id', '=', 'categories_new.id')
                ->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])->first();
            if (empty($documentData)) {
                return $this->responseException('Not found document', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'document',
                'objectItem' => $documentData,
            ];
            if (!$user = $this->getAuthorizedUser('document', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                foreach ($documentData->tasks as $key => $task) {
                    if ($task->added_by > 1 && $task->status == 1) {
                        $task->update(['status' => 2]);
                    }
                    $task->remaining_time = '';
                    if ($task->deadline) {
                        $task->remaining_time = $this->calRemainingTime($task->deadline);
                    }
                }
                $baseUrl = config('app.app_url');
                if ($documentData['uri']) {
                    if ($documentData['category_id'] == 9) {
                        $documentData['url'] = $baseUrl . "/api/v1/image/".  $documentData['uri'];
                    } else {
                        $documentData['url'] = $baseUrl . "/api/v1/uploads/".  $documentData['uri'];
                    }
                }
                $documentData->count_related_object = 0;
                $documentData->related_objects = '';
                $documentData->parent_object = '';
                if ($documentData['is_template']) {
                    $relatedObject = Document::leftJoin('users', 'documents.added_by','=', 'users.id')
                        ->leftJoin('companies', 'documents.company_id','=', 'companies.id')
                        ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('documents.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select('documents.id', 'documents.name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'companies.name as company_name')
                        ->get();

                    if (count($relatedObject) > 0) {
                        $documentData->count_related_object = count($relatedObject);
                        $documentData->related_objects = $relatedObject;
                    }
                }
                if ($documentData->parent_id) {
                    $parentObject = Document::leftJoin('users', 'documents.added_by','=', 'users.id')
                        ->where('documents.id', $documentData->parent_id)
                        ->select('documents.id', 'documents.name',
                            'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                            DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'))
                        ->first();

                    if ($parentObject) {
                        $documentData->parent_object = $parentObject;
                    }
                }
                $documentData->editPermission = $user->editPermission;
                return $this->responseSuccess($documentData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/documents/user/{id}",
     *     tags={"Documents"},
     *     summary="Get document by id",
     *     description="Get document by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDocumentByIdAPI",
     *     @OA\Parameter(
     *         description="document id",
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
            $documentData = Document::where("id",$id)->with(['tasks' => function($query) {
                $query->with(['task_assignees']);
            }])->first();
            if (empty($documentData)) {
                return $this->responseException('Not found document', 404);
            }
            foreach ($documentData->tasks as $key => $task) {
                if ($task->status == 1) {
                    $task->update(['status' => 2]);
                }
                $checkExistAssignee = array_search($user['id'], array_column($task->task_assignees->toArray(), 'user_id'));
                if ($checkExistAssignee === false) {
                    unset($documentData->tasks[$key]);
                }
                $task->remaining_time = '';
                if ($task->deadline) {
                    $task->remaining_time = $this->calRemainingTime($task->deadline);
                }
            }
            return $this->responseSuccess($documentData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/documents/{id}",
     *     tags={"Documents"},
     *     summary="Update document API",
     *     description="Update document API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateDocumentAPI",
     *     @OA\Parameter(
     *         description="document id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Document schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
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
            $rules = Document::$updateRules;
            $input = $request -> all();

            $documentData = Document::where("id",$id)->first();
            if (empty($documentData)) {
                return $this->responseException('Not found document', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'document',
                'objectItem' => $documentData,
            ];
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('document', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $documentData->update($input);

                return $this->responseSuccess($documentData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/documents/{id}",
     *     tags={"Documents"},
     *     summary="Delete document API",
     *     description="Delete document API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteDocumentAPI",
     *     @OA\Parameter(
     *         description="document id",
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
            $documentData = Document::find($id);
            if (empty($documentData)) {
                return $this->responseException('Not found document', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'document',
                'objectItem' => $documentData,
            ];
            if ($documentData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('document', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Document', $documentData->id, $documentData->name, $documentData)) {
//                    $documentData->update(['delete_status' => 1]);
//                    $filePath = storage_path('app/uploads/' . $documentData['uri']);
//                    if ($filePath) {
//                        $path_parts = pathinfo($filePath);
//                        $file_file =  str_replace('documents/', '', $documentData['uri']);
//
//                        $zip = new \ZipArchive();
//                        $zip->open(storage_path( 'app/uploads/documents/' . $path_parts['filename'] . '.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
//                        $zip->addFile($filePath, $file_file);
//                        $zip->close();
//
//                        \File::delete($filePath);
//                    }
                    return $this->responseSuccess("Delete document success");
                }

                return $this->responseException('Delete failed!', 404);

//                $oldFilePath = storage_path('app/uploads/' . $documentData['uri']);
//                if ($oldFilePath) {
//                    File::delete($oldFilePath);
//                }
//                Document::destroy($id);
//                return $this->responseSuccess("Delete document success", 200);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function fileShow($fileName){
        try {
            //This method will look for the file and get it from drive
            $path = storage_path('app/uploads/documents/' . $fileName);
            $header = array(
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment',
                'filename' => $fileName,
            );

            // auth code
            return Response::download($path, $fileName, $header);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $input = $request -> all();
            $documentData = Document::where("id",$id)->first();
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'document',
                'objectItem' => $documentData,
            ];

            if (!$user = $this->getAuthorizedUser('document', 'process', 'updateTask', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                //Handle to update task
                $task = $input['tasks'];
                $oldTasks = Task::where('type', '=', 'Document')
                    ->where('type_id', $id)->with(['task_assignees'])->get();
                $this->processTaskByType('Document', $oldTasks, $task, $user);
//                foreach ($oldTasks as $oldTask) {
//                    $key = array_search($oldTask['id'], array_column($task, 'id'));
//                    if($key > -1){
//                        //update task
//                        $taskRules = Task::$updateRules;
//                        $taskData = Task::where("id", $oldTask -> id)->first();
//                        if (empty($taskData)) {
//                            return $this->responseException('Not found task', 404);
//                        }
//                        $taskValidator = Validator::make($task[$key], $taskRules);
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
                return $this->responseSuccess($documentData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/documents",
     *     tags={"Documents"},
     *     summary="Create new document",
     *     description="Create new document",
     *     security={{"bearerAuth":{}}},
     *     operationId="createDocument",
     *     @OA\RequestBody(
     *         description="Document schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function uploadMultiple(Request $request, Document $document)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Document::$rules;
//                $fileImageRules = Document::$fileImageRules;
                $input = $request -> all();
                if (empty($input['industry_id'])) {
                    $input['industry_id'] = $user['company']['industry_id'];
                }
                if (empty($input['added_by'])) {
                    $input['added_by'] = $user['id'];
                }
                $input['company_id'] = $user['company_id'];

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                // SECURITY - document with sharing options
                $listAdminInfo = User::whereRaw('FIND_IN_SET(?, users.company_id)', [$user['company_id']])
                    ->whereRaw('FIND_IN_SET(?, users.role_id)', 2)
                    ->get();

                if(!empty($files=$request->file('file'))){
                    $document_name = $input['name'];
                    foreach($files as $key=>$file){
//                        $fileValidator = Validator::make($file, $fileImageRules);
//                        if ($fileValidator->fails()) {
//                            $errors = ValidateResponse::make($fileValidator);
//                            return $this->responseError($errors);
//                        }
                        $input['name'] = $document_name . '_Checkpoint_' . $key;
                        $input['original_file_name'] = $file->getClientOriginalName();
                        $path = Storage::disk('private') -> putFile('documents', $file);
                        $input['uri'] = $path;
                        $input['report_question_id'] = $key;
                        $input['security_employee_array'] = null;
                        foreach ($listAdminInfo as $item_info) {
                            $input['security_employee_array'].= $item_info->id;
                        }
                        // Role Normal USER
                        if ($user['role_id'] > 2) { // only me + all Admin can view
                            $input['security_employee_array'] .= ',' . $input['added_by'];
                        }
                        Document::create($input);
                    }
                }

                return $this->responseSuccess(201);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
