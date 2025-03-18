<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\ObjectItem;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentNew;
use App\Models\RiskAnalysis;
use App\Models\ObjectOption;
use App\Models\Attachment;
use App\Models\Attendee;
use App\Models\AttendeeHistory;
use App\Models\AttendeeProcessing;
use App\Models\SourceOfDanger;
use App\Models\Security;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use App\Models\CategoryV2;
use App\Models\Department;
use App\Models\JobTitle;
use Validator;
use JWTAuth;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Models\Report;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Report APIs",
 * )
 **/
class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/reports",
     *     tags={"Reports"},
     *     summary="Get reports",
     *     description="Get reports list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getReports",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            // if (!$user = $this->getAuthorizedUser('report checklist', 'view', 'index', 1))
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else { 
                $checkPermission = $request->checkPermission;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $resultIds = Report::pluck('id')->toArray(); 
                $resultIds = array_filter($resultIds, function ($id) use ($user){
                    return in_array($user->id, Helper::checkReportDisplayAccess($id)); 
                });
                if ($checkPermission == 'allow'){
                    $result = Report::where('company_id', $user->company_id)
                        ->whereIn('id',$resultIds)
                        ->with([ 'user']);

                } else {
                    $result = Report::where('added_by', $user['id'])
                        ->whereIn('id',$resultIds)
                        ->with([ 'user']);
                }
                if(!empty($request->startDate) && !empty($request->endDate)){
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $result->whereBetween('reports.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }
                if(!empty($request->reported_by)){ 
                    $result->where('reports.added_by',$request->reported_by);
                }
                if(!empty($request->by_name)){
                    $result = $result->whereRaw("JSON_EXTRACT(reports.checklist_info, '$.name') LIKE ?", ["%$request->by_name%"]);
                }
                $result = $result->orderBy('id','desc')->paginate(10);

                if($result){
                    // $result = $this->filterViewList('report checklist', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $report){
                        if(!empty($report->checklist_id)){
                            $public = Checklist::where('id', $report->checklist_id)->first();
                            $report->is_public = $public->is_public ?? 0;
                            $report->is_shared = $public->is_shared ?? 0;
                        }
                        $checklistInfo = json_decode($report['checklist_info']);
                        $report->name = $checklistInfo->name ?? '';
                        $report->checklist_id = $checklistInfo->checklist_id ?? '';
                        
                        $report->category = CategoryV2::find($report['category_id']);

                        $reportedUser = User::where('id',$report['added_by'])->select('first_name','last_name')->first();
                        $report->added_by_name = $reportedUser['first_name'] . " " . $reportedUser['last_name'];

                        $responsibleUser = User::where('id',$report['responsible'])->select('first_name','last_name')->first();
                        $report->responsible_name = $responsibleUser['first_name'] . " " . $responsibleUser['last_name'];
                        $taskIds = Helper::checkReportTaskRiskDisplayAccess($report->id,$user);
                        // if($user->role_id == 4){
                            $objectData = ObjectItem::whereIn('id',$taskIds)->where('type', 'task')->with(['attendee', 'responsible', 'time'])->get();
                        // }else{
                        //     $objectData = ObjectItem::where('source_id', $report->id)->where('source', 'report')->with(['attendee', 'responsible', 'time'])->get();
                        // }
                        if(isset($objectData) && !empty($objectData->all())){

                            if(count($objectData) > 1){
                                $newCnt = 0; 
                                $ongoingCnt = 0; 
                                $completedCnt = 0; 
                                $approvedCnt = 0;
                                $overdueCnt = 0;
                                $completedOverdueCnt = 0;
                                $disapprovedCnt = 0;
                                $closedCnt = 0;
                                $disapprovedWithExtendedCnt = 0;
                                $requestCnt = 0;
                                $timelineDisapprovedCnt = 0;
                                $disapprovedOverdueCnt = 0;
                                $approvedOverdueCnt = 0;
                                $removedCnt = 0;
                                $reassignedCnt = 0;
                                foreach($objectData as $object){
                                    $dateTimeObj = $this->getDateTimeBasedOnTimezone($object, $user);
                                    $status = $this->getObjectStatus($object,$dateTimeObj);  
                                    $attendee_info = Attendee::where('object_id', $object->id)->latest()->first();

                                    $task_attendee = $object->attendee ?? null;
                                    $task_responsible = $object->responsible ?? null;

                                    $is_self_rs_and_ca = false;
                                    if((isset($task_attendee->employee_array) && !empty($task_attendee->employee_array)) && (isset($task_responsible->employee_array) && !empty($task_responsible->employee_array))) {
                                        $attn_emp_arr = json_decode($task_attendee->employee_array);
                                        $resp_emp_arr = json_decode($task_responsible->employee_array);
                                        sort($attn_emp_arr);
                                        sort($resp_emp_arr);

                                        $is_self_rs_and_ca = $attn_emp_arr == $resp_emp_arr ? true : false;
                                    }

                                    if((isset($attendee_info) && !empty($attendee_info)) && !$is_self_rs_and_ca) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) {
                                                $attendee_history = $this->getAttendeStatusHistory($object->id, $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                            
                                                $object->status = $attendee_processing->status;
                                                $status = $this->getObjectStatus($object,$dateTimeObj);  
                                            }
                                            // if($user->role_id == 4){
                                            //     $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->get();
            
                                            //     if(isset($attendee_processing) && !empty($attendee_processing)) {
                                            //         foreach ($attendee_processing as $key => $attendee) {
                                            //             $attendee_history = $this->getAttendeStatusHistory($object->id, $user->id);
                                            //             if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                            //                 $attendee->status = 'Reassigned';
                                            //             }
                                            //             if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                            //                 $attendee->status = 'Removed';
                                            //             }
                                                
                                            //             $object->status = $attendee->status;
                                            //             $status = $this->getObjectStatus($object,$dateTimeObj); 
                                            //         }
                                                     
            
                                            //     }
                                            // }
    
                                    } 
                        
                                    if ($object->type == 'task') {
                                        if ($status == 'new') {
                                            $newCnt++;
                                        } elseif ($status == 'ongoing') {
                                            $ongoingCnt++;
                                        } elseif ($status == 'completed') {
                                            $completedCnt++;
                                        } elseif ($status == 'approved') {
                                            $approvedCnt++;
                                        } elseif ($status == 'overdue') {
                                            $overdueCnt++;
                                        } elseif($status == 'completed_overdue') {
                                            $completedOverdueCnt++;
                                        } elseif($status == 'disapproved') {
                                            $disapprovedCnt++;
                                        } elseif($status == 'disapproved_overdue') {
                                            $disapprovedOverdueCnt++;
                                        } elseif($status == 'approved_overdue') {
                                            $approvedOverdueCnt++;
                                        } elseif($status == 'disapproved_with_extended') {
                                            $disapprovedWithExtendedCnt++;
                                        } elseif($status == 'request') {
                                            $requestCnt++;
                                        } elseif($status == 'timeline_disapproved') {
                                            $timelineDisapprovedCnt++;
                                        } elseif($status == 'Removed') {
                                            $removedCnt++;
                                        } elseif($status == 'Reassigned') {
                                            $reassignedCnt++;
                                        } elseif($status == 'closed') {
                                            $closedCnt++;
                                        }
                                    }
                                }
                                if ($overdueCnt > 0) {
                                    $report->status = 'overdue';
                                } elseif ($disapprovedOverdueCnt > 0) {
                                    $report->status = 'disapproved_overdue';
                                } elseif ($disapprovedCnt > 0) {
                                    $report->status = 'ongoing'; // disapproved but checklist status will show ongoing
                                } elseif ($disapprovedWithExtendedCnt > 0) {
                                    $report->status = 'overdue'; // disapproved_with_extended but checklist status will show ongoing overdue
                                } elseif ($overdueCnt > 0) {
                                    $report->status = 'overdue'; // Ongoing Extend Deadline
                                } elseif ($ongoingCnt > 0) {
                                    $report->status = 'ongoing';
                                } elseif ($timelineDisapprovedCnt > 0) {
                                    $report->status = 'timeline_disapproved'; // status will show ongoing
                                } elseif ($completedOverdueCnt > 0) {
                                    $report->status = 'completed_overdue';
                                } elseif ($completedCnt > 0) {
                                    $report->status = 'completed';
                                } elseif ($approvedOverdueCnt > 0) {
                                    $report->status = 'approved_overdue';
                                } elseif ($approvedCnt > 0) {
                                    $report->status = 'approved';
                                } elseif ($requestCnt > 0) {
                                    $report->status = 'request';
                                } elseif ($removedCnt > 0) {
                                    $report->status = 'Removed';
                                } elseif ($reassignedCnt > 0) {
                                    $report->status = 'Reassigned';
                                } elseif ($closedCnt > 0) {
                                    $report->status = 'closed';
                                } elseif ($newCnt > 0) {
                                    $report->status = 'new';
                                } else {
                                    $report->status = $report->status;
                                }
                            } else {
                                // if($user->role_id == 4){
                                    $ObjData = ObjectItem::whereIn('id',$taskIds)->where('type', 'task')->with(['attendee', 'responsible', 'time'])->first();
                                // }else{
                                //     $ObjData = ObjectItem::where('source_id', $report->id)->where('source', 'report')->with(['attendee', 'responsible', 'time'])->first();
                                // }
                                $dateTimeObj = $this->getDateTimeBasedOnTimezone($ObjData, $user);

                                $status = $this->getObjectStatus($ObjData,$dateTimeObj);  
                                $attendee_info = Attendee::where('object_id', $ObjData['id'])->latest()->first();

                                $task_attendee = $ObjData->attendee ?? null;
                                $task_responsible = $ObjData->responsible ?? null;

                                $is_self_rs_and_ca = false;
                                if((isset($task_attendee->employee_array) && !empty($task_attendee->employee_array)) && (isset($task_responsible->employee_array) && !empty($task_responsible->employee_array))) {
                                    $attn_emp_arr = json_decode($task_attendee->employee_array);
                                    $resp_emp_arr = json_decode($task_responsible->employee_array);
                                    sort($attn_emp_arr);
                                    sort($resp_emp_arr);

                                    $is_self_rs_and_ca = $attn_emp_arr == $resp_emp_arr ? true : false;
                                }
                                    

                                if((isset($attendee_info) && !empty($attendee_info)) && !$is_self_rs_and_ca) {
                                        $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                        if(isset($attendee_processing) && !empty($attendee_processing)) {
                                            $attendee_history = $this->getAttendeStatusHistory($ObjData->id, $user->id);
                                            if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                $attendee_processing->status = 'Reassigned';
                                            }
                                            if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                $attendee_processing->status = 'Removed';
                                            }
                                        
                                            $ObjData->status = $attendee_processing->status;
                                            $status = $this->getObjectStatus($ObjData,$dateTimeObj);  
                                        }
                                }
                                $report->status = $status;
                            }
                            $report['is_task'] = true;
                           
                        }else{
                            $report['is_task'] = false;
                            if($report->status == 1){
                                $report->status = 'new';
                            } else if($report->status == 2){
                                $report->status = 'completed';
                            } else if($report->status == 3){
                                $report->status = 'closed';
                            }
                        }
                    
                    }
                    return $result;
                // return $this->responseSuccess($result,201);

                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function filterRecord(Request $request)
    {
        try{
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else { 
                $checkPermission = $request->checkPermission;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $resultIds = Report::pluck('id')->toArray(); 
                $resultIds = array_filter($resultIds, function ($id) use ($user){
                    return in_array($user->id, Helper::checkReportDisplayAccess($id)); 
                });
                if ($checkPermission == 'allow'){
                    $result = Report::where('company_id', $user->company_id)
                        ->whereIn('id',$resultIds)
                        ->with([ 'user']);

                } else {
                    $result = Report::where('added_by', $user['id'])
                        ->whereIn('id',$resultIds)
                        ->with([ 'user']);
                }
                if(!empty($request->startDate) && !empty($request->endDate)){
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $result->whereBetween('reports.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }
                if(!empty($request->reported_by)){ 
                    $result->where('reports.added_by',$request->reported_by);
                }
                if(isset($request->department) && !empty($request->department)){
                    $department_detail = Department::where('id', $request->department)->with('employees')->first();
                    if(isset($department_detail->employees)) {
                        $employees_id = $department_detail->employees->pluck('user_id')->toArray();
                        $result->whereIn('reports.added_by', $employees_id)->where('reports.company_id', $user->company_id);
                    }
                }
                if(isset($request->job_title) && !empty($request->job_title)){
                    $job_title_detail = JobTitle::where('id', $request->job_title)->with('employees')->first();
                    if(isset($job_title_detail->employees)) {
                        $employees_id = $job_title_detail->employees->pluck('user_id')->toArray();
                        $result->whereIn('reports.added_by', $employees_id)->where('reports.company_id', $user->company_id);
                    }
                }
                if(!empty($request->by_name)){
                    $result = $result->whereRaw("JSON_EXTRACT(reports.checklist_info, '$.name') LIKE ?", ["%$request->by_name%"]);
                }
                $result = $result->orderBy('id','desc')->get();

                if($result){
                    foreach ($result as $report){
                        if(!empty($report->checklist_id)){
                            $public = Checklist::where('id', $report->checklist_id)->first();
                            $report->is_public = $public->is_public ?? 0;
                            $report->is_shared = $public->is_shared ?? 0;
                        }
                        $checklistInfo = json_decode($report['checklist_info']);
                        $report->name = $checklistInfo->name ?? '';
                        $report->checklist_id = $checklistInfo->checklist_id ?? '';
                        
                        $report->category = CategoryV2::find($report['category_id']);

                        $reportedUser = User::where('id',$report['added_by'])->select('first_name','last_name')->first();
                        $report->added_by_name = $reportedUser['first_name'] . " " . $reportedUser['last_name'];

                        $responsibleUser = User::where('id',$report['responsible'])->select('first_name','last_name')->first();
                        $report->responsible_name = $responsibleUser['first_name'] . " " . $responsibleUser['last_name'];
                        $taskIds = Helper::checkReportTaskRiskDisplayAccess($report->id,$user);
                        $objectData = ObjectItem::whereIn('id',$taskIds)->where('type', 'task')->with(['attendee', 'responsible', 'time'])->get();
                      
                        if(isset($objectData) && !empty($objectData->all())){

                            if(count($objectData) > 1){
                                $newCnt = 0; 
                                $ongoingCnt = 0; 
                                $completedCnt = 0; 
                                $approvedCnt = 0;
                                $overdueCnt = 0;
                                $completedOverdueCnt = 0;
                                $disapprovedCnt = 0;
                                $closedCnt = 0;
                                $disapprovedWithExtendedCnt = 0;
                                $requestCnt = 0;
                                $timelineDisapprovedCnt = 0;
                                $disapprovedOverdueCnt = 0;
                                $approvedOverdueCnt = 0;
                                $removedCnt = 0;
                                $reassignedCnt = 0;
                                foreach($objectData as $object){
                                    $dateTimeObj = $this->getDateTimeBasedOnTimezone($object, $user);
                                    $status = $this->getObjectStatus($object,$dateTimeObj);  
                                    $attendee_info = Attendee::where('object_id', $object->id)->latest()->first();
                                    if(isset($attendee_info) && !empty($attendee_info)) {
                                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                            if(isset($attendee_processing) && !empty($attendee_processing)) {
                                                $attendee_history = $this->getAttendeStatusHistory($object->id, $user->id);
                                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                    $attendee_processing->status = 'Reassigned';
                                                }
                                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                    $attendee_processing->status = 'Removed';
                                                }
                                            
                                                $object->status = $attendee_processing->status;
                                                $status = $this->getObjectStatus($object,$dateTimeObj);  
                                            }
                                    } 
                        
                                    if ($object->type == 'task') {
                                        if ($status == 'new') {
                                            $newCnt++;
                                        } elseif ($status == 'ongoing') {
                                            $ongoingCnt++;
                                        } elseif ($status == 'completed') {
                                            $completedCnt++;
                                        } elseif ($status == 'approved') {
                                            $approvedCnt++;
                                        } elseif ($status == 'overdue') {
                                            $overdueCnt++;
                                        } elseif($status == 'completed_overdue') {
                                            $completedOverdueCnt++;
                                        } elseif($status == 'disapproved') {
                                            $disapprovedCnt++;
                                        } elseif($status == 'disapproved_overdue') {
                                            $disapprovedOverdueCnt++;
                                        } elseif($status == 'approved_overdue') {
                                            $approvedOverdueCnt++;
                                        } elseif($status == 'disapproved_with_extended') {
                                            $disapprovedWithExtendedCnt++;
                                        } elseif($status == 'request') {
                                            $requestCnt++;
                                        } elseif($status == 'timeline_disapproved') {
                                            $timelineDisapprovedCnt++;
                                        } elseif($status == 'Removed') {
                                            $removedCnt++;
                                        } elseif($status == 'Reassigned') {
                                            $reassignedCnt++;
                                        } elseif($status == 'closed') {
                                            $closedCnt++;
                                        }
                                    }
                                }
                                if ($overdueCnt > 0) {
                                    $report->status = 'overdue';
                                } elseif ($completedOverdueCnt > 0) {
                                    $report->status = 'completed_overdue';
                                } elseif ($approvedOverdueCnt > 0) {
                                    $report->status = 'approved_overdue';
                                } elseif ($disapprovedOverdueCnt > 0) {
                                    $report->status = 'disapproved_overdue';
                                } elseif ($ongoingCnt > 0) {
                                    $report->status = 'ongoing';
                                } elseif ($timelineDisapprovedCnt > 0) {
                                    $report->status = 'timeline_disapproved';
                                } elseif ($disapprovedWithExtendedCnt > 0) {
                                    $report->status = 'disapproved_with_extended';
                                } elseif ($disapprovedCnt > 0) {
                                    $report->status = 'disapproved';
                                } elseif ($completedCnt > 0) {
                                    $report->status = 'completed';
                                } elseif ($approvedCnt > 0) {
                                    $report->status = 'approved';
                                } elseif ($disapprovedCnt > 0) {
                                    $report->status = 'disapproved';
                                } elseif ($requestCnt > 0) {
                                    $report->status = 'request';
                                } elseif ($removedCnt > 0) {
                                    $report->status = 'Removed';
                                } elseif ($reassignedCnt > 0) {
                                    $report->status = 'Reassigned';
                                } elseif ($closedCnt > 0) {
                                    $report->status = 'closed';
                                } elseif ($newCnt > 0) {
                                    $report->status = 'new';
                                } else {
                                    $report->status = $report->status;
                                }
                            } else {
                                $ObjData = ObjectItem::whereIn('id',$taskIds)->where('type', 'task')->with(['attendee', 'responsible', 'time'])->first();
                                $dateTimeObj = $this->getDateTimeBasedOnTimezone($ObjData, $user);

                                $status = $this->getObjectStatus($ObjData,$dateTimeObj);  
                                $attendee_info = Attendee::where('object_id', $ObjData['id'])->latest()->first();
                                if(isset($attendee_info) && !empty($attendee_info)) {
                                        $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                        if(isset($attendee_processing) && !empty($attendee_processing)) {
                                            $attendee_history = $this->getAttendeStatusHistory($ObjData->id, $user->id);
                                            if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                                $attendee_processing->status = 'Reassigned';
                                            }
                                            if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                                $attendee_processing->status = 'Removed';
                                            }
                                        
                                            $ObjData->status = $attendee_processing->status;
                                            $status = $this->getObjectStatus($ObjData,$dateTimeObj);  
                                        }
                                }
                                $report->status = $status;
                            }
                            $report['is_task'] = true;
                           
                        }else{
                            $report['is_task'] = false;
                            if($report->status == 1){
                                $report->status = 'new';
                            } else if($report->status == 2){
                                $report->status = 'completed';
                            } else if($report->status == 3){
                                $report->status = 'closed';
                            }
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
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
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

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $current_items = array_slice($items, $perPage * ($page - 1), $perPage);
        $options = [
            'path' => url('api/v1/reports/filter')
        ];
        $paginator = new LengthAwarePaginator($current_items, count($items), $perPage, $page, $options);
        $paginator->appends(request()->all());
        return $paginator;
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
     * @OA\Post(
     *     path="/api/v1/reports",
     *     tags={"Reports"},
     *     summary="Create new report",
     *     description="Create new report",
     *     security={{"bearerAuth":{}}},
     *     operationId="createReport",
     *     @OA\RequestBody(
     *         description="report schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Report")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Report $report)
    {
        
        try {
            $input = $request->all();
            if (!$user = $this->getAuthorizedUser('report checklist', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Report::$rules;
                
                $checklist = $input['checklist'];
                $checklistData = Checklist::find($checklist); 
                if (empty($checklistData)) {
                    return $this->responseException('Not found checklist', 404);
                }
                
                $topicArray = array();
                $topicData = Topic::where('checklist_id', $checklist)->with(['questions'])->get(); 
                if (!empty($topicData)){
                    foreach ($topicData as $topic){
                        if (!empty($topic)){
                            $topicItemArray = array(
                                'id' => $topic->id,
                                'name' => $topic->name,
                                'description' => $topic->description,
                                'questions' => array(),
                            );

                            $questions = $topic->questions;
                            if (!empty($questions)){
                                foreach ($questions as $question){
                                    if (!empty($question)){
                                        $questionArray = array(
                                            'id' => $question->id,
                                            'name' => $question->name,
                                            'description' => $question->description,
                                            'status' => $question->status
                                        );

                                        array_push($topicItemArray['questions'], $questionArray);
                                    }
                                }
                            }
                            array_push($topicArray, $topicItemArray);
                        }
                    }
                }

                $checklistInfoArray = array(
                    'name' => $checklistData->name,
                    'description' => $checklistData->description,
                    'status' => $checklistData->status,
                    'industry_id' => $checklistData->industry_id,
                    'added_by' => $checklistData->added_by,
                    'topics' => $topicArray,
                    'checklist_id' => $checklistData->id
                );
                $checklistInfo = json_encode($checklistInfoArray); 
                $department = $input['department'];
                $job_title = $input['job_title'];
               

                
                // $input['answers'][0]['image'] = '';
                // if(!empty($request->file('file'))){
                //     $path = Storage::disk('public')->putFile('/' . $user->company_id, $request->file('file'));
                //     $baseUrl = config('app.app_url');
                //     $input['answers'][0]['image'] = $baseUrl. "/api/v1/uploads/". $path;
                // } 
                $newData = [];
                $allData = [];
                if(!empty($input['answers'])){
                    foreach($input['answers'] as $k=> $ans){
                        $newData['file'] = '';  
                        if(!empty($ans['file'])){ 
                            $folderPath = "uploads/";
                            $base64Image = explode(";base64,", $ans['file']);
                            $explodeImage = explode("image/", $base64Image[0]);
                            $imageType = $explodeImage[1];
                            $image_base64 = base64_decode($base64Image[1]);
                            $file = $folderPath . uniqid() . '.'.$imageType;
                            $r = file_put_contents($file, $image_base64);
                            $baseUrl = config('app.app_url');
                            $newData['file'] = $baseUrl.'/'.$file;  
                        }
                        $newData['topic_id'] = $ans['topic_id'];  
                        $newData['question_id'] = $ans['question_id'];   
                        $newData['answer'] = $ans['answer'];   
                        $newData['description'] = $ans['description'];   
                        $newData['action'] = $ans['action'];   
                        $newData['task_id'] = $ans['task_id'];   
                        $newData['risk_id'] = $ans['risk_id'];   
                        $newData['answer_name'] = $ans['answer_name'];    
                        $allData[] = $newData;
                    }
                } 
                $answers = json_encode($allData);
                $input['added_by'] = $user['id'];
                $input['company_id'] = $user->company_id;
                $input['category_id'] = $checklistData->category_id;

                if ($department){
                    $input['department_id'] = $department;
                }

                if ($job_title){
                    $input['job_title_id'] = $job_title;
                }

                $input['answer'] = $answers;
                $input['checklist_info'] = $checklistInfo;
                $input['checklist_id'] = $checklistData->id; 
                // if ($user->employee->nearest_manager) {
                //     $input['responsible'] = $user->employee->nearest_manager;
                // } else 
                if(!empty($user->role_id) && $user->role_id == 3){
                    $input['responsible'] = $user->id;
                }else if($user->employee->nearest_manager){
                    $input['responsible'] = $user->employee->nearest_manager; 
                } else {
                    $companyAdmin = User::where('company_id', $user->company_id)->where('role_id', 2)->first();
                    if ($companyAdmin) {
                        $input['responsible'] = $companyAdmin->id;
                    }
                } 
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newReport = Report::create($input); 
                $this->pushNotification($user['id'], $user->company_id, 2, [$input['responsible']], 'report', 'Report', $newReport['id'], $checklistData['name'], 'responsible');
                $objChecklist = ObjectItem::where('source_id',$checklist)->where('type','checklist')->first(); 
                if(isset($objChecklist) && !empty($objChecklist)){
                    $security = Security::where('object_id',$objChecklist->id)->where('object_type','Checklist')->first();
                    if(isset($security) && !empty($security)){
                        if(gettype($security['department_array']) == 'string') {
                            $security['department_array'] = json_decode($security['department_array']);
                        }
                        if(gettype($security['employee_array']) == 'string') {
                            $security['employee_array'] = json_decode($security['employee_array']);
                        }
                        $security['object_type'] = 'report checklist';
                        $this->createSecurityObject($newReport,$security);
                    }
                }
                return $this->responseSuccess($newReport,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/{id}",
     *     tags={"Reports"},
     *     summary="Get report by id",
     *     description="Get report by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getReportByIdAPI",
     *     @OA\Parameter(
     *         description="report id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
            $taskIds = Helper::checkReportTaskDisplayAccess($id,$user);
            $reportData = Report::leftJoin('departments','reports.department_id','=','departments.id')
                ->leftJoin('job_titles','reports.job_title_id','=','job_titles.id')
                ->leftJoin('users','users.id','=','reports.responsible')
                ->where('reports.id', $id)
                ->with(['tasks' => function($query) {
                    $query->where('tasks.status', '<', 5)
                        ->with(['task_assignees']);
                }, 'risk_analysis' => function($queryRisk) {
                    $queryRisk->where('risk_analysis.status', '<', 5)
                        ->with(['tasks' => function($queryRiskTask) {
                        $queryRiskTask->with(['task_assignees']);
                    }, 'elements']);
                }, ])
                // ->select('reports.*','projects.name as project_name','departments.name as department_name','job_titles.name as job_title_name')
                ->select('reports.*','departments.name as department_name','job_titles.name as job_title_name','users.first_name as responsible_first_name','users.last_name as responsible_last_name')
                ->first();
            if (empty($reportData)) {
                return $this->responseException('Not found report', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'report checklist',
                'objectItem' => $reportData,
            ];
            // if (!$user = $this->getAuthorizedUser('report checklist', 'detail', 'show', $objectInfo))
            
                $answers = json_decode($reportData->answer);
                $reportData->answer = null;
                // $reportData->answer = json_decode($reportData->answer);

                // $topics = Topic::where('checklist_id',  $reportData->checklist_id)->get();
                // $reportData->topics = $topics;
               
                /// return response()->json(['asd'=>$reportData]);
                // $object = DB::table('objects')->where('source_id', $reportData['checklist_id'])->where('type', 'checklist')->first();
                // $security = Security::where('object_type', 'checklist')
                // ->where('object_id', $object->id)
                // ->first(); 
                // if (!empty($security)) {
                //     $reportData->is_shared = $security['is_shared'];
                // }
                $objects = DB::table('objects')->where('source_id',$id)->where('source','report')->first(); 
                if(!empty($reportData->checklist_id)){
                    $public = Checklist::where('id', $reportData->checklist_id)->first();
                    $reportData->is_public = $public->is_public ?? 0;
                    $reportData->is_shared = $public->is_shared ?? 0;
                }
              
                $reportData->checklist_info = json_decode($reportData->checklist_info);
                $topics = $reportData->checklist_info->topics;

                $count_done = 0;
                $filteredData = [];      
                foreach ($topics as $topic){
                    foreach ($answers as $answer){
                        if ($answer->topic_id == $topic->id){
                            $questions = $topic->questions;
                            foreach ($questions as $k =>$question){ 
                                if ($question->id == $answer->question_id){
                                    $question->topic_name = $topic->name ?? ''; 
                                    $question->answer_name = $answer->answer_name;
                                    $question->description = $answer->description;
                                    $question->action = $answer->action;
                                    $question->file = $answer->file ?? ''; 
                                   $objects_id = '';
                                    if ($question->action == 'task' && !empty($reportData['tasks'])) {
                                        foreach ($reportData->tasks as $task) {
                                            $task->remaining_time = '';
                                            if ($task->deadline) {
                                                $task->remaining_time = $this->calRemainingTime($task->deadline);
                                            }
                                            $reportData->responsible_id = $task->responsible_id;
                                            $reportData->deadline = $task->deadline;
                                            $reportData->task_description = $task->description;
                                        }
                                        $tasks = Task::where('type', 'Report')
                                            ->where('type_id', $id)
                                            ->whereIn('status', [1,2])->get();
                                        if (count($tasks) == 0) {
                                            $count_done = $count_done + 1;
                                        } else {
                                            $count_done = $count_done - 1;
                                        } 
                                        $coun = !empty($tasks) ? count($tasks) :'';
                                        if($k >= $coun){
                                            $k = 0;
                                        }
                                        // $objects_id = !empty($tasks) ? $tasks[$k]->object_id : '';
                                        $tas = Task::where('id',$answer->task_id)->first();
                                        $objects_id = !empty($tas) ? $tas->object_id : '';
                                    }
                                    if ($question->action == 'risk' && !empty($reportData['risk_analysis'])) {
                                        foreach ($reportData->risk_analysis as $risk) {
                                            $reportData->risk_responsible_id = $risk->responsible;
                                        }
                                        $risks = RiskAnalysis::where('report_id', $reportData['id'])
                                        ->whereIn('status', [1,2])->get();
                                        if (count($risks) == 0) {
                                            $count_done = $count_done + 1;
                                        } else {
                                            $count_done = $count_done - 1;
                                        }  
                                        $coun = !empty($risks) ? count($risks) :'';
                                        if($k >= $coun){
                                            $k = 0;
                                        }
                                        $ris = RiskAnalysis::where('id',$answer->risk_id)->first();
                                        $objects_id = !empty($ris) ? $ris->object_id : '';
                                    }
                                     
                                    $question->object_id = !empty($objects_id) ? $objects_id :'';
                                    $question->task_id = json_decode($answer->task_id);
                                    $question->risk_id = $answer->risk_id;
//                                    if (!empty($picture = Document::where('report_id', $id)->where('report_question_id', $question->id)->first())) {
//                                        $question->picture_url = config('app.app_url') . "/api/v1/uploads/". $picture->uri;
//                                        $question->picture = $picture->original_file_name;
//                                    }
                                    $picture = DocumentNew::leftJoin('documents_options', 'documents_new.id', '=', 'documents_options.document_id')
                                        ->where('documents_new.company_id', $user['company_id'])
                                        ->where('documents_new.object_type', 'report')
                                        ->where('documents_new.object_id', $id)
                                        ->where('documents_options.report_question_id', $question->id)
                                        ->first();
                                    if (!empty($picture)) {
                                        $question->picture_url = config('app.app_url') . "/api/v1/uploads/". $picture->uri;
                                        $question->picture = $picture->original_file_name;
                                    }
                                }
                            }
                        }
                    }
                }
//                if ($count_done > 0) {
//                    $reportData['is_action_done'] = true; // done all report risks/tasks
//                } else {
//                    $reportData['is_action_done'] = false;
//                }
                // $reportData->topics = $topics;
                if($user->role_id == 4){
                    foreach ($topics as $topic) {
                        foreach ($topic->questions as $question) {
                            if (in_array($question->object_id,$taskIds)) {
                                $filteredData[] = $topic;
                            }
                        }
                    }
                    $filteredData = collect($filteredData)->unique();
                    $reportData->checklist_info->topics = $filteredData->toArray();
                }
                $reportData->editPermission = $user->editPermission;
                return $this->responseSuccess($reportData,201);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }



    private function getSourceOfDangerDetail($array)
    {
        foreach ($array as $item) {
            $item['risk_level'] = $item['probability'] * $item['consequence'];
        }
        return $array;
    }

    private function getRiskAnalysisDetail($object)
    { // popup review RISK ANALYSIS
        $object['riskElementArray'] = ObjectOption::leftJoin('objects', 'objects_option.object_id', '=', 'objects.id')
            ->leftJoin('users', 'objects.added_by', '=', 'users.id')
            ->leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
            ->whereJsonContains('objects_option.risk_analysis_array', $object['id'])
            ->select('objects_option.*', 'users.last_name as lastName', 'users.first_name as firstName', 'categories_new.name as categoryName', 'objects.name as name')
            ->get();
        if (!empty($object['riskElementArray'])) {
            if ($object['riskElementArray']->count() > 0) {
                $object['hasRiskElement'] = true;
            } else {
                $object['hasRiskElement'] = false;
            }
            foreach ($object['riskElementArray'] as $item) {
                // get image url
                if (!empty($item['image_id'])) {
                    $item['hasImage'] = true;
                    $image = Attachment::where('id', $item['image_id'])
                        ->where('object_id', $item['object_id'])
                        ->first();
                    $item['imageUrl'] = $image['url'];
                } else {
                    $item['hasImage'] = false;
                }

                // get security information
                $security = Security::where('object_type', 'risk')
                    ->where('object_id', $item['object_id'])
                    ->first();
                if (!empty($security)) {
                    $item['is_shared'] = $security['is_shared'];
                }
            }
        }
        return $object;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reports/{id}",
     *     tags={"Reports"},
     *     summary="Update report API",
     *     description="Update report API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateReportAPI",
     *     @OA\Parameter(
     *         description="report id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Reports schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Report")
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
            $reportData = Report::find($id);
            if (empty($reportData)) {
                return $this->responseException('Not found report checklist', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'report checklist',
                'objectItem' => $reportData,
            ];
            if (!$user = $this->getAuthorizedUser('report checklist', 'process', 'update', $objectInfo)) {
            // if (!$user = $this->getAuthorizedUser('report checklist', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $input = $request->all();
                
                $tasksOfReport = Task::where('type', 'Report')
                ->where('type_id', $reportData->id)
                    ->where('status', '<', 5)
                    ->get();
                    $riskOfReport = RiskAnalysis::where('report_id', $reportData['id'])
                    ->where('status', '<', 5)
                    ->get();
                    
                    $checklistInfo = json_decode($reportData->checklist_info);
                    $reportName = $checklistInfo->name;
                 
                //change responsible person
                if (!empty($input['updateResponsible'])) {
                    if ($reportData->responsible != $input['responsible']) {
                        $reportData->update(['responsible' => $input['responsible']]);
                        $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'report', 'Report', $reportData['id'], $reportName, 'responsible');
                    }
                    if (!empty($tasksOfReport)) {
                        if ($tasksOfReport[0]->responsible_id != $input['responsible_id']) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible_id']], 'task', 'Report', $reportData['id'], $reportName, 'responsible');

                            foreach ($tasksOfReport as $item) {
                                $item->update(['responsible_id' => $input['responsible_id']]);
                            }
                        }
                    }
                    if (!empty($riskOfReport)) {
                        if ($riskOfReport[0]->responsible != $input['risk_responsible_id']) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$input['risk_responsible_id']], 'report', 'Report', $reportData['id'], $riskOfReport[0]->name, 'responsible');

                            foreach ($riskOfReport as $item) {
                                $item->update(['responsible' => $input['risk_responsible_id']]);
                            }
                        }
                    }
                    return $this->responseSuccess($reportData, 201);
                }
                
                $rules = Report::$updateRules;
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                if ($input['status'] < 3) {
                    // close previous task to reopen report action
                    if (!empty($input['reopen']) && $input['reopen'] == 1) {
                        // task - old tasks before reopen
                        if (!empty($tasksOfReport)) {
                            foreach ($tasksOfReport as $task) {
                                $task->update(['status' => 5]);
                            }
                        }
                        // risk - old risks before reopen
                        if (!empty($riskOfReport)) {
                            foreach ($riskOfReport as $risk) {
                                $risk->update(['status' => 5]);
                            }
                        }
                    }
                    
                    if (!empty($input['tasks'])) {
                        $companyData = Company::where("id", $user['company_id'])->first();
                        $input['industry_id'] = $companyData['industry_id'];
                        $input['status'] = 2;
                        $input['description'] = $input['task_description'];
                        $newTask = '';
                        foreach ($input['tasks'] as $task) {
                            $newTask = $this->addTasksByType($task, $input, $user['id'], $user['company_id'], 'Report', $id);
                        }
                        if ($newTask) {
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$newTask['responsible_id']], 'task', 'Report', $reportData['id'],  $reportName, 'responsible');
                        }
                    }
                   
                    if (!empty($input['risk_analysis'])) {
                        $inputRiskAnalysis = $input['risk_analysis'];
                        $inputRiskAnalysis['responsible'] = $input['risk_responsible_id'];
                        $newRiskAnalysis = $this->createRiskAnalysis($inputRiskAnalysis, $user['id'], $user['company_id']);
                        if ($newRiskAnalysis) {
                            $riskElements = $inputRiskAnalysis['risk_elements'];
                            if (!empty($riskElements)) {
                                foreach ($riskElements as $element) {
                                    $this->createRiskElement($element, $newRiskAnalysis['id'], $user['id']);
                                }
                            }
                            $this->pushNotification($user['id'], $user['company_id'], 2, [$inputRiskAnalysis['responsible']], 'report', 'Report', $reportData['id'], $newRiskAnalysis['name'], 'responsible');
                        }
                    }
                }
               
                // ----- UPDATE report
                
                $checkReportClosedStatus = true;
                if ($input['status'] < 3) {
                    
                    $answers = json_decode($reportData->answer);
                    $total_data_risk = RiskAnalysis::where('report_id', $reportData['id'])
                    ->where('status', '<', 5)->get();  
                    $total_task_data = Task::where('type', 'Report')
                                ->where('type_id', $reportData['id'])
                                ->where('status', '<', 5) ->get();
                    $t_id = 0;
                    $r_id = 0; 
                    foreach ($answers as $k => $answer){
                      
                        $key = array_search($answer->question_id, array_column($input['checkpoint_arr'], 'id'));
                        if ($key > -1) {
                            $answer->action = $input['checkpoint_arr'][$k]['action'] ?? '';
                           
                            if (!empty($answer->action)) {
                               
                                 
                                if ($answer->action == 'risk') {
                                    $checkReportClosedStatus = false;
                                    // $report_risk_id = RiskAnalysis::where('report_id', $reportData['id'])
                                    // ->where('status', '<', 5)->first(); 
                                    $report_risk_id = RiskAnalysis::where('object_id', $total_data_risk[$r_id]->object_id)
                                    ->where('status', '<', 5)->first(); 
                                    $answer->risk_id = $report_risk_id['id'];
                                    $answer->task_id = null;
                                    $answer->action = "risk";
                                    $r_id++;
                                } else if ($answer->action == 'task') {
                                    $checkReportClosedStatus = false;
                                    // $list_task_id = Task::where('type', 'Report')
                                    // ->where('type_id', $reportData['id'])
                                    // ->where('status', '<', 5)
                                    // ->pluck('id')->toArray();  
                                    $report_task_id = Task::where('object_id', $total_task_data[$t_id]->object_id)
                                    ->where('status', '<', 5)->first(); 
                                    // $answer->task_id = json_encode($list_task_id);
                                    $answer->task_id = $report_task_id['id'];
                                    $answer->risk_id = null;
                                    $answer->action = "task";
                                    $t_id++;
                                } else if ($answer->action == 'close') {
                                    $answer->risk_id = null;
                                    $answer->task_id = null;
                                    $answer->action = "close";
                                }
                            }
                        }
                    } 
                    $reportData->answer = json_encode($answers);
                } 
                $data = array(
                    'answer' => $reportData->answer,
                    'status' => 2,
                    'responsible' => $input['responsible'],
                );
                $data['status'] =  $input['status'];
                if ($checkReportClosedStatus) {
                    $data['status'] = 3;
                }
                $reportData->update($data);
                return $this->responseSuccess($reportData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reports/{id}",
     *     tags={"Reports"},
     *     summary="Delete report API",
     *     description="Delete report API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteReportAPI",
     *     @OA\Parameter(
     *         description="report id",
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
            $reportData = Report::where("id",$id)->first();
            if (empty($reportData)) {
                return $this->responseException('Not found report', 404);
            }
            Report::destroy($id);
            return $this->responseSuccess("Delete report success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

}
