<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Mail\FinishedTaskMail;
use App\Models\Attendee;
use App\Models\Company;
use App\Models\Places;
use App\Models\Task;
use App\Models\User;
use App\Models\ObjectItem;
use App\Models\Responsible;
use App\Models\Employee;
use App\Models\Consequences;
use App\Models\SourceOfDanger;
use App\Models\Security;
use App\Models\CategoryV2;
use App\Models\Department;
use App\Models\AttendeeHistory;
use App\Models\AttendeeProcessing;
use App\Models\TimeManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Validator;
use JWTAuth;
use App\Models\Deviation;
use App\Models\JobTitle;
use App\Models\RiskAnalysis;
use App\Models\RiskElementSource;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Deviations",
 *     description="Deviation APIs",
 * )
 **/
class DeviationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/deviations",
     *     tags={"Deviations"},
     *     summary="Get deviations",
     *     description="Get deviations list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDeviations",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            if (!$user = $this->getAuthorizedUser('deviation', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $resultIds = Deviation::pluck('id')->toArray(); 

                $resultIds = array_filter($resultIds, function ($id) use ($user){
                    return in_array($user->id, Helper::checkDeviationDisplayAccess($id)); 
                });
                $getByProjectID = $request->getByProjectID;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                //                $checkDeviationsPermission = $user->hasAccess('update-deviation');
                $result = Deviation::join('users', 'deviations.added_by', '=', 'users.id')
                    // ->leftJoin('categories', 'deviations.category_id', '=', 'categories.id')
                    ->where('deviations.company_id', $user->company_id)
                    ->whereIn('deviations.id', $resultIds);
                //                if(!$checkDeviationsPermission){
                //                    $result = $result->where('added_by', $user->id);
                //    }
                // if(!empty($request->status)){
                //     $result->where('deviations.status',$request->status); 
                // }
                if(!empty($request->startDate) && !empty($request->endDate)){
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $result->whereBetween('deviations.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }
                if(!empty($request->reported_by)){ 
                    if($request->reported_by == 'anonymous'){
                        $result->where('deviations.report_as_anonymous', 1);
                    }else{
                        $result->where('deviations.added_by',$request->reported_by);
                    }
                }
                if(!empty($request->by_name)){
                    $result->where('deviations.subject','Like', '%' .$request->by_name .'%' );
                }
                $result = $result->with(['user', 'tasks', 'risk_analysis', 'place', 'consequence_for'])
                    // $result = $result->with(['user'])
                ->select(
                    'deviations.*',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                )
                ->orderBy('id','desc')->paginate(10);

                $statusMapping = [
                    1 => 'new',
                    2 => 'ongoing',
                    3 => 'closed',
                ];

                if ($result) {
                    foreach ($result as $key => $deviation) {
                        if (isset($deviation['status']) && array_key_exists($deviation['status'], $statusMapping)) {
                            $deviation['status'] = $statusMapping[$deviation['status']];
                        }

                        $deviation['responsible_names'] = $this->getDeviationResponsible($deviation->id, $user);
                        $exist = ObjectItem::select('id')->where('source_id', $deviation->id)->where('type', 'deviation')->first();
                        if (!empty($exist)) {
                            $risk_analysis = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.source_id', $exist->id)
                            ->where('objects.type', 'risk-analysis')
                            // ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.id', 'objects.status')
                            // ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            
                            unset($deviation['risk_analysis']);
                            $deviation['risk_analysis'] = $risk_analysis ?? '';
                            if (isset($deviation['risk_analysis']) && !empty($deviation['risk_analysis'])) { 
                                $deviation['risk_analysis'] = $this->getDateTimeBasedOnTimezone($deviation['risk_analysis'], $user);
                                $dev_task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                    ->where('objects.source_id', $risk_analysis->id)
                                    ->where('objects.type', 'task')
                                    // ->with(['attendee', 'responsible', 'time'])
                                    // ->select('objects.*', 'categories_new.name as categoryName')
                                    ->select('objects.id')
                                    ->first();

                                if (isset($dev_task_data) && !empty($dev_task_data)) {
                                    $deviation['risk_analysis']->task_data = $this->getDateTimeBasedOnTimezone($dev_task_data, $user);
                                    if (isset($deviation['risk_analysis']->task_data) && !empty($deviation['risk_analysis']->task_data)) {
                                        $start_date = $deviation['risk_analysis']->task_data->start_date ?? '';
                                        $start_time = isset($deviation['risk_analysis']->task_data->start_time) && !empty($deviation['risk_analysis']->task_data->start_time) ? $deviation['risk_analysis']->task_data->start_time : '00:00:00';
                                        $end_date = $deviation['risk_analysis']->task_data->deadline ?? '';
                                        $end_time = isset($deviation['risk_analysis']->task_data->end_time) && !empty($deviation['risk_analysis']->task_data->end_time) ? $deviation['risk_analysis']->task_data->end_time : '00:00:00'; 
                                        $task_status = "new";
                                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                                            if(isset($deviation['status']) && ($deviation['status'] == 'closed' || $deviation['status'] == '3')){
                                                $task_status = "closed";
                                            } else if (isset($deviation['risk_analysis']->task_data->status) && ($deviation['risk_analysis']->task_data->status == "3" || $deviation['risk_analysis']->task_data->status == "closed")) {
                                                $task_status = "closed";
                                            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                                $task_status = "new";
                                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                $task_status = "new";
                                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                $task_status = "ongoing";
                                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($deviation['risk_analysis']->task_data->status) && ($deviation['risk_analysis']->task_data->status !== "3" || $deviation['risk_analysis']->task_data->status !== "closed"))) {
                                                $task_status = "overdue";
                                            } else {
                                                $task_status = "new";
                                            }
                                        } 
                                        $deviation['risk_analysis']->task_data->status = $task_status ?? '';
                                        $deviation['status'] = $task_status;
                                        $attendee_info = Attendee::where('object_id', $dev_task_data->id)->latest()->first();
                                        if(isset($attendee_info) && !empty($attendee_info)) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) { 
                                            $deviation['status'] = $attendee_processing->status; 

                                            $attendee_history = $this->getAttendeStatusHistory($deviation['risk_analysis']->task_data['id'], $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                        
                                                $deviation->status = $attendee_processing->status;
                                                $deviation->status = $this->getObjectStatus($deviation,$deviation['risk_analysis']->task_data); 

                                            }
                                        } 

                                        // $deviation['risk_analysis']->task_data->responsible_names = $this->getDeviationResponsible($dev_task_data->id, $user);
                                    }
                                } else {
                                    $deviation['status'] = "completed";
                                }
                            }
                            $task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                ->where('objects.source_id', $exist->id)
                                ->where('objects.type', 'task')
                                // ->with(['attendee', 'responsible', 'time'])
                                // ->select('objects.*', 'categories_new.name as categoryName')
                                ->select('objects.id', 'objects.status')
                                ->with('attendee', 'responsible')
                                ->first();

                            $deviation['task_data'] = $task_data ?? '';
                            $deviation['object_id'] = $exist->id ?? '';

                            if (isset($deviation['task_data']) && !empty($deviation['task_data'])) {
                                $deviation['task_data'] = $this->getDateTimeBasedOnTimezone($task_data, $user);
                                
                                // $deviation['task_data']->responsible_names = $this->getDeviationResponsible($exist->id, $user);
                                if (!empty($deviation['task_data'])) {
                                    $start_date = $deviation['task_data']->start_date ?? '';
                                    $start_time = isset($deviation['task_data']->start_time) && !empty($deviation['task_data']->start_time) ? $deviation['task_data']->start_time : '00:00:00';
                                    $end_date = $deviation['task_data']->deadline ?? '';
                                    $end_time = isset($deviation['task_data']->end_time) && !empty($deviation['task_data']->end_time) ? $deviation['task_data']->end_time : '00:00:00'; 

                                    if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                        $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                        $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                        $startDay = Carbon::make($start_date . ' ' . $start_time);
                                        $endDay = Carbon::make($end_date . ' ' . $end_time);

                                        if(isset($task_data->status) && ($task_data->status == "closed" || $task_data->status == "3")) {
                                            $task_status = "closed";
                                        } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                            $task_status = "new";
                                        } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                            $task_status = "new";
                                        } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                            $task_status = "ongoing";
                                        } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($task_data->status) && ($task_data->status !== "closed" || $task_data->status !== "3"))) {
                                            $task_status = "overdue";
                                        } else {
                                            $task_status = "new";
                                        }

                                        $deviation['task_data']->status = $task_status ?? '';
                                        $deviation->status = $task_status ?? '';
                                    } 

                                    $task_attendee = $task_data->attendee ?? null;
                                    $task_responsible = $task_data->responsible ?? null;

                                    $is_self_rs_and_ca = false;
                                    if((isset($task_attendee->employee_array) && !empty($task_attendee->employee_array)) && (isset($task_responsible->employee_array) && !empty($task_responsible->employee_array))) {
                                        $attn_emp_arr = json_decode($task_attendee->employee_array);
                                        $resp_emp_arr = json_decode($task_responsible->employee_array);
                                        sort($attn_emp_arr);
                                        sort($resp_emp_arr);

                                        $is_self_rs_and_ca = $attn_emp_arr == $resp_emp_arr ? true : false;
                                    }
                                    
                                    $attendee_info = Attendee::where('object_id', $task_data->id)->latest()->first();
                                    if((isset($attendee_info) && !empty($attendee_info)) && !$is_self_rs_and_ca) {
                                        $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                        if(isset($attendee_processing) && !empty($attendee_processing)) {
                                            $attendee_history = $this->getAttendeStatusHistory($deviation['task_data']['id'], $user->id);
                                            if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                $attendee_processing->status = 'Reassigned';
                                            }
                                            if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                $attendee_processing->status = 'Removed';
                                            }
                                        
                                            $deviation->status = $attendee_processing->status;
                                            $deviation->status = $this->getObjectStatus($deviation,$deviation['task_data']);  

                                            // if($deviation->status == 'ongoing') {
                                                // if($startDay->format('Y-m-d H:i:s') >= $todayDate) {
                                                //     $deviation->status = "new";
                                                // } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                //     $deviation->status = "new";
                                                // } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                //     $deviation->status = "ongoing";
                                                // } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($task_data->status) && ($task_data->status !== "closed" || $task_data->status !== "3"))) {
                                                //     $deviation->status = "overdue";
                                                // } else {
                                                //     $deviation->status = $attendee_processing->status;
                                                // }
                                            // }
                                        }
                                    } 
                                }
                            }
                            if (isset($deviation['risk_analysis']) && !empty($deviation['risk_analysis'])) { 
                                $risk = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                ->where('objects.source_id', $risk_analysis->id)
                                ->where('objects.type', 'task')
                                ->where('objects.source', 'risk-analysis')
                                ->select('objects.id', 'objects.status')
                                ->first();
                                
                                $deviation['task_data'] = $risk ?? '';
                                $deviation['object_id'] = $risk_analysis->id ?? '';
                                if (isset($deviation['task_data']) && !empty($deviation['task_data'])) {
                                    $deviation['task_data'] = $this->getDateTimeBasedOnTimezone($risk, $user);
                                    
                                    if (!empty($deviation['task_data'])) {
                                        $start_date = $deviation['task_data']->start_date ?? '';
                                        $start_time = isset($deviation['task_data']->start_time) && !empty($deviation['task_data']->start_time) ? $deviation['task_data']->start_time : '00:00:00';
                                        $end_date = $deviation['task_data']->deadline ?? '';
                                        $end_time = isset($deviation['task_data']->end_time) && !empty($deviation['task_data']->end_time) ? $deviation['task_data']->end_time : '00:00:00'; 
                                        
                                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                                            if(isset($risk->status) && ($risk->status == "closed" || $risk->status == "3")) {
                                                $task_status = "closed";
                                            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                                $task_status = "new";
                                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                $task_status = "new";
                                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                $task_status = "ongoing";
                                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($risk->status) && ($risk->status !== "closed" || $risk->status !== "3"))) {
                                                $task_status = "overdue";
                                            } else {
                                                $task_status = "new";
                                            }
                                            $deviation['task_data']->status = $task_status ?? '';
                                            $deviation->status = $task_status ?? '';
                                        } 
                                        
                                        $attendee_info = Attendee::where('object_id', $risk->id)->latest()->first();
    
                                        if(isset($attendee_info) && !empty($attendee_info)) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) { 
                                                $deviation->status = $attendee_processing->status; 
                                                //    if($deviation->status == 'ongoing') {
                                                //         if($startDay->format('Y-m-d H:i:s') >= $todayDate) {
                                                //             $deviation->status = "new";
                                                //         } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                //             $deviation->status = "new";
                                                //         } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                //             $deviation->status = "ongoing";
                                                //         } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($risk->status) && ($risk->status !== "closed" || $risk->status !== "3"))) {
                                                //             $deviation->status = "overdue";
                                                //         } else {
                                                //             $deviation->status = $attendee_processing->status;
                                                //         }
                                                //     }
                                                $deviation['status'] = $attendee_processing->status; 

                                                $attendee_history = $this->getAttendeStatusHistory($deviation['task_data']['id'], $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                            
                                                $deviation->status = $attendee_processing->status;
                                                $deviation->status = $this->getObjectStatus($deviation,$deviation['task_data']); 
                                            }
                                        } 
                                    }
                                }
                            }
                        }
                        $deviation['category_name'] = CategoryV2::find($deviation->category_id)->name ?? '';
                        // $deviation['task'] = Tasks::find($deviation->category_id)->name ?? '';
                        // $this->getSecurityObject('deviation', $deviation);
                        $this->getSecurityObject('deviation', $deviation);
                    }

                    // $result = $result->toArray();
                    // if(isset($request->status) && !empty($request->status)){
                    //     $status_new_records = $request->status == 1 ? $this->getStatusCount($result, 'new') : null;
                    //     $status_ongoing_records = $request->status == 2 ? $this->getStatusCount($result, 'ongoing') : null;
                    //     $status_closed_records = $request->status == 3 ? $this->getStatusCount($result, 'closed') : null;
                    // } else {
                    //     $status_new_records = $this->getStatusCount($result, 'new');
                    //     $status_ongoing_records = $this->getStatusCount($result, 'ongoing');
                    //     $status_closed_records = $this->getStatusCount($result, 'closed');
                    // }
                    // if(isset($request->status) && $request->status == 1) {
                    //     $final_resp = $this->paginate($status_new_records);
                    // } else if (isset($request->status) && $request->status == 2) {
                    //     $final_resp = $this->paginate($status_ongoing_records);
                    // } else if (isset($request->status) && $request->status == 3) {
                    //     $final_resp = $this->paginate($status_closed_records);
                    // } else {
                    //     $final_resp = $this->paginate($result);
                    // }
                    // $custom = collect([
                    //     'total_new' => $status_new_records->count() ?? 0,
                    //     'total_ongoing' => $status_ongoing_records->count() ?? 0,
                    //     'total_closed' => $status_closed_records->count() ?? 0,
                    // ]);
    
                    // $final_resp = $custom->merge($result);
                    return response()->json($result);
                    // return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
    public function filterRecord(Request $request)
    {
        try {
            if (!$user = $this->getAuthorizedUser('deviation', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                // $resultIds = Deviation::pluck('id')->toArray(); 

                // $resultIds = array_filter($resultIds, function ($id) use ($user){
                //     return in_array($user->id, Helper::checkDeviationDisplayAccess($id)); 
                // });
                $getByProjectID = $request->getByProjectID;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                //                $checkDeviationsPermission = $user->hasAccess('update-deviation');
                $result = Deviation::join('users', 'deviations.added_by', '=', 'users.id')
                    // ->leftJoin('categories', 'deviations.category_id', '=', 'categories.id')
                    ->where('deviations.company_id', $user->company_id);
                //                if(!$checkDeviationsPermission){
                //                    $result = $result->where('added_by', $user->id);
                //    }
                // if(!empty($request->status)){
                //     $result->where('deviations.status',$request->status); 
                // }
                if(!empty($request->startDate) && !empty($request->endDate)){
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $result->whereBetween('deviations.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }
                if(isset($request->department) && !empty($request->department)) {
                    $department_detail = Department::where('id', $request->department)->with('employees')->first();
                    if(isset($department_detail->employees)) {
                        $employees_id = $department_detail->employees->pluck('user_id')->toArray();
                        $result->whereIn('deviations.added_by', $employees_id)->where('deviations.company_id', $user->company_id);
                    }
                }
                if(isset($request->job_title) && !empty($request->job_title)) {
                    $job_title_detail = JobTitle::where('id', $request->job_title)->with('employees')->first();
                    if(isset($job_title_detail->employees)) {
                        $employees_id = $job_title_detail->employees->pluck('user_id')->toArray();
                        $result->whereIn('deviations.added_by', $employees_id)->where('deviations.company_id', $user->company_id);
                    }
                }
                if(!empty($request->reported_by)){ 
                    if($request->reported_by == 'anonymous'){
                        $result->where('deviations.report_as_anonymous', 1);
                    }else{
                        $result->where('deviations.added_by',$request->reported_by);
                    }
                }
                if(!empty($request->by_name)){
                    $result->where('deviations.subject','Like', '%' .$request->by_name .'%' );
                }
                $result = $result->with(['user', 'tasks', 'risk_analysis', 'place', 'consequence_for'])
                    // $result = $result->with(['user'])
                ->select(
                    'deviations.*',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                )
                ->orderBy('id','desc')->get();

                $statusMapping = [
                    1 => 'new',
                    2 => 'ongoing',
                    3 => 'closed',
                ];

                if ($result) {
                    foreach ($result as $key => $deviation) {
                        if (isset($deviation['status']) && array_key_exists($deviation['status'], $statusMapping)) {
                            $deviation['status'] = $statusMapping[$deviation['status']];
                        }

                        $deviation['responsible_names'] = $this->getDeviationResponsible($deviation->id, $user);
                        $exist = ObjectItem::select('id')->where('source_id', $deviation->id)->where('type', 'deviation')->first();
                        if (!empty($exist)) {
                            $risk_analysis = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.source_id', $exist->id)
                            ->where('objects.type', 'risk-analysis')
                            // ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.id', 'objects.status')
                            // ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                            
                            unset($deviation['risk_analysis']);
                            $deviation['risk_analysis'] = $risk_analysis ?? '';
                            if (isset($deviation['risk_analysis']) && !empty($deviation['risk_analysis'])) { 
                                $deviation['risk_analysis'] = $this->getDateTimeBasedOnTimezone($deviation['risk_analysis'], $user);
                                $dev_task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                    ->where('objects.source_id', $risk_analysis->id)
                                    ->where('objects.type', 'task')
                                    // ->with(['attendee', 'responsible', 'time'])
                                    // ->select('objects.*', 'categories_new.name as categoryName')
                                    ->select('objects.id')
                                    ->first();

                                if (isset($dev_task_data) && !empty($dev_task_data)) {
                                    $deviation['risk_analysis']->task_data = $this->getDateTimeBasedOnTimezone($dev_task_data, $user);
                                    if (isset($deviation['risk_analysis']->task_data) && !empty($deviation['risk_analysis']->task_data)) {
                                        $start_date = $deviation['risk_analysis']->task_data->start_date ?? '';
                                        $start_time = isset($deviation['risk_analysis']->task_data->start_time) && !empty($deviation['risk_analysis']->task_data->start_time) ? $deviation['risk_analysis']->task_data->start_time : '00:00:00';
                                        $end_date = $deviation['risk_analysis']->task_data->deadline ?? '';
                                        $end_time = isset($deviation['risk_analysis']->task_data->end_time) && !empty($deviation['risk_analysis']->task_data->end_time) ? $deviation['risk_analysis']->task_data->end_time : '00:00:00'; 
                                        $task_status = "new";
                                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                                            if(isset($deviation['status']) && ($deviation['status'] == 'closed' || $deviation['status'] == '3')){
                                                $task_status = "closed";
                                            } else if (isset($deviation['risk_analysis']->task_data->status) && ($deviation['risk_analysis']->task_data->status == "3" || $deviation['risk_analysis']->task_data->status == "closed")) {
                                                $task_status = "closed";
                                            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                                $task_status = "new";
                                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                $task_status = "new";
                                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                $task_status = "ongoing";
                                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($deviation['risk_analysis']->task_data->status) && ($deviation['risk_analysis']->task_data->status !== "3" || $deviation['risk_analysis']->task_data->status !== "closed"))) {
                                                $task_status = "overdue";
                                            } else {
                                                $task_status = "new";
                                            }
                                        } 
                                        $deviation['risk_analysis']->task_data->status = $task_status ?? '';
                                        $deviation['status'] = $task_status;
                                        $attendee_info = Attendee::where('object_id', $dev_task_data->id)->latest()->first();
                                        if(isset($attendee_info) && !empty($attendee_info)) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) { 
                                            $deviation['status'] = $attendee_processing->status; 

                                            $attendee_history = $this->getAttendeStatusHistory($deviation['risk_analysis']->task_data['id'], $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                        
                                                $deviation->status = $attendee_processing->status;
                                                $deviation->status = $this->getObjectStatus($deviation,$deviation['risk_analysis']->task_data); 

                                            }
                                        } 

                                        // $deviation['risk_analysis']->task_data->responsible_names = $this->getDeviationResponsible($dev_task_data->id, $user);
                                    }
                                } else {
                                    $deviation['status'] = "completed";
                                }
                            }
                            $task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                ->where('objects.source_id', $exist->id)
                                ->where('objects.type', 'task')
                                // ->with(['attendee', 'responsible', 'time'])
                                // ->select('objects.*', 'categories_new.name as categoryName')
                                ->select('objects.id', 'objects.status')
                                ->first();
                            $deviation['task_data'] = $task_data ?? '';
                            $deviation['object_id'] = $exist->id ?? '';

                            if (isset($deviation['task_data']) && !empty($deviation['task_data'])) {
                                $deviation['task_data'] = $this->getDateTimeBasedOnTimezone($task_data, $user);
                                
                                // $deviation['task_data']->responsible_names = $this->getDeviationResponsible($exist->id, $user);
                                if (!empty($deviation['task_data'])) {
                                    $start_date = $deviation['task_data']->start_date ?? '';
                                    $start_time = isset($deviation['task_data']->start_time) && !empty($deviation['task_data']->start_time) ? $deviation['task_data']->start_time : '00:00:00';
                                    $end_date = $deviation['task_data']->deadline ?? '';
                                    $end_time = isset($deviation['task_data']->end_time) && !empty($deviation['task_data']->end_time) ? $deviation['task_data']->end_time : '00:00:00'; 

                                    if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                        $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                        $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                        $startDay = Carbon::make($start_date . ' ' . $start_time);
                                        $endDay = Carbon::make($end_date . ' ' . $end_time);

                                        if(isset($task_data->status) && ($task_data->status == "closed" || $task_data->status == "3")) {
                                            $task_status = "closed";
                                        } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                            $task_status = "new";
                                        } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                            $task_status = "new";
                                        } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                            $task_status = "ongoing";
                                        } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($task_data->status) && ($task_data->status !== "closed" || $task_data->status !== "3"))) {
                                            $task_status = "overdue";
                                        } else {
                                            $task_status = "new";
                                        }

                                        $deviation['task_data']->status = $task_status ?? '';
                                        $deviation->status = $task_status ?? '';
                                    } 
                                    
                                    $attendee_info = Attendee::where('object_id', $task_data->id)->latest()->first();
                                    if(isset($attendee_info) && !empty($attendee_info)) {
                                        $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                        if(isset($attendee_processing) && !empty($attendee_processing)) {
                                            $attendee_history = $this->getAttendeStatusHistory($deviation['task_data']['id'], $user->id);
                                            if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                $attendee_processing->status = 'Reassigned';
                                            }
                                            if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                $attendee_processing->status = 'Removed';
                                            }
                                        
                                            $deviation->status = $attendee_processing->status;
                                            $deviation->status = $this->getObjectStatus($deviation,$deviation['task_data']);  

                                            // if($deviation->status == 'ongoing') {
                                                // if($startDay->format('Y-m-d H:i:s') >= $todayDate) {
                                                //     $deviation->status = "new";
                                                // } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                //     $deviation->status = "new";
                                                // } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                //     $deviation->status = "ongoing";
                                                // } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($task_data->status) && ($task_data->status !== "closed" || $task_data->status !== "3"))) {
                                                //     $deviation->status = "overdue";
                                                // } else {
                                                //     $deviation->status = $attendee_processing->status;
                                                // }
                                            // }
                                        }
                                    } 
                                }
                            }
                            if (isset($deviation['risk_analysis']) && !empty($deviation['risk_analysis'])) { 
                                $risk = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                                ->where('objects.source_id', $risk_analysis->id)
                                ->where('objects.type', 'task')
                                ->where('objects.source', 'risk-analysis')
                                ->select('objects.id', 'objects.status')
                                ->first();
                                
                                $deviation['task_data'] = $risk ?? '';
                                $deviation['object_id'] = $risk_analysis->id ?? '';
                                if (isset($deviation['task_data']) && !empty($deviation['task_data'])) {
                                    $deviation['task_data'] = $this->getDateTimeBasedOnTimezone($risk, $user);
                                    
                                    if (!empty($deviation['task_data'])) {
                                        $start_date = $deviation['task_data']->start_date ?? '';
                                        $start_time = isset($deviation['task_data']->start_time) && !empty($deviation['task_data']->start_time) ? $deviation['task_data']->start_time : '00:00:00';
                                        $end_date = $deviation['task_data']->deadline ?? '';
                                        $end_time = isset($deviation['task_data']->end_time) && !empty($deviation['task_data']->end_time) ? $deviation['task_data']->end_time : '00:00:00'; 
                                        
                                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                                            if(isset($risk->status) && ($risk->status == "closed" || $risk->status == "3")) {
                                                $task_status = "closed";
                                            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                                $task_status = "new";
                                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                $task_status = "new";
                                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                $task_status = "ongoing";
                                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($risk->status) && ($risk->status !== "closed" || $risk->status !== "3"))) {
                                                $task_status = "overdue";
                                            } else {
                                                $task_status = "new";
                                            }
                                            $deviation['task_data']->status = $task_status ?? '';
                                            $deviation->status = $task_status ?? '';
                                        } 
                                        
                                        $attendee_info = Attendee::where('object_id', $risk->id)->latest()->first();
    
                                        if(isset($attendee_info) && !empty($attendee_info)) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) { 
                                                $deviation->status = $attendee_processing->status; 
                                                //    if($deviation->status == 'ongoing') {
                                                //         if($startDay->format('Y-m-d H:i:s') >= $todayDate) {
                                                //             $deviation->status = "new";
                                                //         } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                //             $deviation->status = "new";
                                                //         } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                //             $deviation->status = "ongoing";
                                                //         } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($risk->status) && ($risk->status !== "closed" || $risk->status !== "3"))) {
                                                //             $deviation->status = "overdue";
                                                //         } else {
                                                //             $deviation->status = $attendee_processing->status;
                                                //         }
                                                //     }
                                                $deviation['status'] = $attendee_processing->status; 

                                                $attendee_history = $this->getAttendeStatusHistory($deviation['task_data']['id'], $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                            
                                                $deviation->status = $attendee_processing->status;
                                                $deviation->status = $this->getObjectStatus($deviation,$deviation['task_data']); 
                                            }
                                        } 
                                    }
                                }
                            }
                        }
                        $deviation['category_name'] = CategoryV2::find($deviation->category_id)->name ?? '';
                        // $deviation['task'] = Tasks::find($deviation->category_id)->name ?? '';
                        // $this->getSecurityObject('deviation', $deviation);
                        $this->getSecurityObject('deviation', $deviation);
                        if(!in_array($user->id, Helper::checkDeviationDisplayAccess($deviation->id))) {
                                $result->forget($key);
                        } 
                    }

                    $result = $result->toArray();
                    if(isset($request->status) && !empty($request->status)){
                        $status_new_records = $request->status == 1 ? $this->getStatusCount($result, 'new') : [];
                        $status_ongoing_records = $request->status == 2 ? $this->getStatusCount($result, 'ongoing') : [];
                        $status_closed_records = $request->status == 3 ? $this->getStatusCount($result, 'closed') : [];
                    } else {
                        $status_new_records = $this->getStatusCount($result, 'new');
                        $status_ongoing_records = $this->getStatusCount($result, 'ongoing');
                        $status_closed_records = $this->getStatusCount($result, 'closed');
                    }
                    if(isset($request->status) && $request->status == 1) {
                        $final_resp = $this->paginate($status_new_records);
                    } else if (isset($request->status) && $request->status == 2) {
                        $final_resp = $this->paginate($status_ongoing_records);
                    } else if (isset($request->status) && $request->status == 3) {
                        $final_resp = $this->paginate($status_closed_records);
                    } else {
                        $final_resp = $this->paginate($result);
                    }
                    $custom = collect([
                        'total_new' => count($status_new_records),
                        'total_ongoing' => count($status_ongoing_records),
                        'total_closed' => count($status_closed_records),
                    ]);
    
                    $final_resp = $custom->merge($final_resp);
                    return response()->json($final_resp);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    

    private function getStatusCount($result, $status)
    {
        $status_records = array_filter($result, function($v,$k) use($status) {
            return $v['status'] == $status;
        }, ARRAY_FILTER_USE_BOTH);

        return array_values($status_records) ?? [];
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

    private function getObjectStatus($objectData,$timeInfo){
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
            } else if(isset($objectData['status']) && ($objectData['status'] == "Removed" || $objectData['status'] == "Reassigned" || $objectData['status'] == "disapproved_overdue" || $objectData['status'] == "disapproved_with_extended" || $objectData['status'] == "timeline_disapproved" || $objectData['status'] == "overdue" || $objectData['status'] == "request" || $objectData['status'] == "approved_overdue" || $objectData['status'] == "completed" || $objectData['status'] == "approved" || $objectData['status'] == "disapproved" || $objectData['status'] == "completed_overdue")) {
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

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $current_items = array_slice($items, $perPage * ($page - 1), $perPage);
        $options = [
            'path' => url('api/v1/deviations/filter')
        ];
        $paginator = new LengthAwarePaginator($current_items, count($items), $perPage, $page, $options);
        $paginator->appends(request()->all());
        return $paginator;
    }
 
    public function store(Request $request, Deviation $deviation)
    {

        try {
            if (!$user = $this->getAuthorizedUser('deviation', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Deviation::$rules;
                $input = $request->all();
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user['company_id'];

                $input['attachment'] = '';
                if (!empty($request->file('file'))) {
                    $baseUrl = config('app.app_url');
                    $path = Storage::disk('public')->putFile('/' . $user['company_id'], $request->file('file'));
                    $input['attachment'] = $baseUrl . "/uploads/attachments/" . $path;
                    $input['connectToArray'] = @json_decode(@$input['connectToArray'], true);
                }

                if (!empty($user->role_id) && $user->role_id == 3) {
                    if (empty(json_decode($input['responsible_employee_array']))) {
                        $input['responsible_employee_array'] = json_encode(array($user->id));
                    }
                } else if ($user->employee->nearest_manager) {
                    $input['responsible'] = $user->employee->nearest_manager;
                    if (empty(json_decode($input['responsible_employee_array']))) {
                        $input['responsible_employee_array'] = json_encode(array($user->employee->nearest_manager));
                    }
                } else {
                    $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                    if ($companyAdmin) {
                        $input['responsible'] = $companyAdmin->id; 
                        if (empty($input['responsible_employee_array']) || count(json_decode($input['responsible_employee_array']))==0) {
                            $input['responsible_employee_array'] = json_encode(array($companyAdmin->id));
                        }
                    }
                } 
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
               
                   $newDeviation = Deviation::create($input);

                // Handle to save Security/ Connect to
                $input['name'] = $input['subject'] ?? '';
                
                $newObject = $this->createObject($input, $user, $newDeviation['id'], 'deviation');
                if (!empty($input['department_array'])) {
                    $newObject['department_array'] = json_encode($input['department_array']);
                }
                if (!empty($input['employee_array'])) {
                    $newObject['employee_array'] = json_encode($input['employee_array']);
                }
                
                if((isset($input['department_array']) && !empty($input['department_array'])) && gettype($input['department_array']) == 'string') {
                    $input['department_array'] = json_decode($input['department_array']);
                }
                if((isset($input['employee_array']) && !empty($input['employee_array'])) && gettype($input['employee_array']) == 'string') {
                    $input['employee_array'] = json_decode($input['employee_array']);
                }
                 $this->createSecurityObject($newObject, $input);

                // if(!empty($input['responsible_employee_array'])  ){
                //     $encode = json_decode($input['responsible_employee_array']); 
                //     foreach($encode as $responsible_employee_array){
                //         $this->pushNotification($user['id'], $user['company_id'], 2, [$responsible_employee_array], 'deviation', 'Deviation', $newDeviation['id'], $newDeviation['subject'], 'responsible');
                //     }
                // }else{
                //     if(!empty($input['responsible'])){
                //         $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'deviation', 'Deviation', $newDeviation['id'], $newDeviation['subject'], 'responsible');
                //     }
                // }

                return $this->responseSuccess($newDeviation);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
    

    /**
     * @OA\Get(
     *     path="/api/v1/deviations/{id}",
     *     tags={"Deviations"},
     *     summary="Get deviation by id",
     *     description="Get deviation by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getDeviationByIdAPI",
     *     @OA\Parameter(
     *         description="deviation id",
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
            $deviationData = Deviation::leftJoin('departments', 'deviations.department_id', '=', 'departments.id')
                ->leftJoin('job_titles', 'deviations.job_title_id', '=', 'job_titles.id')
                ->join('categories', 'deviations.category_id', '=', 'categories.id')
                ->where('deviations.id', $id)
                // ->with(['tasks' => function($query) {
                //     $query->with(['task_assignees']);
                // }, 'risk_analysis' => function($queryRisk) {
                //     $queryRisk->with(['tasks' => function($queryRiskTask) {
                //         $queryRiskTask->with(['task_assignees']);
                //     }, 'elements']);
                // }, ])
                ->select('deviations.*', 'departments.name as department_name', 'job_titles.name as job_title_name')
                ->first();
            if (empty($deviationData)) {
                return $this->responseException('Not found deviation', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'deviation',
                'objectItem' => $deviationData,
            ];
            // if (!$user = $this->getAuthorizedUser('deviation', 'detail', 'show', $objectInfo)) {
            if (!$user = $this->getAuthorizedUser('deviation', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

                if(isset($deviationData->status) && $deviationData->status == 1) {
                    $deviationData->status = "new";
                } else if(isset($deviationData->status) && $deviationData->status == 2) {
                    $deviationData->status = "ongoing";
                } else if(isset($deviationData->status) && $deviationData->status == 3) {
                    $deviationData->status = "closed";
                } 

                $reportedUser = User::find($deviationData['added_by']);
                $deviationData['added_by_name'] = $reportedUser['first_name'] . " " . $reportedUser['last_name'];
                $deviationData['place'] = Places::select('id', 'place_name', 'added_by', 'is_deleted')->find($deviationData['place']);
                $deviationData['consequence_for'] = Consequences::select('id', 'name')->find($deviationData['consequence_for']);
                if ($deviationData['action'] == 'task' && !empty($deviationData['tasks'])) {
                    foreach ($deviationData->tasks as $task) {
                        $task->remaining_time = '';
                        if ($task->deadline) {
                            $task->remaining_time = $this->calRemainingTime($task->deadline);
                        }
                        $deviationData->responsible_id = $task->responsible_id;
                        $deviationData->deadline = $task->deadline;
                    }
                    //                    $deviation_tasks = Task::where('type', 'Deviation')
                    //                        ->where('type_id', $id)
                    //                        ->whereIn('status', [1,2])->get();
                    //                    if (count($deviation_tasks) == 0) {
                    //                        $deviationData['is_action_done'] = true; // done all deviation tasks
                    //                    } else {
                    //                        $deviationData['is_action_done'] = false;
                    //                    }
                } else if ($deviationData['action'] == 'risk') {
                    // return response()->json([
                    //     'r'=>'$deviationData->risk_analysis'
                    // ]);
                    // foreach ($deviationData->risk_analysis as $risk) {
                    //     $deviationData->responsible_id = $risk->responsible;
                    // }
                    //                    $deviation_risks = RiskAnalysis::where('deviation_id', $deviationData['id'])
                    //                        ->whereIn('status', [1,2])->get();
                    //                    if (count($deviation_risks) == 0) {
                    //                        $deviationData['is_action_done'] = true; // done all deviation risks
                    //                    } else {
                    //                        $deviationData['is_action_done'] = false;
                    //                    }
                }


                // $deviationobject = DB::table('objects')->where('type','deviation')->where('source_id',$deviationData->id)->select('id')->first();
                // $deviationData->task = [];
                // $deviationData->sourceOfDanger = []; 
                // if(!empty($deviationData->object)){
                //     $deviationData->task = DB::table('objects')->where('type','task')->where('source_id',$deviationobject->id)->get();
                //     $deviationData->sourceOfDanger = SourceOfDanger::where('object_id', $deviationData->object->id)->get(); 
                // }
                $deviationData->editPermission = $user->editPermission;

                // $deviationData->attachment = $this->getObjectAttachment('deviation', $deviationData->id);
                $deviationData->responsible_names = $this->getDeviationResponsible($deviationData->id, $user);
                $deviationData->responsible_employee_array = $this->getDeviationResponsibleArray($deviationData->id, $user);
                $deviationData->department_names = $this->getDeviationDepartment($deviationData->id, $user);
                $deviationData->responsible_department_array = $this->getDeviationDepartmentrray($deviationData->id, $user);
                $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviationData->id)->select('id')->first(); 
                if (!empty($object)) {
                    $deviationData->object = $object;
                    $security = Security::where('object_id', $object->id)->first();
                    $deviationData->employee_array = !empty($security->employee_array) ?   json_decode($security->employee_array) :  '';
                    $deviationData->department_array = !empty($security->department_array) ?   json_decode($security->department_array) :  '';
                    $source_of_danger = SourceOfDanger::where('object_id', $object->id)->get();

                    $deviationData->sourceOfDanger = $this->getSourceOfDangerDetail($source_of_danger);

                    $deviationData->task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.source_id', $object->id)
                        ->where('objects.type', 'task')
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    if (!empty($deviationData->task_data)) {
                        $deviationData->task_data = $this->getObjectDetailInfo($deviationData->task_data, $user);
                        // $start_date = $deviationData->task_data->start_date ?? '';
                        // $start_time = $deviationData->task_data->start_time ?? '';
                        // if ($start_date && $start_time) {
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
                        //     $deviationData->task_data->status = $task_status ?? '';
                        //     if(!empty($task_status)){
                        //         $deviationData->status = $task_status ?? '';
                        //     }
                        // }
                        
                        $deviationData->task_data->responsible_names = $this->getDeviationResponsible($deviationData->id, $user);
                    }
                    $deviationData->risk_analysis = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.source_id', $object->id)
                        ->where('objects.type', 'risk-analysis')
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    if (!empty($deviationData->risk_analysis)) {
                        $deviationData->risk_analysis = $this->getObjectDetailInfo($deviationData->risk_analysis, $user);
                        $source_of_danger_risk = SourceOfDanger::where('object_id', $deviationData->risk_analysis->id)->get();
                        $deviationData->risk_analysis->source_of_danger = $this->getSourceOfDangerDetail($source_of_danger_risk);
                        $dev_task_data = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.source_id', $deviationData->risk_analysis->id)
                            ->where('objects.type', 'task')
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();
                        if (!empty($dev_task_data)) {
                            $deviationData->task_data = $this->getObjectDetailInfo($dev_task_data, $user);
                            $deviationData->task_data->responsible_names = $this->getDeviationResponsible($dev_task_data->id, $user);

                            // $start_date = $deviationData->task_data->start_date ?? '';
                            // $start_time = $deviationData->task_data->start_time ?? '';
                           
                            // if ($start_date && $start_time) {
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
                            //     $deviationData->task_data->status = $task_status ?? '';
                            //     $deviationData->status = $task_status;
                            // }
                            $deviationData->task_data->responsible_names = $this->getDeviationResponsible($dev_task_data->id, $user);
                        }
                    }
                    // $deviationData->tasks = $tasks;

                }

                // get Security information
                // $this->getSecurityObject('deviation', $deviationData);

                return $this->responseSuccess($deviationData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
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

    private function getDepartmentName($id): string
    {
        $department = Department::where('id', $id)->first();
        return $department->name ?? 'Company Admin';
    }

    private function getUserEmpName($id): string
    {
        $user = User::where('id', $id)->first();
        $f = $user->first_name ?? '';
        $l = $user->last_name ?? '';
        return $f . ' ' . $l;
    }
    private function getUserEmpRole($id): string
    {
        $user = User::where('id', $id)->first();
        $f = $user->role_id ?? '';
        return $f;
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
        if (isset($objectData['responsible']['id'])) {
            $objectData['responsible']['addedByName'] = $this->getUserName($objectData['responsible']['added_by'], $users);

            $responsibleArray = json_decode($objectData['responsible']['employee_array']);
            $departmentArray = !empty($objectData['responsible']['department_array']) ? json_decode($objectData['responsible']['department_array']) : [];

            if (in_array($userLogin['id'], $responsibleArray)) {
                $roleObject[] = 'responsible';
            }

            $employeeName = [];
            $employeeRole = [];
            if(!empty($responsibleArray)){ 
                foreach ($responsibleArray as $item) {
                    $user =  User::where('id', $item)->select('id', 'first_name', 'last_name', 'role_id')->first();
                    $employeeName[] = $this->getUserName($item, $users);
                    $employeeRole[] = !empty($user->id) ? $user->id : '';
                }
            }
            $objectData['responsible']['employeeName'] = $employeeName;
            $objectData['responsible']['employeeRole'] = $employeeRole;

            $departmentName = [];
            foreach ($departmentArray as $item) {
                $departmentName[] = $this->getDepartmentName($item);
            }
            $objectData['responsible']['departmentName'] = $departmentName;
        }
        $objectData['totalAttendee'] = 0;
        $objectData['completeAttendee'] = 0;
        if (isset($objectData['attendee']['id'])) {
            $objectData['attendee']['addedByName'] = $this->getUserName($objectData['attendee']['added_by'], $users);

            $attendeeArray = json_decode($objectData['attendee']['employee_array']);
            $departmentArray =  !empty($objectData['attendee']['department_array']) ?  json_decode($objectData['attendee']['department_array']) : [];

            if (in_array($userLogin['id'], $attendeeArray) || $userLogin['role_id'] == 2) {
                $roleObject[] = 'attendee';
            }

            $employeeName = [];
            $employeeRole = [];
            if(!empty($attendeeArray)){ 
                foreach ($attendeeArray as $item) {
                    $user =  User::where('id', $item)->select('id', 'first_name', 'last_name', 'role_id')->first();
                    $employeeName[] = $this->getUserName($item, $users);
                    $employeeRole[] = !empty($user->id) ? $user->id : '';
                }
            }
            $objectData['attendee']['employeeName'] = $employeeName;
            $objectData['attendee']['employeeRole'] = $employeeRole;
            $departmentName = [];
            foreach ($departmentArray as $item) {
                $departmentName[] = $this->getDepartmentName($item);
            }
            $objectData['attendee']['departmentName'] = $departmentName;


            if (!empty($objectData['attendee']['processing'])) {
                $objectData['totalAttendee'] = count($objectData['attendee']['processing']);
                foreach ($objectData['attendee']['processing'] as $item) {

                    $attendee_history = $this->getAttendeStatusHistory($objectData['id'], $item['added_by']);
                    $attendee['attendee_history'] = $this->getAttendeHistory($objectData['id'], $item['added_by']);
                    // $item['attendee_history'] = $this->getAttendeHistory($objectData['id'],$item['added_by']);
                    if (!empty($attendee_history) && $attendee_history->type == 'change') {
                        $attendee['status'] = 'Reassigned';
                    }
                    if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                        $attendee['status'] = 'Removed';
                    }
                    if (empty($attendee_history)) {
                        $attendee['status'] = $item['status'];
                    }
                    $attendee['attendee_id'] = $item['added_by'] ?? '';
                    $attendee['process_id'] = $item['id'];
                    // $attendee['user_id'] = $item['added_by'];
                    // $attendee['responsible_id'] = $item['responsible_id'];

                    if (!empty($objectData['responsible']['id'])) {
                        $responsibleArray = json_decode($objectData['responsible']['employee_array']);

                        if (in_array($userLogin['id'], $responsibleArray)) {
                            $roleObject[] = 'responsible';
                        }

                        $nameemps = [];
                        foreach ($responsibleArray as $newitem) {
                            $nameemps[] = $this->getUserName($newitem, $users);
                        }
                        $attendee['responsibleName'] = $nameemps;
                    }
                    // $attendee['responsible']['departmentName'] = $departmentName;


                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);

                    $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                    $attendee['attendeeDepartment'] = '';
                    if (!empty($emp)) {
                        $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                        $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                    }

                    // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users); 

                    $attendee['comment'] = $item['comment'];
                    $attendee['image'] = $item['attachment_id'];
                    // $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment_id'];
                    $attendee['required_comment'] = $objectData['attendee']['required_comment'];
                    $attendee['required_attachment'] = $objectData['attendee']['required_attachment'];

                    if ($item['status'] == 'closed') {
                        $objectData['completeAttendee'] += 1;
                    }

                    if (in_array('responsible', $roleObject) || in_array('creator', $roleObject) || $userLogin['role_id'] == 2) {
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

        if (isset($objectData['time']['id'])) {
            $objectData['start_date'] = date("Y-m-d", $objectData['time']['start_date']);
            $objectData['start_time'] = date("H:i:s", $objectData['time']['start_date']);
            // $objectData['start_date_pre'] = $objectData['start_date'];
            $objectData['deadline'] = date("Y-m-d", $objectData['time']['deadline']);
            $objectData['end_time'] = date("H:i:s", $objectData['time']['deadline']);
            // $objectData['deadline_pre'] = $objectData['deadline'];
        }

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

    public function getDeviationResponsibleArray($deviation_id, $user)
    {
        $users = User::where('company_id', $user['company_id'])->get();
        $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviation_id)->first();
        $responsibleNameArray = [];
        if (!empty($object)) {
            $responsible = DB::table('responsible')->where('object_id', $object->id)->first();
            if (!empty($responsible->employee_array)) {
                return json_decode($responsible->employee_array);
            }
        }
    }

    public function getDeviationResponsible($deviation_id, $user)
    {
        $users = User::where('company_id', $user['company_id'])->get();
        $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviation_id)->first();
        $responsibleNameArray = [];
        if (!empty($object)) {
            $responsible = DB::table('responsible')->where('object_id', $object->id)->first();

            if (!empty($responsible->employee_array)) {
                $responsibleArray = json_decode($responsible->employee_array);
                if (!empty($responsibleArray) && is_array($responsibleArray)) {
                    foreach ($responsibleArray as $item) {
                        $user = User::where('id', $item)->first();
                        if (!empty($user)) {
                            $responsibleNameArray[] = $user->first_name . ' ' . $user->last_name;
                        }
                    }
                } else {
                    $dev = Deviation::where('id', $deviation_id)->first();
                    $user = User::where('id', $dev->responsible)->first();
                    $responsibleNameArray[] = $user->first_name . ' ' . $user->last_name;
                }
            }
        }
        return $responsibleNameArray;
    }


    public function getDeviationDepartmentrray($deviation_id, $user)
    {
        $users = User::where('company_id', $user['company_id'])->get();
        $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviation_id)->first();
        $departmentNameArray = [];
        if (!empty($object)) {
            $responsible = DB::table('responsible')->where('object_id', $object->id)->first();
            if (!empty($responsible->department_array)) {
                return json_decode($responsible->department_array);
            }
        }
    }


    public function getDeviationDepartment($deviation_id, $user)
    {
        $users = User::where('company_id', $user['company_id'])->get();
        $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviation_id)->first();
        $departmentNameArray = [];
        if (!empty($object)) {
            $responsible = DB::table('responsible')->where('object_id', $object->id)->first();
            if (!empty($responsible->department_array)) {
                $responsibleArray = json_decode($responsible->department_array);
                if (!empty($responsibleArray)) {
                    foreach ($responsibleArray as $item) {
                        $data = Department::where('id', $item)->first();
                        if (!empty($data)) {
                            $departmentNameArray[] = $data->name ?? '';
                        }
                    }
                }
            }
        }
        return $departmentNameArray;
    }
   

    private function getSourceOfDangerDetail($array)
    {
        foreach ($array as $item) {
            $item['risk_level'] = $item['probability'] * $item['consequence'];
        }
        return $array;
    }
    public function update(Request $request, $id)
    {
        try {
            $deviationData = Deviation::where("id", $id)->first();
            if (empty($deviationData)) {
                return $this->responseException('Not found deviation', 404);
            }
            if (!$request->object_id) {
                return $this->responseException('Object id field is required.', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'deviation',
                'objectItem' => $deviationData,
            ];
            // Process REPORT permissions
            //    if (!$user = $this->getAuthorizedUser('deviation', 'detail', 'show', $objectInfo)) {
            if (!$user = $this->getAuthorizedUser('deviation', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $input = $request->all();

                //change responsible person

                if (!empty($input['updateResponsible'])) {
                    if ($deviationData->responsible != $input['responsible']) {
                        $deviationData->update(['responsible' => $input['responsible']]);
                        $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'deviation', 'Deviation', $deviationData['id'], $deviationData['subject'], 'responsible');
                    }

                    $tasksOfDeviation = Task::where('type', 'Deviation')->where('type_id', $deviationData->id)->get();



                    if (!empty($tasksOfDeviation) && count($tasksOfDeviation) > 0) {
                        if ($tasksOfDeviation[0]->responsible_id != $input['responsible_id']) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible_id']], 'task', 'Report', $deviationData['id'], $deviationData['subject'], 'responsible');

                            foreach ($tasksOfDeviation as $item) {
                                $item->update(['responsible_id' => $input['responsible_id']]);
                            }
                        }
                    }

                    if (!empty($input['responsible_employee_array'])) {
                        $encode = ($input['responsible_employee_array']);
                        foreach ($encode as $responsible_employee_array) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$responsible_employee_array], 'deviation', 'Deviation', $deviationData['id'], $deviationData['subject'], 'responsible');
                        }
                        $deviationData->update($input);
                        $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviationData->id)->select('id')->first();
                        if (!empty($object)) {

                            $upd = $this->updateObject($object->id, $input, $user);
                        }
                    }

                    // return $this->responseSuccess($deviationData, 201);
                }

                $rules = Deviation::$updateRules;
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $object = DB::table('objects')->where('type', 'deviation')->where('source_id', $deviationData->id)->select('id')->first();
                if (!empty($object)) {

                    $upd = $this->updateObject($object->id, $input, $user);
                }
                if ($input['status'] == 3 || $input['action'] == 'none') {
                    $input['status'] = 3;
                }

                if ($input['status'] < 3 || $input['status'] == 6) {
                    // close previous task to reopen deviation action
                    if (!empty($input['reopen']) && $input['reopen'] == 1) {
                        $deviation_tasks = Task::where('type', 'Deviation')
                            ->where('type_id', $id)->get();
                        foreach ($deviation_tasks as $task) {
                            $task->update(['status' => 5]);
                        }
                    }
                    if ($input['action'] == 'task') {
                        $companyData = Company::where("id", $user['company_id'])->first();
                        $input['industry_id'] = $companyData['industry_id'];
                        $input['status'] = 2;
                        if (!empty($input['tasks'])) {
                            $newTask = '';
                            foreach ($input['tasks'] as $task) {

                                $newObject = $this->createObject($input, $user, $id, 'task');
                                if ($newObject['id']) {
                                    $this->setTimeManagement($newObject['id']);
                                }
                                if ($newObject->time) {
                                    $input['start_time'] = $newObject->time->start_date ?? '';
                                    $input['deadline'] = $newObject->time->deadline ?? '';
                                }
                                $newTask = $this->addTasksByType($task, $input, $user['id'], $user['company_id'], 'Deviation', $deviationData['id']);
                            }
                            if ($newTask) {
                                $this->pushNotification($user['id'], $user['company_id'], 2, [$newTask['responsible_id']], 'task', 'Deviation', $deviationData['id'], $deviationData['subject'], 'responsible');
                            }
                        }
                    } elseif ($input['action'] == 'risk') {
                        $newRiskAnalysis = '';

                        if (!empty($input['source_of_danger'])) {
                            $riskAnalysisObject = ObjectItem::find($input['object_id']);

                            if (!empty($riskAnalysisObject)) {
                                $input['source_of_danger'] = $this->createObjectSourceOfDanger($input['source_of_danger'], $riskAnalysisObject, $user, true);
                            }
                        } else {
                            $inputRiskAnalysis = $input['risk_analysis'] ?? [];
                            $inputRiskAnalysis['responsible'] = $input['responsible_id'];
                            $newRiskAnalysis = $this->createRiskAnalysis($inputRiskAnalysis, $user['id'], $user['company_id']);
                            if ($input['status'] == 3 && $inputRiskAnalysis['need_to_process'] == 0) {
                                // if action risk don't need to be progressed
                                $input['status'] = 6;
                            } else {
                                $input['status'] = 2;
                            }
                        }

                        if ($newRiskAnalysis) {
                            $riskElements = $inputRiskAnalysis['risk_elements'] ?? [];
                            if (!empty($riskElements)) {
                                foreach ($riskElements as $element) {
                                    $this->createRiskElement($element, $newRiskAnalysis['id'], $user['id']);
                                }
                            }
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$inputRiskAnalysis['responsible']], 'deviation', 'Deviation', $deviationData['id'], $newRiskAnalysis['name'], 'responsible');
                        }
                    }
                }

                $deviationData->update($input);

                // update Security

                if ($user['id'] != $input['added_by']) {

                    $this->updateSecurityObject('deviation', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('deviation', $input, null);
                }

                return $this->responseSuccess($deviationData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }



    private function getAttendeStatusHistory($task_id, $user)
    {
        $attendee = AttendeeHistory::where('object_id', $task_id)->where('old_attendee_employee', $user)->first();
        if (!empty($attendee)) {
            if ($attendee->status == 'change') {
                $attendee->status = 'Reassigned';
            } else if ($attendee->status == 'remove') {
                $attendee->status = 'Removed';
            } else {
                $attendee->status = '';
            }
            return $attendee;
        } else {
            return '';
        }
    }
    private function getAttendeHistory($task_id, $user)
    {
        $attendee = AttendeeHistory::where('object_id', $task_id)->where('old_attendee_employee', $user)->first();
        $data = [];
        if (!empty($attendee)) {

            $data['id'] = $attendee->id ?? '';
            $data['object_id'] = $attendee->object_id ?? '';
            $data['type'] = $attendee->type ?? '';
            $data['reason'] = $attendee->reason ?? '';
            $data['old_attendee_department'] = !empty($attendee->old_attendee_department) ? $this->getDepartmentName($attendee->old_attendee_department) : '';
            $data['old_attendee_department_id'] = $attendee->old_attendee_department ?? '';
            $data['old_attendee_employee'] = !empty($attendee->old_attendee_employee) ? $this->getUserEmpName($attendee->old_attendee_employee) : '';
            $data['old_attendee_employee_id'] = $attendee->old_attendee_employee ?? '';
            $data['new_attendee_department'] = !empty($attendee->new_attendee_department) ? $this->getDepartmentName($attendee->new_attendee_department) : '';
            $data['new_attendee_department_id'] = $attendee->new_attendee_department ?? '';
            $data['new_attendee_employee'] = !empty($attendee->new_attendee_employee) ? $this->getUserEmpName($attendee->new_attendee_employee) : '';
            $data['new_attendee_employee_id'] = $attendee->new_attendee_employee ?? '';
            $data['transfer_information'] = $attendee->transfer_information ?? '';
            $data['transfer_feedback'] = $attendee->transfer_feedback ?? '';
            $data['transfer_attachment'] = $attendee->transfer_attachment ?? '';
            $data['created_at'] = $attendee->created_at ?? '';
            $data['updated_at'] = $attendee->updated_at ?? '';
            $data['updated_by'] = !empty($attendee->updated_by) ? $this->getUserEmpName($attendee->updated_by) : '';
        }
        return $data;
    }


    private function updateObject($id, $input, $user)
    {
        $inputTemp = $input;

        $objectData = ObjectItem::find($id);
        if (empty($objectData)) {
            return $this->responseException('Not found object', 404);
        }

        $rules = ObjectItem::$updateRules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        // $objectData->update($input); 
        // if (!empty($input['connectToArray'])) {
            $input['connectToArray'] = isset($input['connectToArray']) ? $input['connectToArray'] : [];
            $this->updateConnectToObject($user, $objectData['id'], $objectData['type'], $input['connectToArray']);
        // }

        // if object is NOT a Resource
        if (!$objectData['is_template']) {

            // Responsible
            if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
                return $objectData->responsible = $this->createObjectResponsible($inputTemp, $objectData, $user, true);
            }

            // Attendee
            if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
                $objectData->attendee = $this->createObjectAttendee($inputTemp, $objectData, $user, true);

                // Attendee processing
                $attendee = Attendee::where('object_id', $objectData['id'])->first();
                $objectData->processing = $this->createObjectAttendeeProcessing($attendee, $user, true);
            }
        }

        // Source of danger
        if (!empty($inputTemp['type']) && $inputTemp['type'] == 'risk-analysis') {
            $objectData->source_of_danger = $this->createObjectSourceOfDanger($inputTemp['source_of_danger'], $objectData, $user, true);
        }

        return $objectData;
    }


    private function createObjectSourceOfDanger($inputArray, $object, $user, $requestEdit = false)
    {

        $array = $inputArray;
        if ($requestEdit) { // edit 
            $oldSources = SourceOfDanger::where('object_id', $object['id'])->pluck('id')->toArray();

            // old array source of danger
            if (!empty($oldSources)) {
                foreach ($oldSources as $oldSource) {
                    $key = array_search($oldSource, array_column($array, 'id'));
                    if ($key > -1) {
                        // update exist source of danger
                        $source = SourceOfDanger::find($oldSource);
                        if (empty($source)) {
                            return $this->responseException('Not found source of danger', 404);
                        }

                        $updateSource['name'] = $array[$key]['name'];
                        $updateSource['probability'] = $array[$key]['probability'];
                        $updateSource['consequence'] = $array[$key]['consequence'];
                        $updateSource['comment'] = $array[$key]['comment'];
                        $updateSource['need_to_process'] = $array[$key]['need_to_process'];
                        $updateSource['visible_to_others'] = $array[$key]['visible_to_others'];

                        $sourceRules = SourceOfDanger::$updateRules;
                        $sourceValidator = Validator::make($updateSource, $sourceRules);
                        if ($sourceValidator->fails()) {
                            $errors = ValidateResponse::make($sourceValidator);
                            return $this->responseError($errors, 400);
                        }
                        $source->update($updateSource);
                        $array[$key]['updated'] = true;
                    } else {
                        // delete exist source of danger
                        $deleteSource = SourceOfDanger::find($oldSource);
                        if (empty($deleteSource)) {
                            return $this->responseException('Not found source of danger', 404);
                        }
                        SourceOfDanger::destroy($oldSource);
                    }
                }
            }
            // new array source of danger
            if (!empty($array)) {
                foreach ($array as $newSource) {
                    if (!isset($newSource['updated'])) {
                        $this->createItemSourceOfDanger($newSource, $object, $user);
                    }
                }
            }
        } else {
            // add new object
            foreach ($inputArray as $item) {
                $this->createItemSourceOfDanger($item, $object, $user);
            }
        }
        return $array;
    }

    // create single item source of danger
    private function createItemSourceOfDanger($item, $object, $user)
    {

        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        if ($object['source'] == 'risk-analysis') {
            $input['object_id'] = $object['source_id'];
        } else {
            $input['object_id'] = $object['id'];
        }
        $input['name'] = $item['name'];
        $input['probability'] = $item['probability'];
        $input['consequence'] = $item['consequence'];
        $input['comment'] = $item['comment'];
        $input['need_to_process'] = $item['need_to_process'];
        $input['visible_to_others'] = $item['visible_to_others'] ?? 0;
        $rules = SourceOfDanger::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        return SourceOfDanger::create($input);
    }


    private function createObjectTimeManagement($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];

        if (!empty($inputObject['start_time']) && !empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date'] . ' ' . $inputObject['start_time']);
            $input['start_time'] = $inputObject['start_time'];
        } elseif (!empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date']); 
        } else {
            $input['start_date'] = strtotime("today");
            $input['start_time'] = "00:00";
        }

        if (!empty($inputObject['end_time']) && !empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline'] . ' ' . $inputObject['end_time']);
            $input['end_time'] = $inputObject['end_time'];
        } elseif (!empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline']);
        } else {
            $input['deadline'] = strtotime("+1 day", $input['start_date']);
            $input['end_time'] = "00:00";
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



    private function createObject($input, $user, $source_id, $type)
    {
        $inputTemp = $input;

        $rules = ObjectItem::$rules;

        $input['added_by'] = $user['id'];
        $input['type'] = $type;
        $input['source_id'] = $source_id;
        // $input['required_attachment'] = 0;
        // $input['required_comment'] =  0;

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        $newObject = ObjectItem::create($input);

        if (isset($input['connectToArray']) && !empty($input['connectToArray'])) {
            $this->addConnectToObject($user, $newObject['id'], $newObject['type'], $input['connectToArray']);
        }

        
        if ($type == 'task') {
            $newObject->time = $this->createObjectTimeManagement($inputTemp, $newObject, $user);
        }

        if ($type == 'risk-analysis') {
            $this->addRiskElementToRiskAnalysis($input['risk_element_array'], $newObject);
            if (!empty($input['source_of_danger'])) {
                $newObject->source_of_danger = $this->createObjectSourceOfDanger($input['source_of_danger'], $newObject, $user);
            }
        }
        // Responsible
        if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
            $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user, true);
        }

        return $newObject;
    }



    private function createObjectResponsible($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $object['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];


        if (!empty($inputObject['nearest_manager'])) {
            $input['employee_array'] = json_encode(array($user->employee->nearest_manager));
            if (empty($user->employee->nearest_manager)) {
                $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                if ($companyAdmin) {
                    $input['employee_array'] = json_encode(array($companyAdmin->id));
                }
            }
        }

        if ($inputObject['isDefaultResponsible']) {
            $input['employee_array'] = json_encode(array($user['id']));
        }
        if (!empty($inputObject['responsible_employee_array'])) {
            if (!is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] =  ($inputObject['responsible_employee_array']);
            } elseif (is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] =  json_encode($inputObject['responsible_employee_array']);
            }
            $input['employee_array'] = ($inputObject['responsible_employee_array']);
        }
        if (!empty($inputObject['responsible_department_array'])) {
            if (!is_array($inputObject['responsible_department_array'])) {
                $inputObject['responsible_department_array'] =  ($inputObject['responsible_department_array']);
            } elseif (is_array($inputObject['responsible_department_array'])) {
                $inputObject['responsible_department_array'] =  json_encode($inputObject['responsible_department_array']);
            }
            $input['department_array'] = ($inputObject['responsible_department_array']);
        }
        // } elseif (!empty($inputObject['responsible_department_array'])) {   // choose department
        //     $responsible = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
        //         ->where('users.company_id', $object['company_id'])
        //         ->whereIn('users.role_id', [2, 3])
        //         ->whereIn('employees.department_id', $inputObject['responsible_department_array'])
        //         ->pluck('user_id')
        //         ->toArray();
        //     if (!is_array($responsible)) {
        //         $responsible = array($responsible);
        //     }
        //     $input['employee_array'] = json_encode($responsible);
        if ($requestEdit) {
            Responsible::where('object_id', $object['id'])->delete();
        }

        $rules = Responsible::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $responsible = Responsible::create($input);
        $this->requestPushNotification($user['id'], $user['company_id'], json_decode($responsible['employee_array']), 'notification', $object, 'responsible');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/deviations/{id}",
     *     tags={"Deviations"},
     *     summary="Delete deviation API",
     *     description="Delete deviation API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteDeviationAPI",
     *     @OA\Parameter(
     *         description="deviation id",
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
            $deviationData = Deviation::where("id", $id)->first();
            if (empty($deviationData)) {
                return $this->responseException('Not found deviation', 404);
            }
            Deviation::destroy($id);
            return $this->responseSuccess("Delete deviation success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
