<?php
namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Deviation;
use App\Models\Document;
use App\Models\ResponsibleProcessing;
use App\Models\DocumentAttachment;
use App\Models\DocumentNew;
use App\Models\DocumentOption;
use App\Models\TimeManagement;
use App\Models\Routine;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Responsible;
use App\Models\AttendeeProcessing;
use App\Models\ObjectItem;
use App\Models\Attendee;
use App\Models\ObjectOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Validator;
use JWTAuth;
use File;

/**
 * @OA\Tag(
 *     name="Documents New",
 *     description="Document New APIs",
 * )
 **/

class DocumentNewController extends Controller
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
                $object_type = $request->object_type;
                $object_id = $request->object_id;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $template = $request->template;
                 
                $result = DocumentNew::leftJoin('categories_new', 'documents_new.category_id', '=', 'categories_new.id')
                    ->leftJoin('users','documents_new.added_by', '=','users.id')
                    ->leftJoin('documents_attachments', 'documents_new.id', '=', 'documents_attachments.document_id')
                    ->leftJoin('documents_options', 'documents_new.id', '=', 'documents_options.document_id')
                    ->leftJoin('time_management', 'documents_new.task_id', '=', 'time_management.object_id')
                    ->leftJoin('security', 'documents_new.id', '=', 'security.object_id')
                    ->leftJoin('routines', 'documents_new.routine_id', '=', 'routines.id')
                    ->leftJoin('connect_to', 'documents_new.id', '=', 'connect_to.document_id');
                 
                if ($user->role_id === 1) {
                    $result = $result->where('documents_new.added_by', 1);
                    if ($object_type) {
                        $result = $result->where('documents_new.object_type', $object_type);
                    }
                } else {
                    // $result = $result->whereRaw('FIND_IN_SET(?, documents_new.industry_id)', [$user['company']['industry_id']])
                    //     ->where('documents_options.is_public', 1);
                    if ($type) {
                        if($user->company_id){
                            $result = $result->where('documents_new.company_id', $user->company_id);
                        }
                        // $result = $result->where(function ($q) use ($type) {
                        //     // created by super admin
                        //         $q->where('documents_new.added_by', 1)
                        //             ->where('documents_new.status', 1)
                        //             ->where('documents_new.type', '=', $type);
                        //     })
                        //     // created by login user
                        //     ->orWhere(function ($q) use ($user, $type) {
                        //         $q->where('documents_new.company_id', $user['company_id'])
                        //             ->where('documents_new.added_by', $user->id)
                        //             ->where('documents_new.type', '=', $type);
                        //     })
                        //     // created by others
                        //     ->orWhere(function ($q) use ($user, $type) {
                        //         $q->where('documents_new.company_id', $user['company_id'])
                        //             ->where('documents_new.type', '=', $type)
                        //             ->where('documents_new.added_by', '<>', $user->id)
                        //             // ------share to all employees
                        //             ->where(function ($q1) {
                        //                 $q1->where('documents_new.status', 1)
                        //                     ->where('documents_options.is_public', 1)
                        //                     ->where('documents_options.security_department_array', null)
                        //                     ->where('documents_options.security_employee_array', null);
                        //             })
                        //             ->orWhere(function ($q1) {
                        //                 $q1->where('documents_new.status', 1)
                        //                     ->where('documents_options.is_public', null);
                        //             })
                        //             // ------share to chosen employees / departments / both
                        //             // share to manager if document created by user (user choose to share)
                        //             ->orWhere(function ($q1) use ($user) {
                        //                 $q1->whereRaw('FIND_IN_SET(?, documents_options.security_department_array)', [$user->employee->department_id])
                        //                     ->orWhere(function ($q2) use ($user) {
                        //                         $q2->whereRaw('FIND_IN_SET(?, documents_options.security_employee_array)', [$user->id]);
                        //                     });
                        //             });
                        //     });

                        if ($object_type) {
                            $result = $result->where('documents_new.object_type', '=', $object_type);
                            if ($object_id) {
                                $result = $result->where('documents_new.object_id', $object_id);
                            }
                        }
                    } else if ($object_type){
                        // $result = $result->where(function ($q) use ($object_type) {
                        //     // created by super admin
                        //     $q->where('documents_new.added_by', 1)
                        //         ->where('documents_new.status', 1)
                        //         ->where('documents_new.object_type', '=', $object_type);
                        // })
                        //     // created by others
                        //     ->orWhere(function ($q) use ($user, $object_type) {
                        //         $q->where('documents_new.company_id', $user['company_id'])
                        //             ->where('documents_new.object_type', '=', $object_type)
                        //             ->where('documents_new.added_by', '<>', $user->id)
                        //             // ------share to all employees
                        //             ->where(function ($q1) {
                        //                 $q1->where('documents_new.status', 1)
                        //                     ->where('documents_options.is_public', 1)
                        //                     ->where('documents_options.security_department_array', null)
                        //                     ->where('documents_options.security_employee_array', null);
                        //             })
                        //             ->orWhere(function ($q1) {
                        //                 $q1->where('documents_new.status', 1)
                        //                     ->where('documents_options.is_public', null);
                        //             })
                        //             // ------share to chosen employees / departments / both
                        //             // share to manager if document created by user (user choose to share)
                        //             ->orWhere(function ($q1) use ($user) {
                        //                 $q1->whereRaw('FIND_IN_SET(?, documents_options.security_department_array)', [$user->employee->department_id])
                        //                     ->orWhere(function ($q2) use ($user) {
                        //                         $q2->whereRaw('FIND_IN_SET(?, documents_options.security_employee_array)', [$user->id]);
                        //                     });
                        //             });
                        //     })
                        //     // created by login user
                        //     ->orWhere(function ($q) use ($user, $object_type) {
                        //         $q->where('documents_new.company_id', $user['company_id'])
                        //             ->where('documents_new.added_by', $user->id)
                        //             ->where('documents_new.object_type', '=', $object_type);
                        //     });
                        if ($object_id) {
                            $result = $result->where('documents_new.object_id', '=', $object_id);
                        }
                    }

                    // if ($user->role_id !== 2) {
                    //    $result = $result->where(function ($q) use ($user) {
                    //        // ------share to all employees
                    //        $q->where('documents_options.security_department_array', null)
                    //            ->where('documents_options.security_employee_array', null)
                    //            // ------share to chosen employees / departments / both
                    //            // share to manager if document created by user (user choose to share)
                    //            ->orWhere(function ($q1) use ($user) {
                    //                $q1->whereRaw("JSON_CONTAINS(documents_options.security_department_array, ?)", [$user->employee->department_id])
                    //                    ->orWhere(function ($q2) use ($user) {
                    //                        $q2->whereRaw("JSON_CONTAINS(documents_options.security_employee_array, ?)", [$user->id]);
                    //                    });
                    //            });
                    //    })->orWhere('documents_options.is_public', null);
                    // }
                }
                
                $datanew = $result->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                    ->where('documents_new.delete_status', 0);

                    $datanew->select('documents_new.*', 'users.email as added_by_email',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories_new.name as category_name', 'time_management.start_date as start_date','time_management.deadline as deadline','routines.start_time as routine_startdate',
                        'routines.deadline as routine_deadline','connect_to.connect_to_source as connect_to_source',
                        'documents_attachments.original_file_name as original_file_name',
                        'documents_attachments.file_size as file_size',
                        'documents_attachments.uri as uri','documents_options.security_department_array', 'documents_options.security_employee_array', 'documents_options.is_public')
                        ->with('security')->orderBy('id','desc'); 

                    if (isset($template) && $template == 1) {
                        $datanew->where('documents_new.is_template', 1);
                    }else{
                        $datanew->where('documents_new.is_template', 0);
                    }

                    if(isset($request->by_name) && !empty($request->by_name)){
                        $datanew->where('documents_new.name','Like', '%' .$request->by_name .'%' );
                        $datanew->orWhere('categories_new.name','Like', '%' .$request->by_name .'%' );

                        if (isset($template) && $template == 1) {
                            $datanew->where('documents_new.is_template', 1);
                        }else{
                            $datanew->where('documents_new.is_template', 0);
                        }
                    }

                    $result = $datanew->distinct()->get()->toArray();
                    // $result = $datanew->paginate(10);

                if(isset($result) && !empty($result)){
                    foreach ($result as $key => $item) {
                        $item['count_related_object'] = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = DocumentNew::where('parent_id', $item['id'])->count();
                            
                            if ($countRelatedObject > 0) {
                                $result[$key]['count_related_object'] = $countRelatedObject;
                            }
                        }
                        $baseUrl = config('app.app_url'); 
                        if(isset($item['task_id']) && !empty($item['task_id'])){
                            $task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.id', $item['task_id'])
                            ->where('objects.type', 'task')
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            if(isset($task_data) && !empty($task_data)){
                                $result[$key]['responsible'] = $this->getObjectDetailInfo($task_data, $user)['responsible']; 
                            }
                        }

                        if(isset($item['routine_id']) && !empty($item['routine_id'])){
                            $routine_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.id', $item['routine_id'])
                            ->where('objects.type', 'routine')
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            if(isset($routine_data) && !empty($routine_data)){
                                $result[$key]['responsible'] = $this->getObjectDetailInfo($routine_data, $user)['responsible']; 
                            }
                        }

                        $result[$key]['security_employee_array'] = !empty($item['security_employee_array']) ? json_decode($item['security_employee_array']) : '';
                        $result[$key]['security_department_array'] = !empty($item['security_department_array']) ? json_decode($item['security_department_array']) : '';
                        if ($item['uri']) {
                            if ($item['category_id'] == 9) {
                                $result[$key]['url'] = $baseUrl . "image/".  $item['uri']; 
                            } else {
                                $result[$key]['url'] = $baseUrl . "/uploads/attachments/".  $item['uri']; 
                            }
                        }
                        if (!empty($item['task_id'])) {
                            $result[$key]['deadline'] = date("Y-m-d", $item['deadline']);
                            $result[$key]['start_date'] = date("Y-m-d", $item['start_date']);
                        }
                        if (!empty($item['routine_id'])) {
                            $object_routine_data = ObjectItem::where('id', $item['routine_id'])->with('routine')->first();
                            if (isset($object_routine_data->routine->start_time) && !empty($object_routine_data->routine->start_time)) {
                                $result[$key]['start_date'] = date("Y-m-d", $object_routine_data->routine->start_time);
                            }
                            if (isset($object_routine_data->routine->deadline) && !empty($object_routine_data->routine->deadline)) {
                                $result[$key]['deadline'] = date("Y-m-d", $object_routine_data->routine->deadline);
                            }
                        }
                        if(!in_array($user->id, Helper::checkDocumentDisplayAccess($item))) {
                            unset($result[$key]);
                        }
                    }

                    $options = [
                        'path' => url('api/v1/documentsNew')
                    ];

                    $result = $this->paginate($result, 10, null, $options);

                    // return $this->responseSuccess($result);
                    return $result;
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function attachments(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('document', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $type = $request->type;
                $object_type = $request->object_type;
                $object_id = $request->object_id;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $template = $request->template;
                 
                $result = DocumentNew::leftJoin('categories_new', 'documents_new.category_id', '=', 'categories_new.id')
                    ->leftJoin('users','documents_new.added_by', '=','users.id')
                    ->leftJoin('documents_attachments', 'documents_new.id', '=', 'documents_attachments.document_id')
                    ->leftJoin('documents_options', 'documents_new.id', '=', 'documents_options.document_id')
                    ->leftJoin('time_management', 'documents_new.task_id', '=', 'time_management.object_id')
                    ->leftJoin('security', 'documents_new.id', '=', 'security.object_id')
                    ->leftJoin('routines', 'documents_new.routine_id', '=', 'routines.id')
                    ->leftJoin('connect_to', 'documents_new.id', '=', 'connect_to.document_id');
                 
                if ($user->role_id === 1) {
                    $result = $result->where('documents_new.added_by', 1);
                    if ($object_type) {
                        $result = $result->where('documents_new.object_type', $object_type);
                    }
                } else {
                    if ($type) {
                        if($user->company_id){
                            $result = $result->where('documents_new.company_id', $user->company_id);
                        }

                        if ($object_type) {
                            $result = $result->where('documents_new.object_type', '=', $object_type);
                            if ($object_id) {
                                $result = $result->where('documents_new.object_id', $object_id);
                            }
                        }
                    } else if ($object_type){
                        if ($object_id) {
                            $result = $result->where('documents_new.object_id', '=', $object_id);
                        }
                    }
                }
                
                $datanew = $result->with(['tasks' => function($query) {
                    $query->with(['task_assignees']);
                }])
                    ->where('documents_new.delete_status', 0);

                    $datanew->select('documents_new.*', 'users.email as added_by_email',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories_new.name as category_name', 'time_management.start_date as start_date','time_management.deadline as deadline','routines.start_time as routine_startdate',
                        'routines.deadline as routine_deadline','connect_to.connect_to_source as connect_to_source',
                        'documents_attachments.original_file_name as original_file_name',
                        'documents_attachments.file_size as file_size',
                        'documents_attachments.uri as uri','documents_options.security_department_array', 'documents_options.security_employee_array', 'documents_options.is_public')
                        ->with('security')->orderBy('id','desc'); 

                    if (isset($template) && $template == 1) {
                        $datanew->where('documents_new.is_template', 1);
                    }else{
                        $datanew->where('documents_new.is_template', 0);
                    }

                    if(isset($request->by_name) && !empty($request->by_name)){
                        $datanew->where('documents_new.name','Like', '%' .$request->by_name .'%' );
                        $datanew->orWhere('categories_new.name','Like', '%' .$request->by_name .'%' );

                        if (isset($template) && $template == 1) {
                            $datanew->where('documents_new.is_template', 1);
                        }else{
                            $datanew->where('documents_new.is_template', 0);
                        }
                    }

                    $result = $datanew->distinct()->get()->toArray();

                if(isset($result) && !empty($result)){
                    foreach ($result as $key => $item) {
                        $item['count_related_object'] = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = DocumentNew::where('parent_id', $item['id'])->count();
                            
                            if ($countRelatedObject > 0) {
                                $result[$key]['count_related_object'] = $countRelatedObject;
                            }
                        }
                        $baseUrl = config('app.app_url'); 
                        if(isset($item['task_id']) && !empty($item['task_id'])){
                            $task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.id', $item['task_id'])
                            ->where('objects.type', 'task')
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            if(isset($task_data) && !empty($task_data)){
                                $result[$key]['responsible'] = $this->getObjectDetailInfo($task_data, $user)['responsible']; 
                            }
                        }

                        if(isset($item['routine_id']) && !empty($item['routine_id'])){
                            $routine_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.id', $item['routine_id'])
                            ->where('objects.type', 'routine')
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            if(isset($routine_data) && !empty($routine_data)){
                                $result[$key]['responsible'] = $this->getObjectDetailInfo($routine_data, $user)['responsible']; 
                            }
                        }

                        $result[$key]['security_employee_array'] = !empty($item['security_employee_array']) ? json_decode($item['security_employee_array']) : '';
                        $result[$key]['security_department_array'] = !empty($item['security_department_array']) ? json_decode($item['security_department_array']) : '';
                        if ($item['uri']) {
                            if ($item['category_id'] == 9) {
                                $result[$key]['url'] = $baseUrl . "image/".  $item['uri']; 
                            } else {
                                $result[$key]['url'] = $baseUrl . "/uploads/attachments/".  $item['uri']; 
                            }
                        }
                        if (!empty($item['task_id'])) {
                            $result[$key]['deadline'] = date("Y-m-d", $item['deadline']);
                            $result[$key]['start_date'] = date("Y-m-d", $item['start_date']);
                        }
                        if (!empty($item['routine_id'])) {
                            $object_routine_data = ObjectItem::where('id', $item['routine_id'])->with('routine')->first();
                            if (isset($object_routine_data->routine->start_time) && !empty($object_routine_data->routine->start_time)) {
                                $result[$key]['start_date'] = date("Y-m-d", $object_routine_data->routine->start_time);
                            }
                            if (isset($object_routine_data->routine->deadline) && !empty($object_routine_data->routine->deadline)) {
                                $result[$key]['deadline'] = date("Y-m-d", $object_routine_data->routine->deadline);
                            }
                        }
                        if(!in_array($user->id, Helper::checkDocumentDisplayAccess($item))) {
                            unset($result[$key]);
                        }
                    }
                    
                    $result = array_filter($result, function($value) {
                        return $value['type'] == 'attachment' && $value['type_of_attachment'] == 1;
                    });

                    $options = [
                        'path' => url('api/v1/documentsNew/attachments')
                    ];

                    $result = $this->paginate($result, 10, null, $options);
                    return $result;
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $current_items = array_slice($items, $perPage * ($page - 1), $perPage);
        $paginator = new LengthAwarePaginator($current_items, count($items), $perPage, $page, $options);
        $paginator->appends(request()->all());
        return $paginator;
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
            $input['connectToArray'] = @json_decode(@$input['connectToArray'], true);
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('document', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
             
                $rules = DocumentNew::$rules;
                $fileRules = DocumentNew::$fileRules;
                $companyData = Company::where("id", $user['company_id'])->first();
                if ($user->role_id > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
                if (empty($input['added_by'])) {
                    $input['added_by'] = $user['id'];
                }
                if(!empty($input['status']) && $input['status'] == "new"){
                    $input['status'] = 1; 
                }
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                if (!empty($request->file('file'))) {
                    $input['type'] = 'attachment'; 
                } 
              
               
                // create new doc / get old file path to delete existed doc
                if(!empty($input['category_name']) && $input['category_name'] == 'Avatar') { // employee avatar
                    $employeeDetail = User::find($input['object_id']);
                    if ($employeeDetail['avatar'] == null) { // upload new avatar for 1st time
                        $newDocument = DocumentNew::create($input);
                    } else { // update existed avatar
                        $oldFilePath = null;
                        $newDocAttachment = DocumentAttachment::leftJoin('documents_new', 'documents_attachments.document_id', '=', 'documents_new.id')
                            ->where('documents_new.object_type', 'employee')
                            ->where('documents_new.object_id', $input['object_id'])
                            ->where('documents_new.category_id', $input['category_id'])
                            ->first();
                        $oldFilePath = public_path() . '/uploads/attachments/' . $newDocAttachment['uri'];
                        if ($oldFilePath) {
                            File::delete($oldFilePath); // delete previous file in storage folder
                        }
                        $newDocument = DocumentNew::find($newDocAttachment['document_id']);
                    }
                } else if (!empty($input['category_name']) && $input['category_name'] == 'Logo') { // company logo
                    if (empty($input['attachment_updated'])) { // upload new logo for 1st time
                        $newDocument = DocumentNew::create($input);
                    } else { // update existed logo
                        $oldFilePath = null;
                        $newDocAttachment = DocumentAttachment::leftJoin('documents_new', 'documents_attachments.document_id', '=', 'documents_new.id')
                            ->where('documents_new.company_id', $user['company_id'])
                            ->where('documents_new.category_id', $input['category_id'])
                            ->first();
                        $oldFilePath = public_path() . '/uploads/attachments/' . $newDocAttachment['uri'];
                        if ($oldFilePath) {
                            File::delete($oldFilePath); // delete previous file in storage folder
                        }
                        $newDocument = DocumentNew::find($newDocAttachment['document_id']);
                    }
                } else { 
                   
                    // Check if NEW document
                    if (empty($input['attachment_updated'])) {
                     
                        $newDocument = DocumentNew::create($input);

                        if((isset($input['file']) && isset($input['old_file'])) && $input['file'] == $input['old_file']) {
                            $newDocumentAttachment = DocumentAttachment::where('document_id', $newDocument->parent_id)->first();

                            DocumentAttachment::create([
                                "document_id" => $newDocument->id,
                                "original_file_name" => $newDocumentAttachment->original_file_name ?? null,
                                "file_size" => $newDocumentAttachment->file_size ?? 0.00,
                                "file_extension" => $newDocumentAttachment->file_extension ?? null,
                                "uri" => $newDocumentAttachment->uri ?? 0.00,
                            ]);
                        }
                         $this->addConnectToObject($user, $newDocument['id'], 'document', $input['connectToArray']);
                        if ($newDocument && $user['role_id'] == 1 && $input['object_type'] != 'help center') {
                            $this->pushNotificationToAllCompanies('Document', $newDocument['id'], $newDocument['name'],'create');
                        }
                        
                    } else { // existed document
                       
                        $oldFilePath = null;
                        $newDocument = DocumentNew::find($input['id']);
                        $newDocAttachment = DocumentAttachment::where('document_id', $input['id'])->first();
                      
                        // check if exist help center attachment in document list
                        if (!empty($input['object_type']) && $input['object_type'] == 'help center') {
                            $oldFilePath = public_path() . '/uploads/attachments/' . $newDocAttachment['uri'];
                        }
                        // check exist attachments
                        else if(!empty($input['type_of_attachment'])) {
                            if (isset($newDocAttachment['uri'])) {
                                $oldFilePath = storage_path('app/uploads/' . $newDocAttachment['uri']);
                                // when edit -> if changed type from Attachment to Only note => update uri = null
                                if ($input['type_of_attachment'] == 2 && $newDocAttachment['uri']) {
                                    DocumentAttachment::destroy($newDocAttachment['id']);
                                }
                            }
                        }
                        else if (empty($newDocument)) {
                            return $this->responseException('Not found document', 404);
                        }
                        $newDocument->update($input);
                        if ($oldFilePath) {
                            File::delete($oldFilePath); // delete previous file in storage folder
                        }
                    }
                }
                

               
                if (empty($request->file('file'))) {
                    $input['security_employee_array'] = !empty($input['employee_array']) ? ($input['employee_array']) : '';
                    $input['security_department_array'] = !empty($input['department_array']) ? ($input['department_array']) : '';
                }else{
                    
                    $input['security_employee_array'] = !empty($input['employee_array']) ? ($input['employee_array']) : '';
                    $input['security_department_array'] = !empty($input['department_array']) ? ($input['department_array']) : '';
                }
                
                // DocumentOption table - SECURITY - document sharing
                $inputOption = $input;
                $inputOption['document_id'] = $newDocument['id'];
                // normal user choose Shared to their manager department
                // if (!empty($input['show_manager'])) {
                //     $employees = Employee::where('department_id', $user->employee->department_id)
                //     ->with(['jobTitle'])->get();
                //     if (!empty($employees)) {
                //         $inputOption['security_employee_array'] = null;
                //         foreach ($employees as $employee) { 
                //             if (!empty($employee->jobTitle) && $employee->jobTitle->role_name == 'Manager') {
                //                 $inputOption['security_employee_array'] .= $employee['user_id'];
                //             }
                //         }
                //     }
                // }
                
                //old logic
//                if (!$input['is_renewed']) {
//                    $inputOption['deadline'] = null;
////                    $inputOption['recurring'] = 'indefinite';
//                } else {
//                    if (!empty($input['deadline'])) {
//                        $inputOption['deadline'] = strtotime($input['deadline']);
////                        $inputOption['recurring'] = $input['recurring'];
//                    } else {
//                        $inputOption['deadline'] = null;
////                        $inputOption['recurring'] = 'indefinite';
//                    }
//                }
                //---
               
                $check_existed = DocumentOption::where('document_id', $inputOption['document_id'])->first(); 
                if (empty($check_existed)) {
                    if (empty($input['is_public'])) { // if not logo/ avatar
                        $inputOption['is_public'] = null;
                    }
                    if (!empty($input['deadline'])) {
                        $inputOption['deadline'] = strtotime($input['deadline']);
                    } 
                    DocumentOption::create($inputOption);
                } else {
                    $check_existed->update($inputOption);
                }   
                // DocumentAttachment table
                $baseUrl = config('app.app_url');
                $inputAttachment = $input;
                $inputAttachment['document_id'] = $newDocument['id'];
                // If upload with FILE
                if (!empty($request->file('file'))) {
                    $fileValidator = Validator::make($input, $fileRules);
                    if ($fileValidator->fails()) {
                        $errors = ValidateResponse::make($fileValidator);
                        return $this->responseError($errors);
                    }

                    if (!empty($input['private_document'])) {
                        if ($user->role_id === 1) {
                            $path = Storage::disk('private')->putFile('documents/super_admin', $request->file('file'));
                        } else {
                            $path = Storage::disk('private')->putFile('documents/' . $input['company_id'], $request->file('file'));
                        }
                    } else { // if avatar / logo -> save document in public folder
                        if ($user->role_id === 1) {
                            $path = Storage::disk('public')->putFile('/super_admin', $request->file('file'));
                        } else {
                            $path = Storage::disk('public')->putFile('/' . $input['company_id'], $request->file('file'));
                        }
                    }
                    
                    $inputAttachment['uri'] = $path;
                    if ($input['file_size']) {
                        $inputAttachment['file_size'] = round($input['file_size'] / 1024,2); //convert byte to KB
                    }
                    $inputAttachment['file_extension'] = substr($input['original_file_name'], strpos($input['original_file_name'], ".") + 1);
                }
                else if(!empty($request->old_file)){
                    $inputAttachment['uri'] = $baseUrl. "/uploads/attachments/".$request->old_file ;
                    $newDocumentAttachment['url'] = $inputAttachment['uri'];
                }
              
                // create new document with file applied from resource
                if (!empty($input['applied_document'])) {
                    $new_uri = 'documents/' . uniqid() . '_' . $input['original_file_name'];
                    File::copy(storage_path('app/uploads/' . $input['uri']), storage_path('app/uploads/' . $new_uri));
                    $inputAttachment['uri'] = $new_uri;
                } 
                if (!empty($inputAttachment['file_size']) && $inputAttachment['file_size'] > 0) { 
                    $newDocumentAttachment = DocumentAttachment::where('document_id', $inputAttachment['document_id'])->first(); 
                    if (empty($newDocumentAttachment)) {
                        $newDocumentAttachment = DocumentAttachment::create($inputAttachment);
                    } else {
                        $newDocumentAttachment->update($inputAttachment);
                    }
                }
               
                // Create task for renewed attachment
                if (!empty($input['renewed_employee_array'])) {
                    $currentRenewedEmployeesResponsible = explode(',', $input['renewed_employee_array']);
                    $deadline = date_format(date_create($newDocument->deadline), 'd.m.Y');
                    $this->pushNotification($user['id'], $user['company_id'], 2, $currentRenewedEmployeesResponsible, 'document', 'Document', $newDocument['id'], $newDocument['name'], 'reminder', null, $deadline);
                } else {
                    $currentRenewedEmployeesResponsible = null;
                }

               
                if (!empty($request->file('file')) && !empty($input['is_public'])) {
                    // $newDocumentAttachment['url'] = $baseUrl. "/api/v1/uploads/".  $inputAttachment['uri']; // url for other documents uploaded with file
                    $newDocumentAttachment['url'] = $baseUrl. "/uploads/attachments/".  $inputAttachment['uri']; // url for other documents uploaded with file
                } else if (!empty($request->file('file')) && empty($input['is_public'])) {
                    $newDocumentAttachment['url'] = $baseUrl. "/api/v1/image/".  $inputAttachment['uri']; // url for show avatar & logo
                }
                 
                // update company's logo url
                if (!empty($input['category_name']) && $input['category_name'] == 'Logo') {
                    if (empty($companyData)) {
                        return $this->responseException('Not found company', 404);
                    }
                    $companyData->update(['logo' => $newDocumentAttachment['url']]);
                }
               
                // update user's avatar url
                if (!empty($input['category_name']) && $input['category_name'] == 'Avatar') {
                    $userData = User::where("id", $input['object_id'])->first();
                    if (empty($userData)) {
                        return $this->responseException('Not found user', 404);
                    }
                    $userData->update(['avatar' => $newDocumentAttachment['url']]);
                }
             
                // update deviation's attachment url
                if (!empty($input['object_type']) && $input['object_type'] == 'deviation') {
                    $deviationData = Deviation::where("id", $input['object_id'])->first();
                    if (empty($deviationData)) {
                        return $this->responseException('Not found deviation', 404);
                    }
                    $deviationData->update(['attachment' => $newDocumentAttachment['url']]);
                }
              
                
                if (!empty($input['type']) && $input['type'] == 'task' || $input['type'] == 'routine') {
                    if(!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])){
                        $input['responsible_employee_array'] = array($user->id);
                    }else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if(empty($input['responsible_employee_array'])){
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if(empty($input['responsible_employee_array'])){
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }   
                   
                    $newObject = $this->createTaskObject($input, $user);
                    $newObject['object_type'] = 'task';
                    $this->createSecurityObject($newObject, $input);
                   
                    if(!empty($newObject)){
                        $newDocument = DocumentNew::find($newDocument->id);
                        $detil = ([
                            'task_id'=>$newObject['id']
                        ]);
                        $newDocument->update($detil);
                    }

                    if($input['is_routine'] == true){
                        if (!empty($input['start_time'])) {
                            $newinput['start_time'] = ($input['start_time']);
                        } else {
                            // $newinput['start_time'] = strtotime("today");
                        }
                        if (!empty($input['deadline'])) {
                            $newinput['deadline'] = $input['deadline'];
                        }
                        if (!empty($input['start_date'])) {
                            $newinput['start_date'] = ($input['start_date']); 
                        }
                        $input['added_by'] = $user['id'];
                        if ($user->role_id > 1) {
                            $input['industry_id'] = $user['company']['industry_id'];
                            $input['company_id'] = $user['company_id'];
                        }
                        if (!empty($user->employee->nearest_manager)) {
                            $input['responsible'] = $user->employee->nearest_manager;
                            if(empty($input['responsible_employee_array'])){
                                $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                            }
                        } else {
                            $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                            if ($companyAdmin) {
                                $input['responsible'] = $companyAdmin->id;
                                if(empty($input['responsible_employee_array'])){
                                    $input['responsible_employee_array'] = (array($companyAdmin->id));
                                }
                            }
                        }   
                        $rules = Routine::$rules;
                        $finput = $input; 
                         
                        $input = $this->getRoutineData($input);
                        $input['type'] = 'routine';
                        if ($user['role_id'] > 1) {
                            $input['industry_id'] = $user['company']['industry_id'];
                            $input['company_id'] = $user['company_id'];
                        } else {
                            $input['company_id'] = null;
                        }
                        $input['added_by'] = $user['id']; 
    
                        $validator = Validator::make($input, $rules);
                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($validator);
                            return $this->responseError($errors, 400);
                        } 
                        $newRoutine = Routine::create($input);
                        $finput['source'] = 'routine';
                        $finput['type'] = 'routine';
                        $finput['source_id'] = $newRoutine->id;
                        $finput['start_time'] = $newinput['start_time'] ?? '';
                        $finput['deadline'] = $newinput['deadline'] ?? '';
                        $finput['responsible_employee_array'] = $finput['responsible_employee_array'] ?? '';
                        $finput['attendee_employee_array'] = $finput['attendee_employee_array'] ?? '';
    
                        if (!empty($input['start_time'])) {
                            $newinput['start_time'] = ($input['start_time']);
                        }  
                        if (!empty($input['deadline'])) {
                            $newinput['deadline'] = $input['deadline'];
                        }
                        if (!empty($input['start_date'])) {
                            $newinput['start_date'] = ($input['start_date']); 
                        }
                        
                        $newObject2 = $this->createObject($finput, $user);  
                        $this->createSecurityObject($newRoutine, $input);
                        if(!empty($newObject2)){
                            $m_obj = DocumentNew::where('id',$newDocument->id)->update([
                                'routine_id'=>$newObject2['id']
                            ]); 
                        } 
                    }
                }
                
                $newDocument = DocumentNew::find($newDocument->id);
             
                // Handle to save Security/ Connect to
                // $return = $this->createSecurityObject($newDocument, $input);
                // return response()->json([
                //     'asd'=>$return
                // ]);
                return $this->responseSuccess([
                    'data'=>$newDocument
                ]);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function getRoutineData($input)
    {
        $inputRoutine['name'] = $input['name'] ?? '';
        $inputRoutine['description'] = $input['description'] ?? '';
        $inputRoutine['status'] = $input['status'] ?? '';
        $inputRoutine['category_id'] = $input['category_id'] ?? '';
        $inputRoutine['deadline'] = $input['deadline'] ?? '';
        $inputRoutine['start_date'] = $input['start_date'] ?? '';
        $inputRoutine['is_template'] = $input['is_template'] ?? '';
        $inputRoutine['is_public'] = $input['is_public'] ?? '';
        $inputRoutine['parent_id'] = $input['parent_id'];
        $inputRoutine['is_suggestion'] = $input['is_suggestion'] ?? '';
        $inputRoutine['attendingEmpsArray'] = [];
        $inputRoutine['recurring_type'] = $input['recurring_type'] ?? '';
        $inputRoutine['isDefaultResponsible'] = $input['isDefaultResponsible'] ?? '';
        $inputRoutine['isDefaultAttendee'] = $input['isDefaultAttendee'] ?? '';
        $inputRoutine['type'] = $input['type'] ?? '';
        $inputRoutine['object_type'] = $input['object_type'] ?? '';
        $inputRoutine['is_shared'] = $input['is_shared'] ?? '';
        $inputRoutine['duration'] = $input['duration'] ?? '';
        $inputRoutine['is_duration'] = $input['is_duration'] ? 'true' : 'false';

        if((isset($input['department_array']) && !empty($input['department_array'])) && gettype($input['department_array']) == 'string') { 
            $inputRoutine['department_array'] = json_decode($input['department_array']);
        }
        if((isset($input['employee_array']) && !empty($input['employee_array'])) && gettype($input['employee_array']) == 'string') {
            $inputRoutine['employee_array'] = json_decode($input['employee_array']);
        }


        if ($input['is_template'] || empty($input['attending_emps'])) {
            $inputRoutine['attending_emps'] = null;
        } else {
            $inputRoutine['attendingEmpsArray'] = $input['attending_emps'];
            $inputRoutine['attending_emps'] = json_encode($input['attending_emps']);
        }
        if ($input['is_template'] || empty($input['attending_contact'])) {
            $inputRoutine['attending_contact'] = null;
        } else {
            $inputRoutine['attending_contact'] = json_encode($input['attending_contact']);
        }
        if (!empty($input['is_template'])) {
            $inputRoutine['responsible_id'] = null;
            $inputRoutine['attendings_count'] = 0;
        } else {
            if (!empty($input['responsible_id'])) {
                $inputRoutine['responsible_id'] = $input['responsible_id'];
            }
            if(!empty($input['attending_emps']) && !empty($input['attending_contact'])){
                $inputRoutine['attendings_count'] = count($input['attending_emps']) + count($input['attending_contact']);
            }else{
                $inputRoutine['attendings_count'] = 0;
            }
        }

        // Handle to save Reminder/ start date - due date
        $inputRoutine['is_activated'] = $input['is_activated'];
        if (!empty($input['start_time'])) {
            $inputRoutine['start_time'] =  strtotime($input['start_time']  );
        } else {
            // $inputRoutine['start_time'] = strtotime("today");
        }
        if (!$input['is_activated']) {
            $inputRoutine['deadline'] = null;
            $inputRoutine['recurring'] = 'indefinite';
        } else {
            if (!empty($input['deadline'])) {
                $inputRoutine['deadline'] = strtotime($input['deadline']);
                $inputRoutine['recurring'] = !empty($input['recurring']) ? ucfirst($input['recurring']) : '';
            } else {
                $inputRoutine['deadline'] = null;
                $inputRoutine['recurring'] = 'indefinite';
            }
        } 
        // return !$input['is_activated'];
        $inputRoutine['is_attending_activated'] = $input['is_attending_activated'];

        return $inputRoutine;
    }


    private function createObject($input, $user)
    { 
        $inputTemp = $input;

        if ($user->role_id > 1) {
            $input['company_id'] = $user['company_id'];
            $input['industry'] = json_encode($user['company']['industry_id']);
        } else {
            $input['industry'] = json_encode($input['industry']);
        }
        $input['added_by'] = $user['id'];
        //                $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', $input['type'], $input['name']));

        $rules = ObjectItem::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $newObject = ObjectItem::create($input);
         

        if (!empty($input['connectToArray'])) {
            $this->addConnectToObject($user, $newObject['id'], $newObject['type'], $input['connectToArray']);
        }

        if (($newObject['type'] == 'instruction' || $newObject['type'] == 'risk' || $newObject['type'] == 'risk-analysis') && !$newObject['is_template']) {
            // Handle to save Security
            $this->createSecurityObject($newObject, $input);
        } 
        // if object is NOT a Resource
        if (!$newObject['is_template']) {
            // Responsible
            // if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
            //   $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
            // }
            if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) { 
                $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
                // if (!empty($inputTemp['responsible_required_comment']) && $inputTemp['responsible_required_comment'] || !empty($inputTemp['responsible_required_attachment']) && $inputTemp['responsible_required_attachment']) {
                $newObject->processing = $this->createObjectResponsibleProcessing($newObject->responsible, $user, false, $input);
                // }
            }
            
            // Attendee
            // $this->createObjectAttendee($inputTemp, $newObject, $user);
            if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
                $newObject->attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

                // Attendee processing
                $newObject->processing = $this->createObjectAttendeeProcessing($newObject->attendee, $user);
            }

            // Time management
            $newObject->time = $this->createObjectTimeManagement($inputTemp, $newObject, $user);

            // APPLY object - update number of used time
            if (!empty($inputTemp['apply_object_id'])) {
                $this->countObjectResourceUsedTime($inputTemp['apply_object_id']);
            }
            if (!empty($inputTemp['resource_id'])) {
                $this->countObjectResourceUsedTime($inputTemp['resource_id']);
            }
        }

        // Risk element
        if ($newObject['type'] == 'risk' || ($newObject['type'] == 'instruction' && $newObject['is_template'])) {
            $newObject->object_option = $this->createObjectOption($inputTemp, $newObject);
        }

        // Risk analysis
        if ($newObject['type'] == 'risk-analysis') {
            $this->addRiskElementToRiskAnalysis($input['risk_element_array'], $newObject);
            if (!empty($input['source_of_danger'])) {
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user);
            }
        }

        // Task of Risk analysis
        if ($input['type'] == 'task' && $input['source'] == 'risk-analysis') {
            if (!empty($input['source_of_danger'])) {
                $riskAnalysisObject = ObjectItem::find($input['source_id']);
                if (!empty($riskAnalysisObject)) {
                    $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $riskAnalysisObject, $user, true);
                }
            }
        }

        if ($input['type'] == 'task' && $input['source'] == 'deviation') {
            if (!empty($input['source_of_danger'])) {
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user, true);
            }
        }
        if ($input['type'] == 'risk-analysis' && $input['source'] == 'deviation') {
            if (!empty($input['source_of_danger'])) {
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user, true);
            }
        }

        return $newObject;
    }


    private function createObjectResponsibleProcessing($responsible, $user, $requestEdit = false, $inputData = false)
    {
        if ($requestEdit) {
            ResponsibleProcessing::where('responsible_id', $responsible['id'])->delete();
        }
        // $resp = Responsible::where('id',$responsible->id)->first();
        $input['company_id'] = $user['company_id'];
        $input['attendee_id'] = $responsible['id'];
        $start_time = $inputData['start_time'] ?? '';
        $start_date = $inputData['start_date'] ?? '';
       
        if ($start_date && $start_time) {
            $theDay = Carbon::make($start_date . ' ' . $start_time);
            $theDay->isToday();
            $theDay->isPast();
            $theDay->isFuture();
            if ($theDay->isPast() == true) {
                $task_status = 'pending';
            } else if ($theDay->isToday() == true) {
                $task_status = 'ongoing';
            } else if ($theDay->isTomorrow() == true) {
                $task_status = 'new';
            } else if ($theDay->isFuture() == true) {
                $task_status = 'pending';
            }
        } else {
            $task_status = 'new';
        }
        $input['status'] = $task_status;
        $list = json_decode($responsible['employee_array']);
        $result = [];
        
        foreach ($list as $item) {
            $input['added_by'] = $item;
            $rules = ResponsibleProcessing::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            $exist = ResponsibleProcessing::where(['attendee_id' => $responsible['id'], 'added_by' => $item])->first();
            
            if (!empty($exist)) {
                ResponsibleProcessing::where(['attendee_id' => $responsible['id'], 'added_by' => $item])->update([
                    "company_id" => $input["company_id"],
                    "attendee_id" => $input["attendee_id"],
                    "status" => $input["status"],
                    "added_by" => $input["added_by"]
                ]);
                $newdata = ResponsibleProcessing::where(['attendee_id' => $responsible['id'], 'added_by' => $item])->first();
                $result[] = $newdata;
            } else {
                ResponsibleProcessing::create($input);
                $newdata = ResponsibleProcessing::where(['attendee_id' => $responsible['id'], 'added_by' => $item])->first();
                $result[] = $newdata;
            }
            // $result[] = ResponsibleProcessing::updateOrCreate(['attendee_id'=>$responsible['id'],'added_by'=>$item],[$input]); 
        }
        return $result;
    }

    private function countObjectResourceUsedTime($objectId)
    {
        $obj = ObjectOption::where('object_id', $objectId)->first();
        if (empty($obj)) {
            return $this->responseException('Not found resource', 404);
        }
        $obj->update([
            'number_used_time' => $obj['number_used_time'] + 1,
        ]);
    }

    private function createObjectTimeManagement($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];

        if (!empty($inputObject['start_time'])) {
            $input['start_time'] = strtotime($inputObject['start_time']);
        }
        if (!empty($inputObject['end_time'])) {
            $input['end_time'] = strtotime($inputObject['end_time']);
        }
        if (!empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date']);
        } else {
            $input['start_date'] = strtotime("today");
        }

        if (!empty($inputObject['end_time']) && !empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline'] . ' ' . $inputObject['end_time']);
        } elseif (!empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline']);
        } else {
            $input['deadline'] = strtotime("+1 day", $input['start_date']);
        }

        if ($requestEdit) {
            $time = TimeManagement::where('object_id', $object['id'])->first();

            $rules = TimeManagement::$updateRules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            $time->update($input);
            if ($object['source_id']) {
                $this->setTimeManagement($object['source_id']);
            }

            return $time;
        } else {
            $rules = TimeManagement::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            return TimeManagement::create($input);
        }
    }

    private function createObjectAttendeeProcessing($attendee, $user, $requestEdit = false)
    {
        if ($requestEdit) {
            AttendeeProcessing::where('attendee_id', $attendee['id'])->delete();
        }
        $input['company_id'] = $user['company_id'];
        $input['attendee_id'] = $attendee['id'];
        $input['status'] = 'new';

        $list = json_decode($attendee['employee_array']);
        $result = [];
        foreach ($list as $item) {
            $input['added_by'] = $item;
            $rules = AttendeeProcessing::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            $result[] = AttendeeProcessing::create($input);
        }
        return $result;
    }


    private function createObjectAttendee($inputObject, $object, $user, $requestEdit = false)
    {
        
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
        $input['required_comment'] = $inputObject['attendee_required_comment'] ?? 0;
        $input['required_attachment'] = $inputObject['attendee_required_attachment'] ?? 0;
//        $input['department_array'] = '';
        
        if (!empty($inputObject['attendee_all'])) { // attendee = All
            // need change to role level
            $attendee = User::where('company_id', $object['company_id'])
                ->where('role_id', '>=', $user['role_id'])
                ->whereIn('role_id', [3, 4])
                ->pluck('id')
                ->toArray();

            if (!is_array($attendee)) {
                $attendee = array($attendee);
            }
            $input['employee_array'] = json_encode($attendee);
        } else {
            if ($inputObject['isDefaultAttendee']) {
                $input['employee_array'] = json_encode(array($user['id']));
            } else if (!empty($inputObject['attendee_employee_array'])) {  // choose employee
                if (!is_array($inputObject['attendee_employee_array'])) {
                    $inputObject['attendee_employee_array'] = array($inputObject['attendee_employee_array']);
                }
                $input['employee_array'] = json_encode($inputObject['attendee_employee_array']);
                if (!empty($inputObject['attendee_department_array'])) {
                    $input['department_array'] = json_encode($inputObject['attendee_department_array']);
                } 
//                $input['department_array'] = !empty($inputObject['attendee_department_array']) && count($inputObject['attendee_department_array']) > 0 ? json_encode($inputObject['attendee_department_array']) : '';
            } else if (!empty($inputObject['attendee_department_array'])) { // choose department
//                $input['department_array'] = $inputObject['attendee_employee_array'];
                $attendee = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                    ->where('users.company_id', $object['company_id'])
                    ->where('users.role_id', '>=', $user['role_id'])
                    ->whereIn('employees.department_id', $inputObject['attendee_department_array'])->pluck('user_id')->toArray();
                if (!is_array($attendee)) {
                    $attendee = array($attendee);
                }
                $input['employee_array'] = json_encode($attendee);
            } else {    // not choose department & employee
                $input['employee_array'] = json_encode(array($user['id']));
            }
        }
        
        if ($requestEdit) {
            Attendee::where('object_id', $object['id'])->delete();

            //            $rules = Attendee::$updateRules;
            //            $validator = Validator::make($input, $rules);
            //            if ($validator->fails()) {
            //                $errors = ValidateResponse::make($validator);
            //                return $this->responseError($errors,400);
            //            }
            //            $attendee = $attendee->update($input);
        }
        $rules = Attendee::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        } 
        $attendee = Attendee::create($input);
        //        $this->requestPushNotification($user['id'], $user['company_id'], json_decode($attendee['employee_array']), 'notification', $object['type'], $object['id'], $object['name'], 'attendee');
        $this->requestPushNotification($user['id'], $user['company_id'], json_decode($attendee['employee_array']), 'notification', $object, 'attendee');

        return $attendee;
    }

    private function createTaskObject($input, $user)
    {
        $inputTemp = $input;

        if ($user->role_id == 1) {
            $input['company_id'] = '';
        } else {
            $input['company_id'] = $user['company_id'];
            $input['industry'] = json_encode($user['company']['industry_id']);
        }
        $rules = ObjectItem::$rules;
        $input['added_by'] = $user['id'];
        //                $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', $input['type'], $input['name']));

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $newObject = ObjectItem::create($input);
        if (!empty($input['connectToArray'])) {
            $this->addConnectToObject($user, $newObject['id'], $newObject['type'], $input['connectToArray']);
        }

        if ($newObject['type'] == 'instruction' || $newObject['type'] == 'risk' || $newObject['type'] == 'risk-analysis') {
            // Handle to save Security
            $this->createSecurityObject($newObject, $input);
        }

        // Responsible
        // if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
        //     $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
        // }
        if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) { 
            $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
            // if (!empty($inputTemp['responsible_required_comment']) && $inputTemp['responsible_required_comment'] || !empty($inputTemp['responsible_required_attachment']) && $inputTemp['responsible_required_attachment']) {
            $newObject->processing = $this->createObjectResponsibleProcessing($newObject->responsible, $user, false, $input);
            // }
        }

        // Attendee
        if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
            $newObject->attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

            // Attendee processing
            $newObject->processing = $this->createObjectAttendeeProcessing($newObject->attendee, $user);
        }

        // Time management
        $newObject->time = $this->createObjectTimeManagement($inputTemp, $newObject, $user);

        // Risk element
        if ($input['type'] == 'risk') {
            $newObject->object_option = $this->createObjectOption($inputTemp, $newObject);
        }

        // Risk analysis
        if ($input['type'] == 'risk-analysis') {
            $this->addRiskElementToRiskAnalysis($input['risk_element_array'], $newObject);
            if (!empty($input['source_of_danger'])) {
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user);
            }
        }

        // Task of Risk analysis
        if ($input['type'] == 'task' && $input['source'] == 'risk-analysis') {
            if (!empty($input['source_of_danger'])) {
                $newObject['source_id'] = $newObject->id;
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user, true);
            }
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
        if (isset($inputObject['isDefaultResponsible']) && $inputObject['isDefaultResponsible']) {
            $input['employee_array'] = json_encode(array($user['id']));
        } elseif (!empty($inputObject['responsible_employee_array'])) {   // choose employee
            //            foreach ($inputObject['responsible_employee_array'] as $item) {
            //                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
            //            }
            if (!is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] = array($inputObject['responsible_employee_array']);
            }
            if (isset($inputObject['responsible_department_array']) && count($inputObject['responsible_department_array']) > 0 && !is_array($inputObject['responsible_department_array'])) {
                $inputObject['responsible_department_array'] = array($inputObject['responsible_department_array']);
            }

//            $input['department_array'] = !empty($inputObject['responsible_department_array']) && count($inputObject['responsible_department_array']) > 0 ? json_encode($inputObject['responsible_department_array']) : '';
            $input['employee_array'] = json_encode($inputObject['responsible_employee_array']);
        } elseif (!empty($inputObject['responsible_department_array'])) {   // choose department
            $responsible = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['responsible_department_array'])
                ->pluck('user_id')
                ->toArray();
            //            foreach ($responsible as $item) {
            //                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
            //            }
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = json_encode($responsible);
        } else {    // not choose department & employee
            $input['employee_array'] = json_encode(array($user['id']));
        }

        if ($requestEdit) {
            Responsible::where('object_id', $object['id'])->delete();
            //            $rules = Responsible::$updateRules;
            //            $validator = Validator::make($input, $rules);
            //            if ($validator->fails()) {
            //                $errors = ValidateResponse::make($validator);
            //                return $this->responseError($errors,400);
            //            }
            //            $responsible = $responsible->update($input);
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

    private function getObjectStatus($objectData,$timeInfo)
    {
        $obj_start_date = $timeInfo['start_date']?? '';
        $obj_start_time = isset($timeInfo['start_time']) && !empty($timeInfo['start_time']) ? $timeInfo['start_time'] : '00:00:00';
        $obj_end_date = $timeInfo['deadline'] ?? '';
        $obj_end_time = isset($timeInfo['end_time']) && !empty($timeInfo['end_time']) ? $timeInfo['end_time'] : '00:00:00'; 
        $obj_task_status = "new";

        if((!empty($obj_start_date) && !empty($obj_start_time)) && (!empty($obj_end_date) && !empty($obj_end_time))) {
            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
            $startDay = Carbon::make($obj_start_date . ' ' . $obj_start_time);
            $endDay = Carbon::make($obj_end_date . ' ' . $obj_end_time);
            if(isset($objectData['status']) && ($objectData['status'] == "closed" || $objectData['status'] == "3")) {
                $obj_task_status = "closed";
            } else if(isset($objectData['status']) && $objectData['status'] == "Removed" || $objectData['status'] == "Reassigned" || $objectData['status'] == "disapproved_overdue" || $objectData['status'] == "disapproved_with_extended" || $objectData['status'] == "timeline_disapproved" || $objectData['status'] == "overdue" || $objectData['status'] == "request" || $objectData['status'] == "approved_overdue" || $objectData['status'] == "completed" || $objectData['status'] == "approved" || $objectData['status'] == "disapproved" || $objectData['status'] == "completed_overdue") {
                $obj_task_status = $objectData['status'];
            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                $obj_task_status = "new";
            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                $obj_task_status = "new";
            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                $obj_task_status = "ongoing";
            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($objectData['status']) && ($objectData['status'] !== "closed" || $objectData['status'] !== "3"))) {
                $obj_task_status = "overdue";
            } else {
                $obj_task_status = "new";
            }
            
            return $obj_task_status ?? '';
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
            $documentData = DocumentNew::leftJoin('documents_attachments', 'documents_new.id', '=', 'documents_attachments.document_id')
                ->leftJoin('categories_new', 'documents_new.category_id', '=', 'categories_new.id')
                ->leftJoin('documents_options', 'documents_new.id', '=', 'documents_options.document_id')
                ->leftJoin('time_management', 'documents_new.task_id', '=', 'time_management.object_id')
                ->leftJoin('routines', 'documents_new.routine_id', '=', 'routines.id')
                ->where("documents_new.id", $id)
                // ->with(['tasks' => function($query) {
                //     $query->with(['task_assignees']);
                // }])
                ->select('documents_new.*',
                    'documents_attachments.original_file_name as original_file_name',
                    'documents_attachments.file_size as file_size',
                    'documents_attachments.uri as uri',
                    'documents_options.is_renewed as is_renewed',
                    'documents_options.renewed_employee_array as renewed_employee_array',
                    'documents_options.is_public as is_public',
                    'documents_options.security_department_array as security_department_array',
                    'documents_options.security_employee_array as security_employee_array',
                    'time_management.start_date as start_date',
                    'time_management.deadline as deadline',
                    'routines.start_time as routine_startdate',
                    'routines.deadline as routine_deadline',
                    'categories_new.name as category_name')
                ->first();
            if (empty($documentData)) {
                return $this->responseException('Not found document', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'document',
                'objectItem' => $documentData,
            ];
            if (!$user = JWTAuth::parseToken()->authenticate()) {
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
                        $documentData['url'] = $baseUrl . "image/".  $documentData['uri'];
//                        $documentData['url'] = "http://localhost:8000/api/v1/image/".  $documentData['uri'];
                    } else {
                        $documentData['url'] = $baseUrl . "/uploads/attachments/".  $documentData['uri'];
//                        $documentData['url'] = "http://localhost:8000/api/v1/uploads/".  $documentData['uri'];
                    }
                }
                $documentData['security_employee_array'] = !empty($documentData['security_employee_array']) ?  (json_decode($documentData['security_employee_array'])) : '';
                $documentData['security_department_array'] = !empty($documentData['security_department_array']) ?  (json_decode($documentData['security_department_array'])) : '';
                $documentData->is_shared = 0;
                // if(!empty($documentData['security_employee_array']) && !empty($documentData['security_department_array'] )){
                //     $documentData->is_shared = 1;
                // }
                if(!empty($documentData['security_employee_array'])){
                    $documentData->is_shared = 1;
                }
                $documentData->count_related_object = 0;
                $documentData->related_objects = '';
                $documentData->parent_object = '';
               
                // Routine::where('id',  $documentData->routine_id)->first();
               
                unset($documentData['tasks']);
                // $documentData->documentAttachment = DocumentAttachment::where('document_id', $documentData->document_id)->first();
                if ($documentData['is_template']) {
                    $relatedObject = DocumentNew::leftJoin('users', 'documents_new.added_by','=', 'users.id')
                        ->leftJoin('companies', 'documents_new.company_id','=', 'companies.id')
                        ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('documents_new.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select('documents_new.id', 'documents_new.name',
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
                    $parentObject = DocumentNew::leftJoin('users', 'documents_new.added_by','=', 'users.id')
                        ->where('documents_new.id', $documentData->parent_id)
                        ->select('documents_new.id', 'documents_new.name',
                            'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                            DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'))
                        ->first();

                    if ($parentObject) {
                        $documentData->parent_object = $parentObject;
                    }
                }
                $documentData->editPermission = $user->editPermission;
                if (!empty($documentData['task_id'])) {
                    if(!empty($documentData['start_date'])) {
                        $documentData['start_date'] = date("Y-m-d", $documentData['start_date']);
                    }
                    if (!empty($documentData['deadline'])) {
                        $documentData['deadline'] = date("Y-m-d", $documentData['deadline']);
                    }
                }
                if (!empty($documentData['routine_id'])) {
                    if (!empty($documentData['routine_startdate'])) {
                        $documentData['start_date'] = date("Y-m-d", $documentData['routine_startdate']);
                    }
                    if (!empty($documentData['routine_deadline'])) {
                        $documentData['deadline'] = date("Y-m-d", $documentData['routine_deadline']);
                    }
                }
                if(!empty($documentData->task_id)){
                    $task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.id', $documentData->task_id)
                    ->where('objects.type', 'task')
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first();
                   
                    if(!empty($task_data)){
                        $documentData['task_data'] = $this->getObjectDetailInfo($task_data, $user);
                        $documentData['task_data'] = $task_data ?? '';
                        
                        if (!empty($documentData['task_data'])) {
                            $documentData['task_data'] = $this->getDateTimeBasedOnTimezone($task_data, $user); 
                            if (!empty($documentData['task_data'])) {
                                $task_status = $this->getObjectStatus($documentData,$documentData['task_data']);
                                // $start_date = $documentData['task_data']->start_date ?? '';
                                // $start_time = $documentData['task_data']->start_time ?? '';
                                // if (!empty($start_date) && !empty($start_time)) {
                                //     $theDay = Carbon::make($start_date . ' ' . $start_time);
                                //     $theDay->isToday();
                                //     $theDay->isPast();
                                //     $theDay->isFuture();
                                //     if ($theDay->isToday() == true && $theDay->isPast() == true) {
                                //         $task_status = 6;
                                //     } else if ($theDay->isToday() == false && $theDay->isPast() == true) {
                                //         $task_status = 8;
                                //     } else if ($theDay->isToday() == false && $theDay->isPast() == false && $theDay->isFuture() == true) {
                                //         $task_status = 7;
                                //     }
                                // $documentData['task_data']->status = $task_status ?? '';
                                // $documentData->status = $task_status ?? '';
                                // }
                                $documentData['task_data']->status = $task_status ?? '';
                                $documentData->status = $task_status ?? '';
                            }
                        }
                    }
                }
                // if($documentData->status == 1){
                //     $documentData->status = "new";
                // }else if($documentData->status == 2){
                //     $documentData->status = "ongoing";
                // }else if($documentData->status == 3){
                //     $documentData->status = "closed";
                // }else if($documentData->status == 4){
                //     $documentData->status = "";
                // }else if($documentData->status == 5){
                //     $documentData->status = "reopen";
                // }else if($documentData->status == 6){
                //     $documentData->status = "pending";
                // }else if($documentData->status == 7){
                //     $documentData->status = "upcoming";
                // }else if($documentData->status == 8){
                //     $documentData->status = "overdue";
                // }else if($documentData->status == 9){
                //     $documentData->status = "completed";
                // }
                if(!empty($documentData->routine_id)){ 
                    $documentData['routine'] = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.id', $documentData->routine_id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first();
                    $documentData['routine'] = Routine::where('id', $documentData['routine']['source_id'])->first();
                   
                    $documentData['routine']['connect_to'] =   ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.company_id', $user['company_id'])
                    ->where('objects.id', $documentData->routine_id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first(); 
                    $documentData->count_related_object = 0;
                    $documentData->related_objects = '';
                    if ($documentData['is_template']) {
                        $relatedObject = Routine::leftJoin('users', 'routines.added_by', '=', 'users.id')
                            ->leftJoin('companies', 'routines.company_id', '=', 'companies.id')
                            ->where('parent_id', $id);
                        if ($user->filterBy != 'super admin') {
                            $relatedObject = $relatedObject->where('routines.company_id', $user['company_id']);
                        }
                        $relatedObject = $relatedObject->select(
                            'routines.id',
                            'routines.name',
                            'users.first_name as added_by_first_name',
                            'users.last_name as added_by_last_name',
                            DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                            'companies.name as company_name'
                        )
                            ->get();

                        if (count($relatedObject) > 0) {
                            $documentData->count_related_object = count($relatedObject);
                            $documentData->related_objects = $relatedObject;
                        }
                    }
                    if (!empty($documentData['routine']['start_date'])) {
                        $documentData['routine']['start_date'] =  $documentData['routine']['start_date'] ;
                        // $documentData['start_date'] =  $documentData['routine']['start_date'] ;
                    }
                    if (!empty($documentData['routine']['end_time'])) {
                        $documentData['routine']['end_time'] =  $documentData['routine']['end_time'] ; 
                    }
                    
                    if (!empty($documentData['routine']['deadline'])) {
                        $documentData['routine']['deadline'] = date('Y-m-d',$documentData['routine']->deadline) ;
                        // $documentData['deadline'] = !empty($documentData['routine']->deadline) ?  $documentData['routine']->deadline  :'' ;
                    }  

                    if (!empty($documentData['routine']['start_time'])) {
                        $documentData['routine']['start_time'] = date("H:i A",($documentData['routine']->start_time));
                        // $documentData['start_time'] =!empty($documentData['routine']->start_time) ?  $documentData['routine']->start_time  :'' ; 
                    }
                }
               
                    
                // get Security information
                $this->getSecurityObject('document', $documentData);
                // $documentData = $this->getReminderObject($documentData);

                return $this->responseSuccess($documentData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }


    private function getObjectDetailInfo($objectData, $userLogin)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];

        $objectData['addedByName'] = $this->getUserName($objectData['added_by'], $users);
        if ($userLogin['id'] == $objectData['added_by']) {
            $roleObject[] = 'creator';
        }
       
        $employeeName = [];
        $employeeRole = [];
        if (isset($objectData['responsible']['id'])) {
            $objectData['responsible']['addedByName'] = $this->getUserName($objectData['responsible']['added_by'], $users);

            $responsibleArray = json_decode($objectData['responsible']['employee_array']);

            if (in_array($userLogin['id'], $responsibleArray)) {
                $roleObject[] = 'responsible';
            }
             
            if(!empty($responsibleArray)){
                foreach ($responsibleArray as $item) {
                    $user =  User::where('id', $item)->select('id','first_name', 'last_name','role_id')->first();
                    if(!empty($user)){
                        $employeeRole[] = $user->id;
                        $employeeName[] = $this->getUserName($item, $users);
                    }
                }
            }else{
                $user =  User::where('id', $objectData['added_by'])->select('id','first_name', 'last_name','role_id')->first();
                $employeeRole[] = $user->id;
                $employeeName[] = $this->getUserName($objectData['added_by'], $users);
            }
            $objectData['responsible']['employeeName'] = $employeeName;
            $objectData['responsible']['employeeRole'] = $employeeRole;
        }
        
        if (isset($objectData['attendee']['id'])) {
            $objectData['attendee']['addedByName'] = $this->getUserName($objectData['attendee']['added_by'], $users);

            $attendeeArray = json_decode($objectData['attendee']['employee_array']);

            if (in_array($userLogin['id'], $attendeeArray)) {
                $roleObject[] = 'attendee';
            }

            $employeeName = [];
            foreach ($attendeeArray as $item) {
                $employeeName[] = $this->getUserName($item, $users);
            }
            $objectData['attendee']['employeeName'] = $employeeName; 

            $objectData['totalAttendee'] = 0;
            $objectData['completeAttendee'] = 0;
           
            if (!empty($objectData['attendee']['processing'])) {
                $objectData['totalAttendee'] = count($objectData['attendee']['processing']);
                foreach ($objectData['attendee']['processing'] as $item) {
                    $attendee['process_id'] = $item['id'];
                    $attendee['user_id'] = $item['added_by'];
                    $attendee['responsible_id'] = $item['responsible_id'];
                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
                    $attendee['comment'] = $item['comment'];
                    $attendee['image'] = $item['attachment_id'];
                    $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment_id'];
                    $attendee['required_comment'] = $objectData['attendee']['required_comment'];
                    $attendee['required_attachment'] = $objectData['attendee']['required_attachment'];

                    if ($item['status'] == 'closed') {
                        $objectData['completeAttendee'] += 1;
                    }

                    if (in_array('responsible', $roleObject) || in_array('creator', $roleObject)) {
                        $processingArray[] = $attendee;
                    } elseif (in_array('attendee', $roleObject)) {
                        $processingArray[] = $attendee;
                        //                        break;
                    }
                }
            }
            if ($objectData['totalAttendee'] > 0) {
                $objectData['rate'] = $objectData['completeAttendee'] * 100 / $objectData['totalAttendee'];
            } else {
                $objectData['rate'] = 0;
            }
        } 
        $objectData['processingInfo'] = $processingArray;
       
        // if (isset($objectData['time']['id'])) {
        //     // start date
        //     $objectData['start_date'] = date("Y-m-d", $objectData['time']['start_date']);
        //     $objectData['start_date_pre'] = $objectData['start_date'];
        //     // start time
        //     $objectData['start_time'] = date("H:i:s", $objectData['time']['start_time']);
        //     $objectData['start_time_pre'] = $objectData['start_time'];
        //     // deadline
        //     $objectData['deadline'] = date("Y-m-d", $objectData['time']['deadline']);
        //     $objectData['deadline_pre'] = $objectData['deadline'];
        //     // end time
        //     $objectData['end_time'] = date("H:i:s", $objectData['time']['deadline']);
        //     $objectData['end_time_pre'] = $objectData['end_time'];
        // }

        $objectData['role'] = $roleObject;

        return $objectData;
    }

    private function getObjectTimeInfo($objectData, $userLogin)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];

        $objectData['addedByName'] = $this->getUserName($objectData['added_by'], $users); 

        if (isset($objectData['time']['id'])) {
            $objectData['start_date'] = date("Y-m-d", $objectData['time']['start_date']);
            if(isset($objectData['time']['start_time']) && !empty($objectData['time']['start_time'])) {
                $objectData['start_time'] = $objectData['time']['start_time'].":00"; 
            } else {
                $objectData['start_time'] = "00:00:00";
            }
            $objectData['deadline'] = date("Y-m-d", $objectData['time']['deadline']);
            if(isset($objectData['time']['end_time']) && !empty($objectData['time']['end_time'])) {
                $objectData['end_time'] = $objectData['time']['end_time'].":00"; 
            } else {
                $objectData['end_time'] = "00:00:00";
            }
        }

        return $objectData;
    }

    private function getDateTimeBasedOnTimezone($object, $user)
    {
        $dateTimeObject = $this->getObjectTimeInfo($object, $user);
        $start_date = $dateTimeObject['start_date'] ?? '';
        $start_time = $dateTimeObject['start_time'] ?? '';
        $deadline = $dateTimeObject['deadline'] ?? '';
        $end_time = $dateTimeObject['end_time'] ?? '';

        if((isset($start_date) && !empty($start_date)) && (isset($start_time) && !empty($start_time))) {
            $object['start_date'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('Y-m-d');
            if($start_time == "00:00:00") {
                $object['start_time'] = "";
            } else {
                $object['start_time'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('H:i:s');
            }
        } else if(isset($start_date) && !empty($start_date)) {
            $start_time = "00:00";
            $object['start_date'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('Y-m-d');
            $object['start_time'] = "";
        } else {
            $object['start_date'] = "";
            $object['start_time'] = "";
        }

        if((isset($deadline) && !empty($deadline)) && (isset($end_time) && !empty($end_time))) {
            $object['deadline'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('Y-m-d');
            if($end_time == "00:00:00") {
                $object['end_time'] = "";
            } else {
                $object['end_time'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('H:i:s');
            }
        } else if(isset($deadline) && !empty($deadline)) {
            $end_time = "00:00";
            $object['deadline'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('Y-m-d');
            $object['end_time'] = "";
        } else {
            $object['deadline'] = "";
            $object['end_time'] = "";
        }

        return $object;
    }

    private function getUserName($id, $usersList): string
    {
        $username = '';
        $key = array_search($id, array_column($usersList->toArray(), 'id'));

        if ($key > -1) {
            $user =  $usersList[$key];
            $username =  $user['first_name'] . ' ' . $user['last_name'];
        }

        return $username;
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
           $rules = DocumentNew::$updateRules;
           $input = $request -> all();
           $documentData = DocumentNew::where("id",$id)->first();
            
           if (empty($documentData)) {
               return $this->responseException('Not found document', 404);
           }
       
            if(!empty($input['status']) && $input['status'] == "new"){
                $input['status'] = 1; 
            }else if(!empty($input['status']) && $input['status'] == "ongoing"){
               $input['status'] = 2; 
            }else if(!empty($input['status']) && $input['status'] == "closed"){
               $input['status'] = 3; 
            }else if(!empty($input['status']) && $input['status'] == ""){
               $input['status'] = 4; 
            }else if(!empty($input['status']) && $input['status'] == "reopen"){
               $input['status'] = 5; 
            }else if(!empty($input['status']) && $input['status'] == "pending"){
               $input['status'] = 6; 
            }else if(!empty($input['status']) && $input['status'] == "upcoming"){
               $input['status'] = 7; 
            }else if(!empty($input['status']) && $input['status'] == "overdue"){
               $input['status'] = 8; 
            }else if(!empty($input['status']) && $input['status'] == "completed"){
               $input['status'] = 9; 
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

               $this->updateConnectToObject($user, $documentData->id, 'document', $input['connectToArray']);
               
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
            $documentData = DocumentNew::find($id);
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
                    DocumentNew::where('id', $id)->delete();
                    return $this->responseSuccess("Delete document success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function fileShow($fileName) {
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
                return $this->responseSuccess($documentData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function uploadMultiple(Request $request, Document $document)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = DocumentNew::$rules;
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
                    $documentName = $input['name'];
                    foreach($files as $key=>$file){
//                        $fileValidator = Validator::make($file, $fileImageRules);
//                        if ($fileValidator->fails()) {
//                            $errors = ValidateResponse::make($fileValidator);
//                            return $this->responseError($errors);
//                        }

                        // table DocumentNew
                        $input['name'] = $documentName . '_Checkpoint_' . $key;
                        $newDocument = DocumentNew::create($input);

                        // table DocumentAttachment
                        $inputAttachment = $input;
                        $inputAttachment['document_id'] = $newDocument['id'];
                        $inputAttachment['original_file_name'] = $file->getClientOriginalName();
                        $path = Storage::disk('private')->putFile('documents/' . $input['company_id'], $file);
                        $inputAttachment['uri'] = $path;
                        $inputAttachment['file_size'] = round(($file->getSize()) / 1024,2); //convert byte to KB
                        $inputAttachment['file_extension'] = $file->getClientOriginalExtension();
                        DocumentAttachment::create($inputAttachment);

                        // table DocumentOption
                        $inputOption = $input;
                        $inputOption['document_id'] = $newDocument['id'];
                        $inputOption['report_question_id'] = $key;
                        $inputOption['security_employee_array'] = null;
                        foreach ($listAdminInfo as $item_info) {
                            $inputOption['security_employee_array'].= $item_info->id;
                        }
                        // Role Normal USER
                        if ($user['role_id'] > 3) { // only me + all Admin can view
                            $inputOption['security_employee_array'] .= ',' . $input['added_by'];
                        }
                        DocumentOption::create($inputOption);
                    }
                }

                return $this->responseSuccess(201);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
