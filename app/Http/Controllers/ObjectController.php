<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use app\helpers\ValidateResponse;
use App\Models\Attachment;
use App\Models\Attendee;
use App\Models\AttendeeProcessing;
use App\Models\ResponsibleProcessing;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Industry;
use App\Models\ObjectItem;
use App\Models\ResponsibleHistory;
use App\Models\ExtendedTimeline;
use App\Models\ObjectOption;
use App\Models\Responsible;
use App\Models\AttendeeHistory;
use App\Models\Security;
use Illuminate\Support\Facades\Storage;
use App\Models\SourceOfDanger;
use App\Models\TimeManagement;
use App\Models\ChecklistOptionAnswer;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Checklist;
use App\Models\Deviation;
use App\Models\Report;
use App\Models\ChecklistOption;
use App\Models\Routine;
use App\Models\Task;
use App\Models\RiskAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;
use JWTAuth;
use App\Models\Category;
use App\Models\ConnectTo;
use App\Models\Goal;
use App\Models\Places;
use App\Models\Consequences;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * @OA\Tag(
 *     name="Objects",
 *     description="Objects APIs",
 * )
 **/
class ObjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/objects",
     *     tags={"Objects"},
     *     summary="Get objects",
     *     description="Get objects list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getObjects",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (!$request->objectType && empty($request->objectTypeArray)) {
                    return $this->responseSuccess([]);
                }
                if(isset($request->objectType) && $request->objectType == "risk-analysis"){
                    $resultIds = ObjectItem::where('type','risk-analysis')->where('company_id',$user->company_id)->pluck('id')->toArray(); 

                    $resultIds = array_filter($resultIds, function ($id) use ($user){
                        return in_array($user->id, Helper::checkRiskAnalysisDisplayAccess($id)); 
                    });
                }
                if(isset($request->objectTypeArray) && in_array("task", $request->objectTypeArray)){
                    $resultIds = ObjectItem::where('type','task')->where('company_id',$user->company_id)->pluck('id')->toArray(); 
                    $resultIds = array_filter($resultIds, function ($id) use ($user){
                        return in_array($user->id, Helper::checkTaskDisplayAccess($id)); 
                    });
                }
                if(isset($request->objectTypeArray) && in_array("checklist", $request->objectTypeArray)){
                    $resultIds = ObjectItem::where('type','checklist')->where('company_id',$user->company_id)->pluck('id')->toArray(); 
                    $resultIds = array_filter($resultIds, function ($id) use ($user){
                        return in_array($user->id, Helper::checkChecklistDisplayAccess($id)); 
                    });
                }
                if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("routine", $request->objectTypeArray)){
                    $resultIds = ObjectItem::where('type','routine')->where('company_id',$user->company_id)->pluck('id')->toArray(); 
                    $resultIds = array_filter($resultIds, function ($id) use ($user){
                        return in_array($user->id, Helper::checkRoutineDisplayAccess($id)); 
                    });
                }

                $objects = ObjectItem::leftJoin('users', 'objects.added_by', '=', 'users.id')
                    ->leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->leftJoin('routines', function($join) {
                        $join->on('objects.source_id', '=', 'routines.id');
                        $join->where('objects.type', '=', "routine");
                    })
                    ->leftJoin('checklists', function($join) {
                        $join->on('objects.source_id', '=', 'checklists.id');
                        $join->where('objects.type', '=', "checklist");
                    })
                    ->leftJoin('risk_analysis', function($join) {
                        $join->on('objects.source_id', '=', 'risk_analysis.id');
                        $join->where('objects.type', '=', "risk-analysis");
                    })
                    ->where(function ($q) use ($user) {
                        if ($user->role_id == 1) {
                            // $q->whereJsonContains('objects.industry', $user['company']['industry_id'])
                                //     ->where(function ($query) use ($user) {
                                    //         $query->where('objects.company_id', $user['company_id'])
                                        //             ->orWhere('objects.added_by', 1);
                                //     });
                        } else if ($user->role_id == 1) {
                            $q->where('objects.added_by', 1);
                        }
                    })
                    ->where('objects.is_valid', 1);

                if ($request->objectType) {
                    $objects = $objects->where('objects.type', $request->objectType);
                } elseif (!empty($request->objectTypeArray)) {
                    $objects = $objects->whereIn('objects.type', $request->objectTypeArray);
                }
                if(isset($request->is_template)) {
                    $objects = $objects->where('objects.is_template', $request->is_template);
                }
                if($user->company_id){
                    $objects = $objects->where('objects.company_id',$user->company_id);
                }
                if((isset($request->objectType) && $request->objectType == "risk-analysis") || (isset($request->objectTypeArray) && in_array("checklist", $request->objectTypeArray))){
                    $objects = $objects->whereIn('objects.id', $resultIds);
                }
                if(isset($request->objectTypeArray) && in_array("task", $request->objectTypeArray)){
                    $objects = $objects->whereIn('objects.id', $resultIds);
                }
                if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("routine", $request->objectTypeArray)){
                    $objects = $objects->whereIn('objects.id', $resultIds);
                }
                // start - added filter on routines

                if(isset($request->department) && !empty($request->department)) {
                    $department_detail = Department::where('id', $request->department)->with('employees')->first();
                    if(isset($department_detail->employees) && !empty($department_detail->employees)) {
                        $employees_id = $department_detail->employees->pluck('user_id')->toArray();
                        if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("routine", $request->objectTypeArray)){
                            $objects = $objects->whereIn('routines.added_by', $employees_id)->where('routines.company_id', $user->company_id);
                        }

                        if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("checklist", $request->objectTypeArray)){
                            $objects = $objects->whereIn('checklists.added_by', $employees_id)->where('checklists.company_id', $user->company_id);
                        }
                    }

                }

                if(isset($request->job_title) && !empty($request->job_title)) {
                    $job_title_detail = JobTitle::where('id', $request->job_title)->with('employees')->first();
                    if(isset($job_title_detail->employees) && !empty($job_title_detail->employees)) {
                        $employees_id = $job_title_detail->employees->pluck('user_id')->toArray();
                        if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("routine", $request->objectTypeArray)){
                            $objects = $objects->whereIn('routines.added_by', $employees_id)->where('routines.company_id', $user->company_id);
                        }
    
                        if((isset($request->objectTypeArray) && !empty($request->objectTypeArray)) && in_array("checklist", $request->objectTypeArray)){
                            $objects = $objects->whereIn('checklists.added_by', $employees_id)->where('checklists.company_id', $user->company_id);
                        }
                    }
                }

                if(isset($request->category) && !empty($request->category)) {
                    if($request->category !== 0) {
                        $objects = $objects->where('categories_new.id', "{$request->category}");
                    }
                }
                if(isset($request->reported_by) && !empty($request->reported_by)) {
                    if($request->reported_by == "anonymous") {
                        $objects = $objects->where('objects.report_as_anonymous', 1);
                    } else {
                        $objects = $objects->where('objects.added_by', "{$request->reported_by}");
                    }
                }

                if(isset($request->startDate) && isset($request->endDate)) {
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }

                if(isset($request->status) && !empty($request->status)) {
                    if(isset($request->objectTypeArray) && in_array("checklist", $request->objectTypeArray)) {
                        if($request->status == 1) {
                            $objects = $objects->where('checklists.status', "New");
                        } else if($request->status == 2) {
                            $objects = $objects->where('checklists.status', "Ongoing");
                        } else if($request->status == 3) {
                            $objects = $objects->where('checklists.status', "Closed");
                        }
                    }
                }

                if(isset($request->by_name) && !empty($request->by_name)) {
                    if(isset($request->category) && !empty($request->category)) {
                        if($request->category !== 0) {
                            $objects = $objects->where('categories_new.id', "{$request->category}")->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else if(isset($request->startDate) && isset($request->endDate)) {
                        $from = date('Y-m-d',strtotime($request->startDate));
                        $to = date('Y-m-d',strtotime($request->endDate));
                        $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                            $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                        });
                    }  else if(isset($request->reported_by) && !empty($request->reported_by)) {
                        $objects = $objects->where('objects.added_by',$request->reported_by)->where(function($q) use($request) {
                            $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                        });
                    } else if(isset($request->category) && (isset($request->startDate) && isset($request->endDate))) {
                        if($request->category !== 0) {
                            $from = date('Y-m-d',strtotime($request->startDate));
                            $to = date('Y-m-d',strtotime($request->endDate));
                            $objects = $objects->where('categories_new.id', "{$request->category}")->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        } else {
                            $from = date('Y-m-d',strtotime($request->startDate));
                            $to = date('Y-m-d',strtotime($request->endDate));
                            $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else {
                        $objects = $objects->where('objects.name', 'Like', "%{$request->by_name}%")
                        ->orWhere('categories_new.name', 'Like', "%{$request->by_name}%")
                        ->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                    }
                }

                // end - added filter on routines
                
                $objects = $objects->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'users.last_name as lastName', 'users.first_name as firstName', 'categories_new.name as categoryName', 'categories_new.source as categoryType')
                    ->orderBy('id','desc')
                    ->paginate(10);
                if (!empty($objects)) {
                    $result = [];
                    $users = User::where('company_id', $user['company_id'])->get();
                    foreach ($objects as $itemKey => $object) {
                        // display table with column See more
                        if ($user['id'] == $object['added_by']) {
                            $object['isCreator'] = true;
                        }

                        // responsible list
                        $responsibleNameArray = [];
                        if (!empty($object['responsible']['employee_array'])) {
                            $responsibleArray = json_decode($object['responsible']['employee_array']);
                            foreach ($responsibleArray as $item) {
                                $responsibleNameArray[] = $this->getUserName($item, $users);
                            }
                            // display table with column See more
                            if (in_array($user['id'], $responsibleArray)) {
                                $object['isResponsible'] = true;
                            }
                        }
                        $object['responsibleName'] = $responsibleNameArray;

                        // RESOURCE object - show number of used time
                        if ($object['is_template']) {
                            $object['number_used_time'] = $this->getObjectNumberOfUsedTime($object);
                        }

                        if ($object['type'] == 'routine') {
                            // $object['routine'] = Routine::where('id',  $object['source_id'])->first(); 

                            $object['addedByName'] = $this->getUserName($object['added_by'], $users);
                            $processdata =  $this->getObjectDetailInfo($object, $user);
                            $object['processingInfo'] = $processdata['processingInfo'] ?? '';
                            $object['processingInfoResponsible'] = $processdata['processingInfoResponsible'] ?? '';
                            $object['recurring_type'] = '';
                            if (!empty($object['source_id'])) {
                                $routineData = Routine::where('id', $object['source_id'])->select('recurring_type', 'recurring', 'start_time', 'start_date', 'deadline', 'id', 'is_duration')->first();
                                $taskData = ObjectItem::where('type','task')->where('source','routine')->where('source_id',$object['source_id'])->with(['attendee', 'responsible', 'time'])->latest()->first();
                                if(isset($taskData) && !empty($taskData)){
                                    $taskObj = $this->getObjectDetailInfo($taskData, $user);
                                    if(isset($taskObj['processingInfo']) && !empty($taskObj['processingInfo'])){
                                        $task_data = [];
                                        foreach ($taskObj['processingInfo'] as $key => $attendee) {
                                            $start_date = $attendee['time_info']['start_date'] ?? '';
                                            $start_time = isset($attendee['time_info']['start_time']) && !empty($attendee['time_info']['start_time']) ? $attendee['time_info']['start_time'] : '00:00:00';
                                            $end_date = $attendee['time_info']['deadline'] ?? '';
                                            $end_time = isset($attendee['time_info']['end_time']) && !empty($attendee['time_info']['end_time']) ? $attendee['time_info']['end_time'] : '00:00:00'; 
                                            if(isset($attendee['extended_timeline']) && !empty($attendee['extended_timeline'])){
                                                $end_date = $attendee['extended_timeline']['deadline_date'] ?? $attendee['time_info']['deadline'] ;
                                                $end_time = isset($attendee['extended_timeline']['deadline_time']) && !empty($attendee['extended_timeline']['deadline_time']) ? $attendee['extended_timeline']['deadline_time'] : '00:00:00'; 
                                            }
                                            $task_status = "new";
                                            if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                                $startDay = Carbon::make($start_date . ' ' . $start_time);
                                                $endDay = Carbon::make($end_date . ' ' . $end_time);
                                                if(isset($attendee['status']) && ($attendee['status'] == "closed" || $attendee['status'] == "3")) {
                                                    $task_status = "closed";
                                                }else if(isset($attendee['status']) && $attendee['status'] == "Removed" || $attendee['status'] == "Reassigned" || $attendee['status'] == "disapproved_overdue" || $attendee['status'] == "disapproved_with_extended" || $attendee['status'] == "timeline_disapproved" || $attendee['status'] == "overdue" || $attendee['status'] == "request" || $attendee['status'] == "approved_overdue" || $attendee['status'] == "completed" || $attendee['status'] == "approved" || $attendee['status'] == "disapproved" || $attendee['status'] == "completed_overdue") { 
                                                    $task_status = $attendee['status'];
                                                }else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                                    $task_status = "new";
                                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                                    $task_status = "new";
                                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                                    $task_status = "ongoing";
                                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($attendee['status']) && ($attendee['status'] !== "closed" || $attendee['status'] !== "3"))) {
                                                    $task_status = "overdue";
                                                } else {
                                                    $task_status = "new";
                                                }
                                                $attendee['status'] = $task_status ?? '';
                                                $task_data[] = $attendee;
                                            }   
                                        }
                                        $object['task_data'] = $task_data;
                                    }
                                }
                                $object['recurring_type'] = !empty($routineData->recurring_type) ? ucfirst($routineData->recurring_type) : '';
                                $object['recurring'] = !empty($routineData->recurring) ? ucfirst($routineData->recurring) : '';
                                if (!empty($routineData['start_date'])) {
                                    $object['start_date'] =  $routineData['start_date'];
                                }

                                if (!empty($routineData['deadline'])) {
                                    $object['deadline'] = date('Y-m-d', $routineData->deadline);
                                }

                                if (!empty($routineData['start_time'])) {
                                    $object['start_time'] = date("H:i:s", ($routineData->start_time));
                                }
                            }
                            $object->count_related_object = 0;
                            $object->related_objects = '';
                            if ($object['is_template']) {
                                $relatedObject = Routine::leftJoin('users', 'routines.added_by', '=', 'users.id')
                                    ->leftJoin('companies', 'routines.company_id', '=', 'companies.id')
                                    ->where('parent_id', $object['id']);
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
                                    $object->count_related_object = count($relatedObject);
                                    $object->related_objects = $relatedObject;
                                }
                            }
                        }

                        $object['countSubObject'] = ObjectItem::where('source_id', $object['id'])->where('objects.is_valid', 1)->count();

                        $object['rateSubObject'] = 0;
                        if ($object['countSubObject'] > 0) {
                            $object['totalAttendee'] = 0;
                            $object['completeAttendee'] = 0;

                            $subObjects = ObjectItem::where('objects.company_id', $user['company_id'])
                                ->where('objects.source', $object['type'])
                                ->where('objects.source_id', $object['id'])
                                ->where('objects.is_valid', 1)
                                ->with(['attendee', 'responsible', 'time'])
                                ->get();

                            $attendeeNameArray = [];
                            $seeMoreList = [];
                            foreach ($subObjects as $subObject) {
                                if (!empty($subObject['attendee']['processing'])) {
                                    $object['totalAttendee'] += count($subObject['attendee']['processing']);
                                    $countByStatus = array_count_values(array_column($subObject['attendee']['processing']->toArray(), 'status'));

                                    if (isset($countByStatus['closed'])) {
                                        $object['completeAttendee'] += $countByStatus['closed'];
                                    }
                                }

                                // display table with column See more
                                if ($object['isCreator'] || $object['isResponsible']) {
                                    // for Instruction
                                    if ($subObject['type'] == 'instruction-activity' && !empty($subObject['attendee']['employee_array'])) {
                                        $attendeeArray = json_decode($subObject['attendee']['employee_array']);
                                        foreach ($attendeeArray as $item) {
                                            $attendeeNameArray[] = $this->getUserName($item, $users);
                                        }
                                    }
                                    // for Goal
                                    if ($subObject['type'] == 'sub-goal') {
                                        $seeMoreList = $this->getSeeMoreAttendeeTable($subObject, $seeMoreList);
                                    }
                                }
                            }
                            $object['see_more'] = $seeMoreList;

                            // display table with column See more
                            if ($object['isCreator'] || $object['isResponsible']) {
                                // for Instruction
                                if ($object['type'] == 'instruction' && !empty($attendeeNameArray)) {
                                    $tempArray = array_unique($attendeeNameArray);
                                    $newAttendeeNameArray = [];
                                    foreach ($tempArray as $item) {
                                        $newAttendee = ['name' => $item];
                                        array_push($newAttendeeNameArray, $newAttendee);
                                    }
                                    $object['attendeeName'] = $newAttendeeNameArray;
                                }
                            }

                            if ($object['totalAttendee'] > 0) {
                                $object['rateSubObject'] = $object['completeAttendee'] * 100 / $object['totalAttendee'];
                            }

                            // display status Risk analysis based on task status (if risk analysis has task)
                            if ($object['type'] == 'risk-analysis' && count($subObjects) > 0) {
                                $object['status'] = $this->showStatusNewByDate($subObjects[0]);
                            }
                        } else {
                            // display table with column See more
                            if ($object['isCreator'] || $object['isResponsible']) {
                                // for Goal
                                if ($object['type'] == 'goal') {
                                    $object['see_more'] = $this->getSeeMoreAttendeeTable($object, $object['see_more']);
                                }
                            }
                        }

                        $object['rate'] = 0;
                        if (!empty($object['attendee']['processing'])) {
                            $object['totalAttendee'] = count($object['attendee']['processing']);
                            $countByStatus =  array_count_values(array_column($object['attendee']['processing']->toArray(), 'status'));

                            if (isset($countByStatus['closed'])) {
                                $object['completeAttendee'] = $countByStatus['closed'];
                            }
                        }

                        $object = $this->getObjectDetailInfo($object, $user);
                        $dateTimeObj = $this->getDateTimeBasedOnTimezone($object);
                        $object['start_date'] = $dateTimeObj['start_date'] ?? '';
                        $object['start_time'] = $dateTimeObj['start_time'] ?? '';
                        $object['deadline'] = $dateTimeObj['deadline'] ?? '';
                        $object['end_time'] = $dateTimeObj['end_time'] ?? '';
                        if((isset($object['status']) && $object['type'] == 'risk-analysis') && ($object['status'] == "completed" || $object['status'] == "1")) {
                            $object['status'] = "completed";
                        }else{
                            $object['status'] = $this->getObjectStatus($object,$dateTimeObj);                              
                        }
                        $object['object_id'] = null;
                        if($object['type'] == "risk-analysis"){
                            $this->getSecurityObject('risk-analysis', $object);
                            $object['object_id'] = null;
                            $riskTaskObj =  ObjectItem::with(['attendee', 'responsible', 'time'])->where('source_id',$object['id'])->first();
                            if(isset($riskTaskObj) && !empty($riskTaskObj)){
                                $object['object_id'] = $riskTaskObj->id;
                                $taskDetailObj = $this->getObjectDetailInfo($riskTaskObj, $user);                        
                                $dateTimeObj = $this->getDateTimeBasedOnTimezone($riskTaskObj);
                                $object['start_date'] = $dateTimeObj['start_date'] ?? '';
                                $object['start_time'] = $dateTimeObj['start_time'] ?? '';
                                $object['deadline'] = $dateTimeObj['deadline'] ?? '';
                                $object['end_time'] = $dateTimeObj['end_time'] ?? '';

                                $object['status'] = $this->getObjectStatus($taskDetailObj,$dateTimeObj);  
                                if(isset($taskDetailObj['my_processing']) && !empty($taskDetailObj['my_processing'])){
                                    if($taskDetailObj['my_processing']['status'] == "Removed" || $taskDetailObj['my_processing']['status'] == "Reassigned" ){
                                        $object['status'] = $taskDetailObj['my_processing']['status'];
                                    }
                                }  

                            }
                        }
                        if (!empty($object['totalAttendee']) && $object['totalAttendee'] > 0) {
                            $object['rate'] = round($object['completeAttendee'] * 100 / $object['totalAttendee'], 2);
                        }

                        $object['totalResponsible'] = 0;
                        $object['completeResponsible'] = 0;
                        $object['responsible_rate'] = 0;
    
                        if (!empty($object['responsible']['processing'])) {
                            // $object['totalResponsible'] = count($object['responsible']['processing']);
                            $countByStatus =  array_count_values(array_column($object['responsible']['processing']->toArray(), 'status'));
                            foreach ($object['responsible']['processing'] as $key => $processing) {
                                $userR =  User::where('id', $processing['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                                if (isset($processing->status) && $processing->status == 'completed') {
                                    if (empty($this->getResponsibleHistory($object['id'], $userR->id))) {
                                        $object['completeResponsible'] += 1;
                                    }
                                }
                                if (empty($this->getResponsibleHistory($object['id'], $userR->id))) {
                                    $object['totalResponsible']  += 1;
                                }
                            }
                            if ($object['totalResponsible'] > 0) {
                                $object['responsible_rate'] = round($object['completeResponsible'] * 100 / $object['totalResponsible']);
                            }
                        }
                        $object['total_rate'] = ($object['responsible_rate'] + $object['rate']) / 2;
    
    
                        // $object['start_date'] = null;
                        // $object['deadline'] = null;

                        // if ($object['time']) {
                        //     $object['start_date'] = date("Y-m-d H:i:s", $object['time']['start_date']);
                        //     $object['deadline'] = date("Y-m-d H:i:s", $object['time']['deadline']);
                        // }

                        // object type - RISK ANALYSIS
                        if ($object['type'] == 'task') {
                            $object['sourceOfDanger'] = SourceOfDanger::where('object_id', $object['id'])->get();
                        }
                        if ($object['type'] == 'risk-analysis') {
                            $object['countSourceOfDanger'] = SourceOfDanger::where('object_id', $object['id'])->count();
                            if ($object['countSourceOfDanger'] == 1) {
                                $sourceDanger = SourceOfDanger::where('object_id', $object['id'])->first();
                                $object['risk_level'] = $sourceDanger['probability'] * $sourceDanger['consequence'];
                            } elseif ($object['countSourceOfDanger'] > 1) {
                                $sourceDangerArray = SourceOfDanger::where('object_id', $object['id'])->get();
                                $maxRisk = 0;
                                foreach ($sourceDangerArray as $source) {
                                    if (($source['probability'] * $source['consequence']) > $maxRisk) {
                                        $maxRisk = $source['probability'] * $source['consequence'];
                                    }
                                }
                                $object['risk_level'] = $maxRisk;
                            }
                        }

                        // object type - RISK ELEMENT
                        if ($object['type'] == 'risk') {
                            $riskElement = ObjectOption::where('object_id', $object['id'])->first();
                            if (empty($riskElement)) {
                                return null;
                            }
                            $object['risk_element_show_in_risk_analysis'] = $riskElement['show_in_risk_analysis'];
                            $object['risk_element_number_used_time'] = $riskElement['number_used_time'];
                        }
                        // if ($object['time']) {
                        //     $object['start_date'] = date("Y-m-d H:i:s", $object['time']['start_date']);
                        //     $object['deadline'] = date("Y-m-d H:i:s", $object['time']['deadline']);
                        // }

                        // object type - CHECKLIST
                        $object->topic = 0;
                        $object->checkpoints = 0;
                        $object->checklist_used = 0;
                        if ($object['type'] == 'checklist' && $object['source_id']) {
                            $public = Checklist::where('id', $object['source_id'])->with(['defaultOptions'])->first();
                            $object->is_public = $public->is_public ?? 0;
                            $object->defaultOptions = $public->defaultOptions ?? '';
                            $object->topic = Topic::where('checklist_id', $object['source_id'])->count();
                            $object->checkpoints = Question::where('checklist_id', $object['source_id'])->count();
                            $object->checklist_used = Report::where('checklist_id', $object['source_id'])->count();

                            $object->employee_array = $this->getSecurityObject('checklist', $object)["employee_array"] ?? '';
                            $object->employee_names = $this->getSecurityObject('checklist', $object)["employee_names"] ?? '';
                            // return response()->json([
                            //     'asd'=>$object
                            // ]);
                            if ($user->role_id > 1) {
                                // $responsible_emp = Responsible::where('object_id', $object->id)->first(); 
                                // if(!empty($object->employee_array) && in_array($user->id,json_decode($object->employee_array)) ){ 
                                //     $result[] = $object;
                                // }else  if(!empty($responsible_emp->employee_array) && in_array($user->id,json_decode($responsible_emp->employee_array)) ){
                                //     $result[] = $object; 
                                // }else  if(!empty($object->added_by) && $user->id == $object->added_by ){
                                //     $result[] = $object; 
                                // } 
                                $result[] = $object;
                            }
                        } else if ($object['type'] == 'routine' && $object['source_id']) {
                            $routineData = Routine::where('id', $object['source_id'])->select('recurring_type', 'recurring', 'start_time', 'start_date', 'deadline', 'id', 'is_duration')->first();
                            if(empty($routineData->deadline)) {
                                $object['deadline'] = "";
                                $object['end_time'] = "";
                            }
                            if ($user->role_id > 1) {
                                $responsible_emp = Responsible::where('object_id', $object->id)->first();
                                $attendee_emp = Attendee::where('object_id', $object->id)->first();
                                $security = Security::where('object_id', $object['source_id'])->first();

                                if (!empty($responsible_emp->employee_array) && in_array($user->id, json_decode($responsible_emp->employee_array))) {
                                    $result[] = $object;
                                } else  if (!empty($attendee_emp->employee_array) && in_array($user->id, json_decode($attendee_emp->employee_array))) {
                                    $result[] = $object;
                                } else  if (!empty($security->employee_array) && in_array($user->id, json_decode($security->employee_array))) {
                                    $result[] = $object;
                                } else  if (!empty($object->added_by) && $user->id == $object->added_by) {
                                    $result[] = $object;
                                }
                            }
                            $obj_status = $this->getPriorityStatus($object['source_id'],$user);
                            if(isset($obj_status) && !empty($obj_status)){
                                $object['status'] = $obj_status;
                            }else{
                                $object['status'] = $object['status'];
                            }

                        } else {
                            $result[] = $object;
                        }
                    }
                    return $objects;
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getPriorityStatus($id,$user){
        $allTask = ObjectItem::where('type','task')->where('source','routine')->where('source_id',$id)->with(['attendee', 'responsible', 'time'])->get();
        if(isset($allTask) && !empty($allTask->all())){
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
                foreach($allTask as $taskObject){
                    $dateTimeObj = $this->getDateTimeBasedOnTimezone($taskObject, $user);
                    $status = $this->getObjectStatus($taskObject,$dateTimeObj);  
                    $attendee_info = Attendee::where('object_id', $taskObject->id)->latest()->first();
                    if(isset($attendee_info) && !empty($attendee_info)) {
                            $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                            if(isset($attendee_processing) && !empty($attendee_processing)) {
                                $attendee_history = $this->getAttendeStatusHistory($taskObject->id, $user->id);
                                if (!empty($attendee_history) && $attendee_history->type == 'change') {
                                    $attendee_processing->status = 'Reassigned';
                                }
                                if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                                    $attendee_processing->status = 'Removed';
                                }
                            
                                $taskObject->status = $attendee_processing->status;
                                $status = $this->getObjectStatus($taskObject,$dateTimeObj);  
                            }
                    } 
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
                if ($overdueCnt > 0) {
                    $status = 'overdue';
                } elseif ($disapprovedOverdueCnt > 0) {
                    $status = 'disapproved_overdue';
                } elseif ($disapprovedCnt > 0) {
                    $status = 'ongoing'; // disapproved but checklist status will show ongoing
                } elseif ($disapprovedWithExtendedCnt > 0) {
                    $status = 'overdue'; // disapproved_with_extended but checklist status will show ongoing overdue
                } elseif ($overdueCnt > 0) {
                    $status = 'overdue'; // Ongoing Extend Deadline
                } elseif ($ongoingCnt > 0) {
                    $status = 'ongoing';
                } elseif ($timelineDisapprovedCnt > 0) {
                    $status = 'timeline_disapproved'; // status will show ongoing
                } elseif ($completedOverdueCnt > 0) {
                    $status = 'completed_overdue';
                } elseif ($completedCnt > 0) {
                    $status = 'completed';
                } elseif ($approvedOverdueCnt > 0) {
                    $status = 'approved_overdue';
                } elseif ($approvedCnt > 0) {
                    $status = 'approved';
                } elseif ($requestCnt > 0) {
                    $status = 'request';
                } elseif ($removedCnt > 0) {
                    $status = 'Removed';
                } elseif ($reassignedCnt > 0) {
                    $status = 'Reassigned';
                } elseif ($closedCnt > 0) {
                    $status = 'closed';
                } elseif ($newCnt > 0) {
                    $status = 'new';
                } else {
                    $status = '';
                }

                return $status ?? '';
        }
    }
    public function riskAnalysisFilter(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                // if (!$request->objectType && empty($request->objectTypeArray)) {
                //     return $this->responseSuccess([]);
                // }
              
                    $resultIds = ObjectItem::where('type','risk-analysis')->pluck('id')->toArray(); 

                    $resultIds = array_filter($resultIds, function ($id) use ($user){
                        return in_array($user->id, Helper::checkRiskAnalysisDisplayAccess($id)); 
                    });

                $objects = ObjectItem::leftJoin('users', 'objects.added_by', '=', 'users.id')
                    ->leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->leftJoin('routines', function($join) {
                        $join->on('objects.source_id', '=', 'routines.id');
                        $join->where('objects.type', '=', "routine");
                    })
                    ->leftJoin('checklists', function($join) {
                        $join->on('objects.source_id', '=', 'checklists.id');
                        $join->where('objects.type', '=', "checklist");
                    })
                    ->leftJoin('risk_analysis', function($join) {
                        $join->on('objects.source_id', '=', 'risk_analysis.id');
                        $join->where('objects.type', '=', "risk-analysis");
                    })
                    ->where(function ($q) use ($user) {
                        if ($user->role_id == 1) {
                            // $q->whereJsonContains('objects.industry', $user['company']['industry_id'])
                            //     ->where(function ($query) use ($user) {
                            //         $query->where('objects.company_id', $user['company_id'])
                            //             ->orWhere('objects.added_by', 1);
                            //     });
                        } else if ($user->role_id == 1) {
                            $q->where('objects.added_by', 1);
                        }
                    })
                    ->where('objects.is_valid', 1);

                if ($request->objectType) {
                    $objects = $objects->where('objects.type', $request->objectType);
                } elseif (!empty($request->objectTypeArray)) {
                    $objects = $objects->whereIn('objects.type', $request->objectTypeArray);
                }
                $objects = $objects->whereIn('objects.id', $resultIds);

                // start - added filter on routines

                if(isset($request->category) && !empty($request->category)) {
                    if($request->category !== 0) {
                        $objects = $objects->where('categories_new.id', "{$request->category}");
                    }
                }
                if(isset($request->reported_by) && !empty($request->reported_by)) {
                    if($request->reported_by == "anonymous") {
                        $objects = $objects->where('objects.report_as_anonymous', 1);
                    } else {
                        $objects = $objects->where('objects.added_by', "{$request->reported_by}");
                    }
                }

                if(isset($request->startDate) && isset($request->endDate)) {
                    $from = date('Y-m-d',strtotime($request->startDate));
                    $to = date('Y-m-d',strtotime($request->endDate));
                    $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59']);
                }

                

                if(isset($request->by_name) && !empty($request->by_name)) {
                    if(isset($request->category) && !empty($request->category)) {
                        if($request->category !== 0) {
                            $objects = $objects->where('categories_new.id', "{$request->category}")->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else if(isset($request->startDate) && isset($request->endDate)) {
                        $from = date('Y-m-d',strtotime($request->startDate));
                        $to = date('Y-m-d',strtotime($request->endDate));
                        $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                            $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                        });
                    }  else if(isset($request->reported_by) && !empty($request->reported_by)) {
                        $objects = $objects->where('objects.added_by',$request->reported_by)->where(function($q) use($request) {
                            $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                        });
                    } else if(isset($request->category) && (isset($request->startDate) && isset($request->endDate))) {
                        if($request->category !== 0) {
                            $from = date('Y-m-d',strtotime($request->startDate));
                            $to = date('Y-m-d',strtotime($request->endDate));
                            $objects = $objects->where('categories_new.id', "{$request->category}")->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        } else {
                            $from = date('Y-m-d',strtotime($request->startDate));
                            $to = date('Y-m-d',strtotime($request->endDate));
                            $objects = $objects->whereBetween('objects.created_at', [$from.' 00:00:00',$to.' 23:59:59'])->where(function($q) use($request) {
                                $q->orWhere('objects.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else {
                        $objects = $objects->where('objects.name', 'Like', "%{$request->by_name}%")
                        ->orWhere('categories_new.name', 'Like', "%{$request->by_name}%")
                        ->orWhere('routines.recurring', 'Like', "%{$request->by_name}%");
                    }
                }

                $objects = $objects->with(['attendee', 'responsible', 'time'])
                ->select('objects.*', 'users.last_name as lastName', 'users.first_name as firstName', 'categories_new.name as categoryName', 'categories_new.source as categoryType')
                ->orderBy('id','desc')
                ->get();
                
                if (!empty($objects)) {
                    $result = [];
                    $users = User::where('company_id', $user['company_id'])->get();
                    foreach ($objects as $itemKey => $object) {
                        // display table with column See more
                        if ($user['id'] == $object['added_by']) {
                            $object['isCreator'] = true;
                        }

                        // responsible list
                        $responsibleNameArray = [];
                        if (!empty($object['responsible']['employee_array'])) {
                            $responsibleArray = json_decode($object['responsible']['employee_array']);
                            foreach ($responsibleArray as $item) {
                                $responsibleNameArray[] = $this->getUserName($item, $users);
                            }
                            // display table with column See more
                            if (in_array($user['id'], $responsibleArray)) {
                                $object['isResponsible'] = true;
                            }
                        }
                        $object['responsibleName'] = $responsibleNameArray;

                        // RESOURCE object - show number of used time
                        if ($object['is_template']) {
                            $object['number_used_time'] = $this->getObjectNumberOfUsedTime($object);
                        }

                        

                        $object['countSubObject'] = ObjectItem::where('source_id', $object['id'])->where('objects.is_valid', 1)->count();

                        $object['rateSubObject'] = 0;
                        if ($object['countSubObject'] > 0) {
                            $object['totalAttendee'] = 0;
                            $object['completeAttendee'] = 0;

                            $subObjects = ObjectItem::where('objects.company_id', $user['company_id'])
                                ->where('objects.source', $object['type'])
                                ->where('objects.source_id', $object['id'])
                                ->where('objects.is_valid', 1)
                                ->with(['attendee', 'responsible', 'time'])
                                ->get();

                            $attendeeNameArray = [];
                            $seeMoreList = [];
                            foreach ($subObjects as $subObject) {
                                if (!empty($subObject['attendee']['processing'])) {
                                    $object['totalAttendee'] += count($subObject['attendee']['processing']);
                                    $countByStatus = array_count_values(array_column($subObject['attendee']['processing']->toArray(), 'status'));

                                    if (isset($countByStatus['closed'])) {
                                        $object['completeAttendee'] += $countByStatus['closed'];
                                    }
                                }

                                // display table with column See more
                                if ($object['isCreator'] || $object['isResponsible']) {
                                    // for Instruction
                                    if ($subObject['type'] == 'instruction-activity' && !empty($subObject['attendee']['employee_array'])) {
                                        $attendeeArray = json_decode($subObject['attendee']['employee_array']);
                                        foreach ($attendeeArray as $item) {
                                            $attendeeNameArray[] = $this->getUserName($item, $users);
                                        }
                                    }
                                    // for Goal
                                    if ($subObject['type'] == 'sub-goal') {
                                        $seeMoreList = $this->getSeeMoreAttendeeTable($subObject, $seeMoreList);
                                    }
                                }
                            }
                            $object['see_more'] = $seeMoreList;

                            // display table with column See more
                            if ($object['isCreator'] || $object['isResponsible']) {
                                // for Instruction
                                if ($object['type'] == 'instruction' && !empty($attendeeNameArray)) {
                                    $tempArray = array_unique($attendeeNameArray);
                                    $newAttendeeNameArray = [];
                                    foreach ($tempArray as $item) {
                                        $newAttendee = ['name' => $item];
                                        array_push($newAttendeeNameArray, $newAttendee);
                                    }
                                    $object['attendeeName'] = $newAttendeeNameArray;
                                }
                            }

                            if ($object['totalAttendee'] > 0) {
                                $object['rateSubObject'] = $object['completeAttendee'] * 100 / $object['totalAttendee'];
                            }

                            // display status Risk analysis based on task status (if risk analysis has task)
                            if ($object['type'] == 'risk-analysis' && count($subObjects) > 0) {
                                $object['status'] = $this->showStatusNewByDate($subObjects[0]);
                            }
                        } else {
                            // display table with column See more
                            if ($object['isCreator'] || $object['isResponsible']) {
                                // for Goal
                                if ($object['type'] == 'goal') {
                                    $object['see_more'] = $this->getSeeMoreAttendeeTable($object, $object['see_more']);
                                }
                            }
                        }

                        $object['rate'] = 0;
                        if (!empty($object['attendee']['processing'])) {
                            $object['totalAttendee'] = count($object['attendee']['processing']);
                            $countByStatus =  array_count_values(array_column($object['attendee']['processing']->toArray(), 'status'));

                            if (isset($countByStatus['closed'])) {
                                $object['completeAttendee'] = $countByStatus['closed'];
                            }
                        }

                        $object = $this->getObjectDetailInfo($object, $user);
                        $dateTimeObj = $this->getDateTimeBasedOnTimezone($object);
                        $object['start_date'] = $dateTimeObj['start_date'] ?? '';
                        $object['start_time'] = $dateTimeObj['start_time'] ?? '';
                        $object['deadline'] = $dateTimeObj['deadline'] ?? '';
                        $object['end_time'] = $dateTimeObj['end_time'] ?? '';
                        if((isset($object['status']) && $object['type'] == 'risk-analysis') && ($object['status'] == "completed" || $object['status'] == "1")) {
                            $object['status'] = "completed";
                        }else{
                            $object['status'] = $this->getObjectStatus($object,$dateTimeObj);                              
                        }
                        $object['object_id'] = null;
                        if($object['type'] == "risk-analysis"){
                            $this->getSecurityObject('risk-analysis', $object);
                            // if(!in_array($user->id, Helper::checkRiskAnalysisDisplayAccess($object->id))) {
                            //     $objects->forget($itemKey);
                            // }  
                            $object['object_id'] = null;
                            $riskTaskObj =  ObjectItem::with(['attendee', 'responsible', 'time'])->where('source_id',$object['id'])->first();
                            if(isset($riskTaskObj) && !empty($riskTaskObj)){
                                $object['object_id'] = $riskTaskObj->id;
                                $taskDetailObj = $this->getObjectDetailInfo($riskTaskObj, $user);                        
                                $dateTimeObj = $this->getDateTimeBasedOnTimezone($riskTaskObj);
                                $object['start_date'] = $dateTimeObj['start_date'] ?? '';
                                $object['start_time'] = $dateTimeObj['start_time'] ?? '';
                                $object['deadline'] = $dateTimeObj['deadline'] ?? '';
                                $object['end_time'] = $dateTimeObj['end_time'] ?? '';

                                $object['status'] = $this->getObjectStatus($taskDetailObj,$dateTimeObj);  
                                if(isset($taskDetailObj['my_processing']) && !empty($taskDetailObj['my_processing'])){
                                    if($taskDetailObj['my_processing']['status'] == "Removed" || $taskDetailObj['my_processing']['status'] == "Reassigned" ){
                                        $object['status'] = $taskDetailObj['my_processing']['status'];
                                    }
                                }  

                            }
                        }
                        if (!empty($object['totalAttendee']) && $object['totalAttendee'] > 0) {
                            $object['rate'] = round($object['completeAttendee'] * 100 / $object['totalAttendee'], 2);
                        }

                        $object['totalResponsible'] = 0;
                        $object['completeResponsible'] = 0;
                        $object['responsible_rate'] = 0;
    
                        if (!empty($object['responsible']['processing'])) {
                            // $object['totalResponsible'] = count($object['responsible']['processing']);
                            $countByStatus =  array_count_values(array_column($object['responsible']['processing']->toArray(), 'status'));
                            foreach ($object['responsible']['processing'] as $key => $processing) {
                                $userR =  User::where('id', $processing['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                                if (isset($processing->status) && $processing->status == 'completed') {
                                    if (empty($this->getResponsibleHistory($object['id'], $userR->id))) {
                                        $object['completeResponsible'] += 1;
                                    }
                                }
                                if (empty($this->getResponsibleHistory($object['id'], $userR->id))) {
                                    $object['totalResponsible']  += 1;
                                }
                            }
                            if ($object['totalResponsible'] > 0) {
                                $object['responsible_rate'] = round($object['completeResponsible'] * 100 / $object['totalResponsible']);
                            }
                        }
                        $object['total_rate'] = ($object['responsible_rate'] + $object['rate']) / 2;

                       
                        if ($object['type'] == 'risk-analysis') {
                            $object['countSourceOfDanger'] = SourceOfDanger::where('object_id', $object['id'])->count();
                            if ($object['countSourceOfDanger'] == 1) {
                                $sourceDanger = SourceOfDanger::where('object_id', $object['id'])->first();
                                $object['risk_level'] = $sourceDanger['probability'] * $sourceDanger['consequence'];
                            } elseif ($object['countSourceOfDanger'] > 1) {
                                $sourceDangerArray = SourceOfDanger::where('object_id', $object['id'])->get();
                                $maxRisk = 0;
                                foreach ($sourceDangerArray as $source) {
                                    if (($source['probability'] * $source['consequence']) > $maxRisk) {
                                        $maxRisk = $source['probability'] * $source['consequence'];
                                    }
                                }
                                $object['risk_level'] = $maxRisk;
                            }
                        }

                        // object type - CHECKLIST
                        $object->topic = 0;
                        $object->checkpoints = 0;
                        $object->checklist_used = 0;
                        $result[] = $object;
                    }
                    $objects = $objects->toArray();
                    if(isset($request->status) && !empty($request->status)){
                        $status_new_records = $request->status == 1 ? $this->getStatusCount($objects, 'new') : [];
                        $status_ongoing_records = $request->status == 2 ? $this->getStatusCount($objects, 'ongoing') : [];
                        $status_closed_records = $request->status == 3 ? $this->getStatusCount($objects, 'closed') : [];
                    } else {
                        $status_new_records = $this->getStatusCount($objects, 'new');
                        $status_ongoing_records = $this->getStatusCount($objects, 'ongoing');
                        $status_closed_records = $this->getStatusCount($objects, 'closed');
                    }
                    if(isset($request->status) && $request->status == 1) {
                        $final_resp = $this->paginate($status_new_records);
                    } else if (isset($request->status) && $request->status == 2) {
                        $final_resp = $this->paginate($status_ongoing_records);
                    } else if (isset($request->status) && $request->status == 3) {
                        $final_resp = $this->paginate($status_closed_records);
                    } else {
                        $final_resp = $this->paginate($objects);
                    }
                    // $custom = collect([
                    //     'total_new' => count($status_new_records),
                    //     'total_ongoing' => count($status_ongoing_records),
                    //     'total_closed' => count($status_closed_records),
                    // ]);
    
                    // $final_resp = $custom->merge($final_resp);
                    return $final_resp;

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

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $current_items = array_slice($items, $perPage * ($page - 1), $perPage);
        $options = [
            'path' => url('api/v1/riskAnalysis/filter')
        ];
        $paginator = new LengthAwarePaginator($current_items, count($items), $perPage, $page, $options);
        $paginator->appends(request()->all());
        return $paginator;
    }

    private function getDateTimeBasedOnTimezone($object)
    {
        $dateTimeObject = $this->getObjectTimeInfo($object);
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

    private function getOnlyDateTimeBasedOnTimezone($object)
    {
        $newObj = [];
        $dateTimeObject = $this->getObjectTimeInfo($object);
        $start_date = $dateTimeObject['start_date'] ?? '';
        $start_time = $dateTimeObject['start_time'] ?? '';
        $deadline = $dateTimeObject['deadline'] ?? '';
        $end_time = $dateTimeObject['end_time'] ?? '';

        if((isset($start_date) && !empty($start_date)) && (isset($start_time) && !empty($start_time))) {
            $newObj['start_date'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('Y-m-d');
            if($start_time == "00:00:00") {
                $newObj['start_time'] = "";
            } else {
                $newObj['start_time'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('H:i:s');
            }
        } else if(isset($start_date) && !empty($start_date)) {
            $start_time = "00:00";
            $newObj['start_date'] = Carbon::make($start_date . ' ' . $start_time,$this->timezone)->format('Y-m-d');
            $newObj['start_time'] = "";
        } else {
            $newObj['start_date'] = "";
            $newObj['start_time'] = "";
        }

        if((isset($deadline) && !empty($deadline)) && (isset($end_time) && !empty($end_time))) {
            $newObj['deadline'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('Y-m-d');
            if($end_time == "00:00:00") {
                $newObj['end_time'] = "";
            } else {
                $newObj['end_time'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('H:i:s');
            }
        } else if(isset($deadline) && !empty($deadline)) {
            $end_time = "00:00";
            $newObj['deadline'] = Carbon::make($deadline . ' ' . $end_time,$this->timezone)->format('Y-m-d');
            $newObj['end_time'] = "";
        } else {
            $newObj['deadline'] = "";
            $newObj['end_time'] = "";
        }

        return $newObj;
    }

    private function getSeeMoreAttendeeTable($object, $seeMoreList)
    {
        $users = User::where('company_id', $object['company_id'])->get();
        if (!empty($object['attendee']['processing'])) {
            foreach ($object['attendee']['processing'] as $item) {
                $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                $attendee['comment'] = $item['comment'];
                $attendee['image'] = $item['attachment_id'];
                $attendee['status'] = $item['status'];

                $seeMoreList[] = $attendee;
            }
        }
        return $seeMoreList;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/objects",
     *     tags={"Objects"},
     *     summary="Create new objects",
     *     description="Create new object",
     *     security={{"bearerAuth":{}}},
     *     operationId="createObject",
     *     @OA\RequestBody(
     *         description="Object schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Object")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request)
    {

        try {
            $input = $request->all();

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                // if (!$input['type'] || $user['role']['level'] == 0) return null;
              
                if ($input['type'] == 'routine') {
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
                    if (!empty($user->role_id) && $user->role_id == 3  && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else
                    if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                   

                    // if (!empty($user->employee->nearest_manager)) {
                    //     $input['responsible'] = $user->employee->nearest_manager;
                    //     if(empty($input['responsible_employee_array'])){
                    //         $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                    //     }
                    // } else {
                    //     $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                    //     if ($companyAdmin) {
                    //         $input['responsible'] = $companyAdmin->id;
                    //         if(empty($input['responsible_employee_array'])){
                    //             $input['responsible_employee_array'] = (array($companyAdmin->id));
                    //         }
                    //     }
                    // } 

                    $rules = Routine::$rules;
                    $finput = $input;

                    if (!empty($input['resource_id'])) {
                        $obj = Routine::where('id', $input['resource_id'])->first();
                        if (!empty($obj)) {
                            if (!empty($obj['used_count'])) {
                                $cnt = $obj['used_count'];
                            } else {
                                $cnt = 0;
                            }
                            $r = Routine::where('id', $input['resource_id'])->update([
                                'used_count' => $cnt + 1,
                            ]);
                        }
                    }
                    $input = $this->getRoutineData($input);
                  
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
                    $finput['source_id'] = $newRoutine->id;
                    $finput['start_time'] = $newinput['start_time'] ?? '';
                    $finput['deadline'] = $newinput['deadline'] ?? '';
                    $finput['responsible_employee_array'] = $finput['responsible_employee_array'] ?? '';
                    $finput['attendee_employee_array'] = $finput['attendee_employee_array'] ?? '';
                    $input['responsible_department_array'] = $finput['responsible_department_array'] ?? '';
                    $input['attendee_department_array'] = $finput['attendee_department_array'] ?? '';
                    Schedule::create(['routine_id' => $newRoutine->id,'schedule_data' => json_encode($finput)]);
                    $newObject = $this->createObject($finput, $user);


                    // if ($newRoutine) {

                    //     if ($user['role_id'] == 1) {
                    //         $this->pushNotificationToAllCompanies('Routine', $newRoutine['id'], $newRoutine['name'], 'create');
                    //     }
                    //     if ($newRoutine['responsible_id']) {
                    //         $this->pushNotification($user['id'], $user['company_id'], 2, [$newRoutine['responsible_id']], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'responsible');
                    //     }
                    //     if (!empty($inputRoutine['attendingEmpsArray'])) {
                    //         $this->pushNotification($user['id'], $user['company_id'], 2, $inputRoutine['attendingEmpsArray'], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'assigned');
                    //     }
                    //     // if(!empty($input['responsible_employee_array'])  ){
                    //     //     $encode = ($input['responsible_employee_array']); 
                    //     //     foreach($encode as $responsible_employee_array){ 
                    //     //         $n = $this->pushNotification($user['id'], $user['company_id'], 2, [$responsible_employee_array], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'responsible'); 
                    //     //     }
                    //     // }
                    // }
                    $this->createSecurityObject($newObject, $input);
                } elseif ($input['type'] == 'checklist') {
                    $rules = Checklist::$rules;
                    $input['added_by'] = $user['id'];
                    if ($user->role_id > 1) {
                        $input['industry_id'] = $user['company']['industry_id'];
                        $input['company_id'] = $user['company_id'];
                    }
                    if (!empty($user->employee->nearest_manager)) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    $newChecklist = Checklist::create($input);
                    $input['source'] = 'checklist';
                    $input['source_id'] = $newChecklist->id;
                    $newObject = $this->createObject($input, $user);
                    $topics = $input['topics'];

                    foreach ($topics as $item) {
                        $topicRules = Topic::$rules;
                        if ($user['company_id']) {
                            $item['company_id'] = $user['company_id'];
                        }
                        $item['checklist_id'] = $newChecklist->id;
                        $topicValidator = Validator::make($item, $topicRules);

                        if ($topicValidator->fails()) {
                            $errors = ValidateResponse::make($topicValidator);
                            return $this->responseError($errors, 400);
                        }
                        $newTopic = Topic::create($item);
                        //Handle to create question //
                        $questions = $item['questions'];
                        foreach ($questions as $question) {
                            $questionRules = Question::$rules;
                            $question['added_by'] = $user['id'];
                            if ($user['company_id']) {
                                $question['company_id'] = $user['company_id'];
                            }
                            $question['checklist_id'] = $newChecklist->id;
                            $question['topic_id'] = $newTopic->id;
                            $question['status'] = 'New';
                            $questionValidator = Validator::make($question, $questionRules);

                            if ($questionValidator->fails()) {
                                $errors = ValidateResponse::make($questionValidator);
                                return $this->responseError($errors, 400);
                            }
                            $source_id = Question::create($question);
                            $this->createQuestionObject($input, $user, $question, $newObject);
                        }
                    }
                    if (!empty($input['resource_id'])) {
                        $obj = Checklist::where('id', $input['resource_id'])->first();
                        if (!empty($obj) && !empty($input['resource_id'])) {
                            if (!empty($obj['used_count'])) {
                                $cnt = $obj['used_count'];
                            } else {
                                $cnt = 0;
                            }
                            $r = Checklist::where('id', $input['resource_id'])->update([
                                'used_count' => $cnt + 1,
                            ]);
                        }
                    }
                    $this->updateChecklistOptions($input['defaultOptions'], $newChecklist->id);
                    $this->createSecurityObject($newObject, $input);
                } elseif ($input['type'] == 'risk-analysis' && !empty($input['source']) && $input['source'] == 'deviation') {

                    $devistatus = $input['status'];
                    $input['source_id'] = $input['object_id'];
                    $input['object_type'] = 'risk-analysis';
                    $input['status'] = 1;
                    if (!empty($user->role_id) && $user->role_id == 3  && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else
                    if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }

                    $newObject = $this->createObject($input, $user);
                    $object_dev = ObjectItem::find($input['object_id']);
                    if (!empty($object_dev)) {
                        $deviationData = Deviation::where("id", $object_dev->source_id)->first();
                        $dev['source_of_danger'] = $this->createObjectSourceOfDanger($input['source_of_danger'], $object_dev, $user, true);
                        $dev['action'] = 'risk';
                        $dev['happened_before'] = $input['happened_before'] ?? null;
                        $dev['corrective_action'] = $input['corrective_action'] ?? null;
                        $dev['specifications'] = $input['specifications'] ?? null;
                        $dev['status'] = $devistatus;
                        $deviationData->update($dev);
                    }
                } elseif ($input['type'] == 'task' && !empty($input['source']) && $input['source'] == 'deviation') {
                    $input['source_id'] = $input['object_id'];
                    $input['object_type'] = 'task';
                    if (!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    $newObject = $this->createObject($input, $user);
                    $object = DB::table('objects')->where('type', 'deviation')->where('id', $input['object_id'])->select('id', 'source_id')->first();
                    if (!empty($object)) {
                        $deviationData = Deviation::where("id", $object->source_id)->first();
                        $dev['action'] = 'task';
                        $dev['status'] = 2;
                        $dev['happened_before'] = $input['happened_before'] ?? null;
                        $dev['corrective_action'] = $input['corrective_action'] ?? null;
                        $dev['specifications'] = $input['specifications'] ?? null;
                        $deviationData->update($dev);
                    }
                } elseif ($input['type'] == 'task' && !empty($input['source']) && $input['source'] == 'risk') {
                    $input['source_id'] = $input['object_id'];
                    $input['object_type'] = 'task';
                    if (!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    $newObject = $this->createObject($input, $user);
                    $object = DB::table('objects')->where('type', 'deviation')->where('id', $input['object_id'])->select('id', 'source_id')->first();
                    if (!empty($object)) {
                        $deviationData = Deviation::where("id", $object->source_id)->first();
                        $dev['action'] = 'task';
                        $dev['happened_before'] = $input['happened_before'] ?? null;
                        $dev['corrective_action'] = $input['corrective_action'] ?? null;
                        $dev['specifications'] = $input['specifications'] ?? null;
                        $deviationData->update($dev);
                    }
                } elseif ($input['type'] == 'task' && !empty($input['source']) && $input['source'] == 'risk-analysis') {
                    if (!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    $newObject = $this->createTaskObject($input, $user);
                    $newObject['object_type'] = 'task';
                    $this->createSecurityObject($newObject, $input);
                } elseif ($input['type'] == 'risk-analysis' && !empty($input['source']) && $input['source'] == 'report') {
                    $input['source_id'] = $input['report_id'];
                    $input['object_type'] = 'risk-analysis';
                    $rules = RiskAnalysis::$rules;
                    $input['added_by'] = $user['id'];
                    $input['company_id'] = $user['company_id'];

                    if (!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    $validator = Validator::make($input, $rules);

                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
                    $newRiskAnalysis = RiskAnalysis::create($input);
                    if(isset($input['topics']) && !empty($input['topics'])){
                        $input['topics'] = json_encode($input['topics']); 
                    }
                    $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'risk', 'Risk analysis', $newRiskAnalysis['id'], $newRiskAnalysis['name'], 'responsible');
                    $newObject = $this->createObject($input, $user);
                    $object_dev = ObjectItem::find($newObject['id']);

                    if (!empty($object_dev)) {
                        $object_dev->type = 'risk-analysis';
                        $newRiskAnalysis['source_of_danger'] = $this->createObjectSourceOfDanger($input['source_of_danger'], $object_dev, $user, true);
                    }
                    RiskAnalysis::where('id', $newRiskAnalysis['id'])->update([
                        'object_id' => $newObject->id ?? ''
                    ]);
                } elseif ($input['type'] == 'risk-analysis' && !empty($input['source']) && $input['source'] == 'risk-analysis') {
                    // $input['source_id'] = '';
                    $input['object_type'] = 'risk-analysis';
                    $input['status'] = 1;
                    $rules = RiskAnalysis::$rules;
                    $input['added_by'] = $user['id'];

                    $input['company_id'] = $user['company_id'];

                    $validator = Validator::make($input, $rules);

                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
                    $newRiskAnalysis = RiskAnalysis::create($input);
                    if (!empty($input['responsible'])) {
                        $this->pushNotification($user['id'], $user['company_id'], 2, [$input['responsible']], 'risk', 'Risk analysis', $newRiskAnalysis['id'], $newRiskAnalysis['name'], 'responsible');
                    }
                    if ($user->employee->nearest_manager) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = json_encode(array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = json_encode(array($companyAdmin->id));
                            }
                        }
                    }

                    $newObject = $this->createObject($input, $user);

                    $object_dev = ObjectItem::find($newObject['id']);

                    if (!empty($object_dev)) {
                        $object_dev->type = 'risk-analysis';
                        $newRiskAnalysis['source_of_danger'] = $this->createObjectSourceOfDanger($input['source_of_danger'], $object_dev, $user, true);
                    }


                    RiskAnalysis::where('id', $newRiskAnalysis['id'])->update([
                        'object_id' => $newObject->id ?? ''
                    ]);
                } elseif ($input['type'] == 'task' && !empty($input['source']) && $input['source'] == 'report') {
                    $input['source_id'] = $input['report_id'];
                    if (!empty($user->role_id) && $user->role_id == 3 && empty($input['responsible_employee_array'])) {
                        $input['responsible_employee_array'] = array($user->id);
                    } else if ($user->employee->nearest_manager && empty($input['responsible_employee_array'])) {
                        $input['responsible'] = $user->employee->nearest_manager;
                        if (empty($input['responsible_employee_array'])) {
                            $input['responsible_employee_array'] = (array($user->employee->nearest_manager));
                        }
                    } else {
                        $companyAdmin = User::where('company_id', $user['company_id'])->where('role_id', 2)->first();
                        if ($companyAdmin) {
                            $input['responsible'] = $companyAdmin->id;
                            if (empty($input['responsible_employee_array'])) {
                                $input['responsible_employee_array'] = (array($companyAdmin->id));
                            }
                        }
                    }
                    if(isset($input['topics']) && !empty($input['topics'])){
                        $input['topics'] = json_encode($input['topics']); 
                    }
                    $newObject = $this->createObject($input, $user);
                    if (!empty($input['start_date']) && !empty($input['start_time'])) {
                        $input['start_time'] = strtotime($input['start_date'] . ' ' . $input['start_time']);
                    } elseif (!empty($input['start_date'])) {
                        $input['start_time'] = strtotime($input['start_date']);
                    }

                    if (!empty($input['deadline']) && !empty($input['end_time'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    } elseif (!empty($input['deadline'])) {
                        $input['deadline'] = strtotime($input['deadline']);
                    }
                    $input['type_id'] =  $input['report_id'];
                    $input['type'] =  'report';

                    $rules = Task::$rules;
                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
                    $input['object_id'] = $newObject->id ?? '';
                    $newTask = Task::create($input);
                    // $this->createTaskAssignee($input['taskAssignees'], $input['employee_array'], $user['id'], $newTask->id, $user['company_id']);
                } else {
                  
                    $newObject = $this->createObject($input, $user);
                }

                if ((isset($newObject['type']) && !empty($newObject['type'])) && $newObject['type'] == 'goal') {
                    if (!empty($input['resource_id'])) {
                        $obj = ObjectItem::find($input['resource_id']);
                        if (!empty($obj) && !empty($input['resource_id'])) {
                            if (!empty($obj['used_count'])) {
                                $cnt = $obj['used_count'];
                            } else {
                                $cnt = 0;
                            }
                            $r = ObjectItem::where('id', $input['resource_id'])->update([
                                'used_count' => $cnt + 1,
                            ]);
                        }
                    }
                }
                if ((isset($newObject['id']) && !empty($newObject)) && $newObject['id'] && (!empty($input['subGoal']) || !empty($input['activities']))) {
                    $subObject = [];
                    $subObjectArray = [];

                    if ($newObject['type'] == 'goal') {
                        $subObjectArray = $input['subGoal'];
                    } elseif ($newObject['type'] == 'instruction') {
                        $subObjectArray = $input['activities'];
                    }

                    if (!empty($subObjectArray)) {
                        foreach ($subObjectArray as $item) {
                            $item['category_id'] = $newObject['category_id'];
                            $item['source'] = $newObject['type'];
                            $item['source_id'] = $newObject['id'];
                            $item['is_template'] = $newObject['is_template'];


                            $newSubObject = $this->createObject($item, $user);

                            //                            $newSubObject['task'] = '';
                            //
                            //                            if ($newObject['type'] == 'goal') {
                            //                                $item['type'] = 'task';
                            //                                $item['source'] = $newSubObject['type'];
                            //                                $item['source_id'] = $newSubObject['id'];
                            //
                            //                                $newSubObject['task'] = $this->createObject($item, $user);
                            //                            }

                            $subObject[] = $newSubObject;
                        }

                        if (!$newObject['is_template']) {
                            $this->setTimeManagement($newObject['id']);
                        }
                    }
                    $newObject['subObject'] = $subObject;
                }

                return $this->responseSuccess($newObject);
            }
        } catch (Exception $e) {
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
        $inputRoutine['employee_array'] = $input['employee_array'] ?? '';
        $inputRoutine['department_array'] = $input['department_array'] ?? '';
        $inputRoutine['type'] = $input['type'] ?? '';
        $inputRoutine['object_type'] = $input['object_type'] ?? '';
        $inputRoutine['is_shared'] = $input['is_shared'] ?? '';
        $inputRoutine['duration'] = $input['duration'] ?? '';
        $inputRoutine['is_duration'] = $input['is_duration'] ? true : false;
        $inputRoutine['report_as_anonymous'] = $input['report_as_anonymous'] ?? 0;

        if((isset($inputRoutine['department_array']) && !empty($inputRoutine['department_array'])) && gettype($inputRoutine['department_array']) == 'string') { 
            $inputRoutine['department_array'] = json_decode($inputRoutine['department_array']);
        }
        if((isset($inputRoutine['employee_array']) && !empty($inputRoutine['employee_array'])) && gettype($inputRoutine['employee_array']) == 'string') {
            $inputRoutine['employee_array'] = json_decode($inputRoutine['employee_array']);
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
            if (!empty($input['attending_emps']) && !empty($input['attending_contact'])) {
                $inputRoutine['attendings_count'] = count($input['attending_emps']) + count($input['attending_contact']);
            } else {
                $inputRoutine['attendings_count'] = 0;
            }
        }

        // Handle to save Reminder/ start date - due date
        $inputRoutine['is_activated'] = $input['is_activated'];
       
        if (!empty($input['start_time'])) {
            $inputRoutine['start_time'] =  strtotime($input['start_time']);
        } else {
            // $inputRoutine['start_time'] = strtotime("today");
        }
        // if (!$input['is_activated']) {
        //     $inputRoutine['deadline'] = null;
        //     $inputRoutine['recurring'] = 'indefinite';
        // } else {
            if (!empty($input['deadline'])) {
                $inputRoutine['deadline'] = strtotime($input['deadline']);
            } else {
                $inputRoutine['deadline'] = null;
                // $inputRoutine['recurring'] = 'indefinite';
            }
            $inputRoutine['recurring'] = !empty($input['recurring']) ? ucfirst($input['recurring']) : '';
        // }
        // return !$input['is_activated'];
        $inputRoutine['is_attending_activated'] = $input['is_attending_activated'];

        return $inputRoutine;
    }

    private function createQuestionObject($input, $user, $question, $source_id)
    {
        $inputTemp = $input;

        $rules = ObjectItem::$rules;

        $input['type'] = 'checkpoint';
        $input['source_id'] = $source_id['id'];
        $input['company_id'] = $user['company_id'];
        $input['required_attachment'] = $question['required_attachment'] ?? 0;
        $input['required_comment'] = $question['required_comment'] ?? 0;

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        unset($input['topics']);
        $newObject = ObjectItem::create($input);
        if ($user['role_id'] > 1) {
            // Responsible
            $this->createObjectResponsible($inputTemp, $newObject, $user, $question);
        }
        return $newObject;
    }

    private function updateChecklistOptions($options, $checklistID)
    {
        if (!empty($options)) {
            foreach ($options as $option) {
                $optionData = ChecklistOption::find($option['id']);

                $optionRules = ChecklistOption::$rules;
                $newOption['name'] = $option['name'];
                $newOption['type_of_option_answer'] = $option['type_of_option_answer'];
                $newOption['company_id'] = $option['company_id'];
                $newOption['checklist_id'] = $checklistID;
                $newOption['count_option_answers'] = $option['count_option_answers'];
                $newOption['is_template'] = 0;
                $newOption['count_used_time'] = 1;
                $newOption['added_by'] = $option['added_by'];
                $optionValidator = Validator::make($newOption, $optionRules);
                if ($optionValidator->fails()) {
                    $errors = ValidateResponse::make($optionValidator);
                    return $this->responseError($errors, 400);
                }

                if (!empty($optionData)) {
                    if ($option['is_template'] == 1) {
                        // ChecklistOption::create($newOption);
                        if (!empty($optionData)) {
                            $optionData->update(['count_used_time' => $optionData['count_used_time'] + 1]);
                        }
                    }
                    //  else { 
                    // $optionData->update(['checklist_id' => $checklistID]);
                    // }
                } else {
                    ChecklistOption::create($newOption);
                }
            }
        }
        return $options;
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
        if($input['type']  == 'checklist'){
            unset($input['topics']);
        }
        $rules = ObjectItem::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        $newObject = ObjectItem::create($input);

        if (isset($input['connectToArray'])  && !empty($input['connectToArray'])) {
            $this->addConnectToObject($user, $newObject['id'], $newObject['type'], $input['connectToArray']);
        }

        if (($newObject['type'] == 'instruction' || $newObject['type'] == 'risk' || $newObject['type'] == 'risk-analysis') && !$newObject['is_template']) {
            // Handle to save Security
            $this->createSecurityObject($newObject, $input);
        }
        // if object is NOT a Resource
        if (!$newObject['is_template']) {
            // Responsible

            if(empty($inputTemp['responsible_employee_array'])) {
                if(isset($user->role_id) && $user->role_id == 4) {
                    $employee = Employee::where('user_id', Auth::id())->first();
                    if(isset($employee->nearest_manager) && !empty($employee->nearest_manager)) {
                        $inputTemp['responsible_employee_array'] = [$employee->nearest_manager];
                    }
                } else {
                    $inputTemp['responsible_employee_array'] = [Auth::id()];
                }
            }

            if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) { 
                $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
                // if (!empty($inputTemp['responsible_required_comment']) && $inputTemp['responsible_required_comment'] || !empty($inputTemp['responsible_required_attachment']) && $inputTemp['responsible_required_attachment']) {
                $newObject->processing = $this->createObjectResponsibleProcessing($newObject->responsible, $user, false, $input);
                // }
            }

            // Attendee
            // $this->createObjectAttendee($inputTemp, $newObject, $user);

            if(empty($inputTemp['attendee_employee_array'])) {
                $inputTemp['attendee_employee_array'] = [Auth::id()];
            }

            if (!empty($inputTemp['attendee_employee_array']) && $inputTemp['attendee_employee_array'] || !empty($inputTemp['attendee_department_array']) && $inputTemp['attendee_department_array']) {
                $newObject->attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);
                // Attendee processing
                $newObject->processing = $this->createObjectAttendeeProcessing($newObject->attendee, $user, false, $input);
            } else if (!empty($inputTemp['attendee_all']) && $inputTemp['attendee_all'] == true) {
                $newObject->attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

                // Attendee processing
                $newObject->processing = $this->createObjectAttendeeProcessing($newObject->attendee, $user, false, $input);
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
        if(empty($inputTemp['responsible_employee_array'])) {
            if(isset($user->role_id) && $user->role_id == 4) {
                $employee = Employee::where('user_id', Auth::id())->first();
                if(isset($employee->nearest_manager) && !empty($employee->nearest_manager)) {
                    $inputTemp['responsible_employee_array'] = [$employee->nearest_manager];
                }
            } else {
                $inputTemp['responsible_employee_array'] = [Auth::id()];
            }
        }

        if(empty($inputTemp['attendee_employee_array'])) {
            $inputTemp['attendee_employee_array'] = [Auth::id()];
        }
        // Responsible
        if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
            $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
            $newObject->processing = $this->createObjectResponsibleProcessing($newObject->responsible, $user, false, $input);
        }

        // Attendee
        if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
            $newObject->attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

            // Attendee processing
            $newObject->processing = $this->createObjectAttendeeProcessing($newObject->attendee, $user);
        } else if (!empty($inputTemp['attendee_all']) && $inputTemp['attendee_all'] == true) {
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
       
        $objectData->update($input);
      

        // if (!empty($input['connectToArray'])) {
            $input['connectToArray'] = $input['connectToArray'] ?? [];
            $this->updateConnectToObject($user, $objectData['id'], $objectData['type'], $input['connectToArray']);
        // }
       
        // if object is NOT a Resource
        if (!$objectData['is_template']) {
            if ($objectData['type'] == 'instruction') {
                // Handle to save Security/
                $this->updateSecurityObject('instruction', $inputTemp, $user['id']);
            }
            // Responsible
            if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) {
                $objectData->responsible = $this->createObjectResponsible($inputTemp, $objectData, $user, true);
                $objectData->processing = $this->createObjectResponsibleProcessing($objectData->responsible, $user, false, $inputTemp);
            }

            // Attendee
            if (!empty($inputTemp['attendee_employee_array']) || !empty($inputTemp['attendee_department_array'])) {
                $objectData->attendee = $this->createObjectAttendee($inputTemp, $objectData, $user, true);

                // Attendee processing
                // $attendee = Attendee::where('object_id', $objectData['id'])->first();
                $objectData->processing = $this->createObjectAttendeeProcessing($objectData->attendee, $user, true);
            } else if (!empty($inputTemp['attendee_all']) && $inputTemp['attendee_all'] == true) {
                $objectData->attendee = $this->createObjectAttendee($inputTemp, $objectData, $user);

                // Attendee processing
                $objectData->processing = $this->createObjectAttendeeProcessing($objectData->attendee, $user, true);
            }

            // Time management
            if (!empty($inputTemp['start_date']) || !empty($inputTemp['deadline'])) {
                $objectData->time = $this->createObjectTimeManagement($inputTemp, $objectData, $user, true);
            }
        }

        // Source of danger
        if ($inputTemp['type'] == 'risk-analysis') {
            $objectData->source_of_danger = $this->createObjectSourceOfDanger($inputTemp['source_of_danger'], $objectData, $user, true);
        }

        return $objectData;
    }

    private function createObjectResponsible($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
        if(!empty($inputObject['responsible_required_comment'])){
            $input['required_comment'] = $inputObject['responsible_required_comment'];
        }
        if(!empty($inputObject['responsible_required_attachment'])){
            $input['required_attachment'] = $inputObject['responsible_required_attachment'];
        }
        
        $input['start_time'] = $inputData['start_time'] ?? '';
        $input['start_date'] = $inputData['start_date'] ?? '';
        if ($inputObject['isDefaultResponsible']) {
            if(isset($user['role_id']) && $user['role_id'] == 4) {
                $employee = Employee::where('user_id', $user['id'])->first();
                if(isset($employee->nearest_manager) && !empty($employee->nearest_manager)) {
                    $input['employee_array'] = json_encode(array($employee->nearest_manager));
                }
            } else {
                $input['employee_array'] = json_encode(array($user['id']));
            }
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
            $input['department_array'] = !empty($inputObject['responsible_department_array']) ? json_encode($inputObject['responsible_department_array']) : '';
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
        if(isset($input['employee_array']) && !empty($input['employee_array'])) {
            $required_comments_array = [];
            $required_attachments_array = [];
            $employee_arr = json_decode($input['employee_array']);
            foreach($employee_arr as $employee) {
                $required_comments_array[] = $inputObject['responsible_required_comment'] ?? 0;
                $required_attachments_array[] = $inputObject['responsible_required_attachment'] ?? 0;
            }
            $input['required_comments_array'] = implode(',', $required_comments_array);
            $input['required_attachments_array'] = implode(',', $required_attachments_array);
        }
        if ($requestEdit) {
            if($object['type'] == 'checklist' || $object['type'] == 'checkpoint'){
                Responsible::where('object_id', $object['id'])->delete();
            }else{
                $responsible = Responsible::where('object_id', $object['id'])->first();
            }
            // Responsible::where('object_id', $object['id'])->delete();
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
        if($object['type'] == 'checklist' || $object['type'] == 'checkpoint'){
            $responsible = Responsible::create($input);
        }else{
            if (!$requestEdit) {
                $responsible = Responsible::create($input);
            }
        }
       
        $this->requestPushNotification($user['id'], $user['company_id'], json_decode($responsible['employee_array']), 'notification', $object, 'responsible');

        return $responsible;
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

        if(isset($input['employee_array']) && !empty($input['employee_array'])) {
            $required_comments_array = [];
            $required_attachments_array = [];
            $employee_arr = json_decode($input['employee_array']);
            foreach($employee_arr as $employee) {
                $required_comments_array[] = $inputObject['attendee_required_comment'] ?? 0;
                $required_attachments_array[] = $inputObject['attendee_required_attachment'] ?? 0;
            }
            $input['required_comments_array'] = implode(',', $required_comments_array);
            $input['required_attachments_array'] = implode(',', $required_attachments_array);
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

    public function attendee_process(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $det =  AttendeeProcessing::where('id', $request->processing_id)->first();
                $input['url'] = null;
                $input['company_id'] = $user['company_id'];
                $input['processing_id'] = $request->processing_id;
                $input['object_id'] = $request->object_id;
                $input['added_by'] = $user['id'];
                if (!empty($user['role_id'])) {
                    $role = Role::where('id', $user['role_id'])->first();
                    $input['added_by_role'] = $role->name ?? '';
                }
                $newObject = '';
                if (!empty($request->file('attachment'))) {
                    $path = Storage::disk('public')->putFile('/' . $user['company_id'], $request->file('attachment'));
                    $baseUrl = config('app.app_url');
                    $input['url'] = $baseUrl . "/api/v1/image/" . $path;
                    if (!empty($input['url'])) {
                        $newObject = Attachment::create($input);
                    }
                }
                $status = 'completed';
                // if (!empty($request->processing_id) && $det->added_by) {
                //     $resultTimeline = ExtendedTimeline::where('process_id', $request->processing_id)->where('requested_by', $det->added_by)->first();
                //     if (!empty($resultTimeline)) {
                //         $status = 'completed_overdue';
                //     }
                // }
                $objectData = ObjectItem::where('id', $request->object_id)->with(['time'])->first();
                $time_info =  $this->getOnlyDateTimeBasedOnTimezone($objectData);
                $start_date = $time_info['start_date'] ?? '';
                $start_time = isset($time_info['start_time']) && !empty($time_info['start_time']) ? $time_info['start_time'] : '00:00:00';
                $end_date = $time_info['deadline'] ?? '';
                $end_time = isset($time_info['end_time']) && !empty($time_info['end_time']) ? $time_info['end_time'] : '00:00:00'; 
                
                if (!empty($request->processing_id) && $det->added_by) {
                    $resultTimeline = ExtendedTimeline::where('process_id', $request->processing_id)->where('requested_by', $det->added_by)->latest()->first();
                    if (!empty($resultTimeline)) {
                        $end_date = $resultTimeline['deadline_date'] ?? $time_info['deadline'];
                        $end_time = isset($resultTimeline['deadline_time']) && !empty($resultTimeline['deadline_time']) ? $resultTimeline['deadline_time'] : '00:00:00'; 
                    }
                }
                if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                    $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                    // $tomorrowDate = Carbon::tomorrow()->setTimezone($this->timezone)->format('Y-m-d H:i:s');
                    // $startDay = Carbon::make($start_date . ' ' . $start_time);
                    $endDay = Carbon::make($end_date . ' ' . $end_time);
                    if($todayDate > $endDay->format('Y-m-d H:i:s')) {
                        $status = "completed_overdue";
                    }
                }

                AttendeeProcessing::where('id', $request->processing_id)->update([
                    'comment' => $request->comment ?? '',
                    'attachment_id' => $newObject->id ?? null,
                    'status' => $status,
                ]);
                $det =  AttendeeProcessing::where('id', $request->processing_id)->first();
                return $det;
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function responsible_process(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $det =  ResponsibleProcessing::where('id', $request->processing_id)->first();
                $baseUrl = config('app.app_url');
                $input['url'] = null;
                $input['company_id'] = $user['company_id'];
                $input['processing_id'] = $request->processing_id;
                $input['object_id'] = $request->object_id;
                $input['added_by'] = $user['id'];

                if (!empty($user['role_id'])) {
                    $role = Role::where('id', $user['role_id'])->first();
                    $input['added_by_role'] = $role->name ?? '';
                }
                $newObject = '';
                if (!empty($request->file('attachment'))) {
                    $path = Storage::disk('public')->putFile('/' . $user['company_id'], $request->file('attachment'));
                    $input['url'] = $baseUrl . "/api/v1/image/" . $path;
                    // if (!empty($input['url'])) {
                    //     $newObject = Attachment::create($input);
                    // } 
                }
                $status = 'completed';
                // if(!empty($request->processing_id) && $det->added_by){
                //     $resultTimeline = ExtendedTimeline::where('process_id', $request->processing_id )->where('requested_by',$det->added_by)->first();
                //     if(!empty($resultTimeline)){
                //         $status = 'completed_overdue';
                //     }
                // }
                ResponsibleProcessing::where('id', $request->processing_id)->update([
                    'responsible_comment' => $request->comment ?? '',
                    'responsible_attachment' => $input['url'] ?? null,
                    'status' => $status,
                ]);
                $det =  ResponsibleProcessing::where('id', $request->processing_id)->first();
                return $det;
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


    private function createObjectAttendeeProcessing($attendee, $user, $requestEdit = false, $inputData = false)
    {
        if ($requestEdit) {
            AttendeeProcessing::where('attendee_id', $attendee['id'])->delete();
        }

        $input['company_id'] = $user['company_id'];
        $input['attendee_id'] = $attendee['id'];
        $start_time = $inputData['start_time'] ?? '00:00';
        $start_date = $inputData['start_date'] ?? '';
        if ($start_date && $start_time) {
            $theDay = Carbon::make($start_date . ' ' . $start_time,$this->timezone); 
            if ($theDay->isPast() == true || $theDay->isToday() == true) {
                $task_status = 'ongoing';
            } else if ($theDay->isTomorrow() == true || $theDay->isFuture() == true) {
                $task_status = 'new';
            }
        } else {
            $task_status = 'new';
        }
        $input['status'] = $task_status;
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
            // $result[] = AttendeeProcessing::updateOrCreate(['attendee_id'=>$attendee['id'],'added_by'=>$item],[$input]);
        }
        return $result;
    }

    private function createUpdateObjectAttendeeProcessing($attendee, $user, $requestEdit = false, $inputData = false)
    {
        if ($requestEdit) {
            AttendeeProcessing::where('attendee_id', $attendee['id'])->delete();
        }
        $input['company_id'] = $user['company_id'];
        $input['attendee_id'] = $attendee['id'];
        $start_time = !empty($inputData['start_time']) ? date('H:i:s', $inputData['start_time']) : '';
        $start_date = !empty($inputData['start_date']) ?  $inputData['start_date']  : '';


        if ($start_date && $start_time) {
            $theDay = Carbon::make($start_date . ' ' . $start_time);
            $theDay->isToday();
            $theDay->isPast();
            $theDay->isFuture();
            $theDay->isTomorrow();

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
            $exist = AttendeeProcessing::where(['attendee_id' => $attendee['id'], 'added_by' => $item])->first();
            if (!empty($exist)) {
                AttendeeProcessing::where(['attendee_id' => $attendee['id'], 'added_by' => $item])->update([
                    "company_id" => $input["company_id"],
                    "attendee_id" => $input["attendee_id"],
                    "status" => $input["status"],
                    "added_by" => $input["added_by"]
                ]);
                $newdata = AttendeeProcessing::where(['attendee_id' => $attendee['id'], 'added_by' => $item])->first();
                $result[] = $newdata;
            } else {
                AttendeeProcessing::create($input);
                $newdata = AttendeeProcessing::where(['attendee_id' => $attendee['id'], 'added_by' => $item])->first();
                $result[] = $newdata;
            }
        }
        return $result;
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

    private function createObjectTimeManagement($inputObject, $object, $user, $requestEdit = false)
    {
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
       
        if(isset($inputObject['start_time']) && !empty($inputObject['start_time'])){

            $startTimeArray = explode(':', $inputObject['start_time']);
            $startTimeCount =  count($startTimeArray);
            if($startTimeCount >= 3){
                array_pop($startTimeArray);
                $inputObject['start_time'] =  implode(':',$startTimeArray);
            }
        }

        if(isset($inputObject['end_time']) && !empty($inputObject['end_time'])){

            $endTimeArray = explode(':', $inputObject['end_time']);
            $endTimeCount =  count($endTimeArray);
            if($endTimeCount >= 3){
                array_pop($endTimeArray);
                $inputObject['end_time'] =  implode(':',$endTimeArray);
            }
        }
        

        if (!empty($inputObject['start_time']) && !empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date'] . ' ' . $inputObject['start_time']);
            $input['start_time'] = $inputObject['start_time'];
        } elseif (!empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date']); 
            $input['start_time'] = Carbon::now($this->timezone)->format('H:i');
        } else {
            $input['start_date'] = strtotime("today");
            $input['start_time'] = Carbon::now($this->timezone)->format('H:i');
        }
      
        if (!empty($inputObject['end_time']) && !empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline'] . ' ' . $inputObject['end_time']);
            $input['end_time'] = $inputObject['end_time'];
        } elseif (!empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline']);
            $input['end_time'] = $input['start_time'] ?? null;
        } else {
            $input['deadline'] = strtotime("+1 day", $input['start_date']);
            $input['end_time'] = $input['start_time'] ?? null;
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

    // create list source of danger of main object
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
        if ($object['source'] == 'risk-analysis' && $object['type'] == 'risk-analysis') {
            $input['object_id'] = $object->id;
        } else if ($object['source'] == 'risk-analysis') {
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

    private function createObjectOption($inputObject, $object, $requestEdit = false)
    {
        $input['object_id'] = $object['id'];
        if (!empty($inputObject['show_in_risk_analysis'])) {
            $input['show_in_risk_analysis'] = $inputObject['show_in_risk_analysis'];
        }
        $input['number_used_time'] = 0;

        $rules = ObjectOption::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $option = ObjectOption::create($input);
        return $option;
    }

    private function addRiskElementToRiskAnalysis($inputArray, $object)
    {
        if (!empty($inputArray)) {
            foreach ($inputArray as $risk) {
                $riskElement = ObjectOption::where('object_id', $risk)->first();
                if (empty($riskElement)) {
                    return $this->responseException('Not found risk element', 404);
                }
                $array = [];
                if ($riskElement['number_used_time'] > 0) {
                    $array = json_decode($riskElement['risk_analysis_array']);
                }
                array_push($array, $object['id']);
                $riskElement->update([
                    'number_used_time' => $riskElement['number_used_time'] + 1,
                    'risk_analysis_array' => json_encode($array)
                ]);
            }
        }
    }

    private function showStatusNewByDate($object)
    {
        if ($object['status'] == 'new') {
            if (date("Y-m-d", strtotime($object['time']['created_at'])) == date("Y-m-d")) {
                return $object['status'] = 'new';
            } else {
                if (date("Y-m-d", $object['time']['start_date']) <= date("Y-m-d")) {
                    return $object['status'] = 'ongoing';
                } else {
                    return $object['status'] = 'pending';
                }
            }
        } else {
            return $object['status'];
        }
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

    /**
     * @OA\Get(
     *     path="/api/v1/objects/{id}",
     *     tags={"Objects"},
     *     summary="Get object by id",
     *     description="Get object by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getObjectByIdAPI",
     *     @OA\Parameter(
     *         description="object id",
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
    public function getAttendee($id)
    {
        $data = [];
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($id) {
                    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.id', $id)
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    $deviationData = Routine::where('id',  $objectData['source_id'])->first();
                    $objectData = $this->getObjectDetailInfo($objectData, $user);
                    // $data['attendee']  = $objectData['processingInfo'];
                    if(isset($objectData['processingInfo']) && !empty($objectData['processingInfo'])){
                        foreach ($objectData['processingInfo'] as $key => $attendee) {
                            $start_date = $attendee['time_info']['start_date'] ?? '';
                            $start_time = isset($attendee['time_info']['start_time']) && !empty($attendee['time_info']['start_time']) ? $attendee['time_info']['start_time'] : '00:00:00';
                            $end_date = $attendee['time_info']['deadline'] ?? '';
                            $end_time = isset($attendee['time_info']['end_time']) && !empty($attendee['time_info']['end_time']) ? $attendee['time_info']['end_time'] : '00:00:00'; 
                            if(isset($attendee['extended_timeline']) && !empty($attendee['extended_timeline'])){
                                $end_date = $attendee['extended_timeline']['deadline_date'] ?? $attendee['time_info']['deadline'] ;
                                $end_time = isset($attendee['extended_timeline']['deadline_time']) && !empty($attendee['extended_timeline']['deadline_time']) ? $attendee['extended_timeline']['deadline_time'] : '00:00:00'; 
                            }
                            $task_status = "new";
                            if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                $startDay = Carbon::make($start_date . ' ' . $start_time);
                                $endDay = Carbon::make($end_date . ' ' . $end_time);
                                if(isset($attendee['status']) && ($attendee['status'] == "closed" || $attendee['status'] == "3")) {
                                    $task_status = "closed";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "completed")) {
                                    $task_status = "completed";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "approved")) {
                                    $task_status = "approved";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "disapproved")) {
                                    $task_status = "disapproved";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "completed_overdue")) {
                                    $task_status = "completed_overdue";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "approved_overdue")) {
                                    $task_status = "approved_overdue";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "request")) {
                                    $task_status = "request";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "overdue")) {
                                    $task_status = "overdue";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "disapproved_with_extended")) {
                                    $task_status = "disapproved_with_extended";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "timeline_disapproved")) {
                                    $task_status = "timeline_disapproved";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "disapproved_overdue")) {
                                    $task_status = "disapproved_overdue";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "Removed")) {
                                    $task_status = "Removed";
                                } else if(isset($attendee['status']) && ($attendee['status'] == "Reassigned")) {
                                    $task_status = "Reassigned";
                                } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                    $task_status = "new";
                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                    $task_status = "new";
                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                    $task_status = "ongoing";
                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($attendee['status']) && ($attendee['status'] !== "closed" || $attendee['status'] !== "3"))) {
                                    $task_status = "overdue";
                                } else {
                                    $task_status = "new";
                                }
                                $attendee['status'] = $task_status ?? '';
                                $data['attendee'][] = $attendee;
                            }
                        }
                    }
                     
                    // $data['attendee']['responsibleName'] = $this->getDeviationResponsible($deviationData->id, $user);
                }
            }
            return $data;
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
    public function getResponsible($id)
    {
        $data = [];
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($id) {
                    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.id', $id)
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    $deviationData = Routine::where('id',  $objectData['source_id'])->first();
                    $objectData = $this->getObjectDetailInfoAllResp($objectData, $user);
                    // $data['responsible'] = $objectData['responsible_employeeData'] ?? '';
                    if(isset($objectData['responsible_employeeData']) && !empty($objectData['responsible_employeeData'])){
                        foreach ($objectData['responsible_employeeData'] as $key => $responsible) {
                            $start_date = $responsible['time_info']['start_date'] ?? '';
                            $start_time = isset($responsible['time_info']['start_time']) && !empty($responsible['time_info']['start_time']) ? $responsible['time_info']['start_time'] : '00:00:00';
                            $end_date = $responsible['time_info']['deadline'] ?? '';
                            $end_time = isset($responsible['time_info']['end_time']) && !empty($responsible['time_info']['end_time']) ? $responsible['time_info']['end_time'] : '00:00:00'; 
                            $task_status = "new";
                            if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                $startDay = Carbon::make($start_date . ' ' . $start_time);
                                $endDay = Carbon::make($end_date . ' ' . $end_time);
                                if(isset($responsible['status']) && ($responsible['status'] == "closed" || $responsible['status'] == "3")) {
                                    $task_status = "closed";
                                } else if(isset($responsible['status']) && ($responsible['status'] == "Removed")) {
                                    $task_status = "Removed";
                                } else if(isset($responsible['status']) && ($responsible['status'] == "Reassigned")) {
                                    $task_status = "Reassigned";
                                } else if(isset($responsible['status']) && ($responsible['status'] == "completed")) {
                                    $task_status = "completed";
                                } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                    $task_status = "new";
                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                    $task_status = "new";
                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                    $task_status = "ongoing";
                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($responsible['status']) && ($responsible['status'] !== "closed" || $responsible['status'] !== "3"))) {
                                    $task_status = "overdue";
                                } else {
                                    $task_status = "new";
                                }
                                $responsible['status'] = $task_status ?? '';
                                $data['responsible'][] = $responsible;
                            }
                        }
                    }
                    // $data['attendee']['responsibleName'] = $this->getDeviationResponsible($deviationData->id, $user);
                }
            }
            return $data;
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getProcessingInfo($id, $processing_id)
    {
        $data = [];
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($id) {
                    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.id', $id)
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    $deviationData = Routine::where('id',  $objectData['source_id'])->first();
                    $objectData = $this->getObjectDetailInfoSingle($objectData, $user, $processing_id);
                    $data = !empty($objectData) ? $objectData : '';
                    if(isset($objectData['processingInfo']) && !empty($objectData['processingInfo'])){
                        $processingInfo = $objectData['processingInfo'];
                        $start_date = $processingInfo[0]['time_info']['start_date'] ?? '';
                            $start_time = isset($processingInfo[0]['time_info']['start_time']) && !empty($processingInfo[0]['time_info']['start_time']) ? $processingInfo[0]['time_info']['start_time'] : '00:00:00';
                            $end_date = $processingInfo[0]['time_info']['deadline'] ?? '';
                            $end_time = isset($processingInfo[0]['time_info']['end_time']) && !empty($processingInfo[0]['time_info']['end_time']) ? $processingInfo[0]['time_info']['end_time'] : '00:00:00'; 
                            $task_status = "new";
                            if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                $startDay = Carbon::make($start_date . ' ' . $start_time);
                                $endDay = Carbon::make($end_date . ' ' . $end_time);
                                if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "closed" || $processingInfo[0]['status'] == "3")) {
                                    $task_status = "closed";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "completed")) {
                                    $task_status = "completed";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "approved")) {
                                    $task_status = "approved";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "disapproved")) {
                                    $task_status = "disapproved";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "completed_overdue")) {
                                    $task_status = "completed_overdue";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "approved_overdue")) {
                                    $task_status = "approved_overdue";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "request")) {
                                    $task_status = "request";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "overdue")) {
                                    $task_status = "overdue";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "timeline_disapproved")) {
                                    $task_status = "timeline_disapproved";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "disapproved_overdue")) {
                                    $task_status = "disapproved_overdue";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "Removed")) {
                                    $task_status = "Removed";
                                } else if(isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] == "Reassigned")) {
                                    $task_status = "Reassigned";
                                } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                    $task_status = "new";
                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                    $task_status = "new";
                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                    $task_status = "ongoing";
                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($processingInfo[0]['status']) && ($processingInfo[0]['status'] !== "closed" || $processingInfo[0]['status'] !== "3"))) {
                                    $task_status = "overdue";
                                } else {
                                    $task_status = "new";
                                }
                                $processingInfo[0]['status'] = $task_status ?? '';
                                $data['processingInfo'] = $processingInfo;
                            }
                    }
                }
            }
            return $data;
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getProcessingInfoResponsible($id, $processing_id)
    {
        $data = [];
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($id) {
                    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.id', $id)
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    $deviationData = Routine::where('id',  $objectData['source_id'])->first();
                    $objectData = $this->getObjectDetailInfoSingleResp($objectData, $user, $processing_id);
                    $data = !empty($objectData) ? $objectData : '';
                }
            }
            return $data;
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


    public function show($id)
    {
        try {
            $source_of_danger = [];
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                //                $type = $request->objectType;
                //
                //                if (!$type) return $this->responseSuccess([]); 
                $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.id', $id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first();

                if (!empty($objectData->updated_by)) {
                    $user = User::where('id', $objectData->updated_by)->first();
                    if (!empty($user)) {
                        unset($objectData->updated_by);
                        $objectData->updated_by_name = $user->first_name . ' ' . $user->last_name;
                    }
                }

                if (empty($objectData)) {
                    return $this->responseException('Not found object', 404);
                }
                $subObjectType = '';
                if ($objectData['type'] == 'goal') {
                    $subObjectType = 'sub-goal';
                } elseif ($objectData['type'] == 'instruction') {
                    $subObjectType = 'instruction-activity';
                    $objectData = $this->getSecurityObject('instruction', $objectData);
                } elseif ($objectData['type'] == 'risk-analysis' || $objectData['type'] == 'task') {
                    if($objectData['source'] == 'deviation'){
                        $objectData['sourceData']  = $this->getSourceObject($objectData);
                    }
                    // if($objectData['source'] == 'routine'){
                    //     $routineData = ObjectItem::where('id', $objectData['source_id'])->where('type', 'routine')->with('time')->first();
                    //     if(isset($routineData) && !empty($routineData)){
                    //         $dateTimeObj = $this->getDateTimeBasedOnTimezone($routineData);
                    //         $objectData['sourceData'] = Routine::where('id', $routineData->source_id)->first();
                    //         $objectData['sourceData']['deadline'] = $dateTimeObj['deadline'];
                    //         $objectData['sourceData']['start_time'] = $dateTimeObj['start_time'];
                    //     }
                    // }
                    if($objectData['source'] == 'risk'){
                        $riskObj = ObjectItem::where('id',$objectData->source_id)->first(['type','source','source_id']);
                        if($riskObj['source'] == 'deviation'){
                            $objectData['sourceData']  = $this->getSourceObject($riskObj);
                        }
                    }

                    // source of danger
                    if(($objectData['source'] == 'risk-analysis' || $objectData['source'] == 'deviation' || $objectData['source'] == 'report') && $objectData['type'] == 'risk-analysis'){
                            $source_of_danger = SourceOfDanger::where('object_id',$objectData->id)->get();
                            if($objectData['source'] == 'report' && $objectData['type'] == 'risk-analysis'){
                                if(isset($objectData['topics']) && !empty($objectData['topics'])){
                                    $objectData['topics'] = json_decode($objectData['topics'],true); 
                                }
                            }
                    }else if ($objectData['source'] == 'risk-analysis' && $objectData['type'] == 'task') {
                        $source_of_danger = SourceOfDanger::where('object_id',$objectData->id)->get();
                        $objTopicSource = ObjectItem::where('id',$objectData->source_id)->first('topics');
                        if(isset($objTopicSource['topics']) && !empty($objTopicSource['topics'])){
                            $objectData['topics'] = json_decode($objTopicSource['topics'],true); 
                        }
                    }else if ($objectData['source'] == 'report' && $objectData['type'] == 'task') {
                            $source_of_danger = [];
                                if(isset($objectData['topics']) && !empty($objectData['topics'])){
                                    $objectData['topics'] = json_decode($objectData['topics'],true); 
                                }
                    } else if ($objectData['source'] == 'routine' && $objectData['type'] == 'task') {
                        $source_of_danger = [];
                    } else {
                        $objSource = ObjectItem::where('id',$objectData->source_id)->first('source_id');
                        if(isset($objSource)){
                            $source_of_danger = SourceOfDanger::where('object_id',$objSource->source_id)->get();
                        }
                    }

                    // $source_of_danger = SourceOfDanger::where('object_id', $id)->get();
                    $objectData['source_of_danger'] = $this->getSourceOfDangerDetail($source_of_danger);
                    $objectData = $this->getSecurityObject('risk-analysis', $objectData);
                    // list risk element
                    $objectData = $this->getRiskAnalysisDetail($objectData);

                    // $objectData->task_data = $objectData;

                    // task of risk analysis
                    $subObjectType = 'task';

                    if (isset($objectData) && !empty($objectData)) {
                        $task_data = $this->getDateTimeBasedOnTimezone($objectData);

                        $start_date = $task_data['start_date'] ?? '';
                        $start_time = isset($task_data['start_time']) && !empty($task_data['start_time']) ? $task_data['start_time'] : '00:00:00'; 
                        $end_date = $task_data['deadline'] ?? '';
                        $end_time = isset($task_data['end_time']) && !empty($task_data['end_time']) ? $task_data['end_time'] : '00:00:00'; 
                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                            if(isset($objectData->status) && ($objectData->status == "closed" || $objectData->status == "3")) {
                                $task_status = "closed";
                            } else if((isset($objectData->status) && $objectData['type'] == 'risk-analysis') && ($objectData->status == "completed" || $objectData->status == "1")) {
                                $task_status = "completed";
                            } else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                $task_status = "new";
                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                $task_status = "new";
                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                $task_status = "ongoing";
                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($objectData->status) && ($objectData->status !== "3" || $objectData->status !== "closed"))) {
                                $task_status = "overdue";
                            } else {
                                $task_status = "new";
                            }
                            $objectData->status = $task_status ?? '';
                        } 
                        $objectData->responsible_names = $this->getDeviationResponsible($objectData->id, $user);
                    }

                } elseif ($objectData['type'] == 'risk') {
                    $objectData = $this->getRiskElementDetail($objectData);
                    $objectData = $this->getSecurityObject('risk', $objectData);
                } else if ($objectData['type'] == 'checklist') {

                    if ($objectData['id']) {
                        $result = Topic::where('checklist_id',  $objectData['source_id'])->get();
                        if ($result) {
                            foreach ($result as $topic) {
                                if (!empty($topic['questions'])) {
                                    foreach ($topic['questions'] as $question) {
                                        $dataCheck = ChecklistOption::where("id", $question['default_option_id'])->first();
                                        $question->type_of_option_answer = $dataCheck['type_of_option_answer'] ?? '';
                                        $question->option_name = $dataCheck['name'] ?? '';
                                        $question->checklist_required_comment = $dataCheck['checklist_required_comment'] ?? '';
                                        $question->checklist_required_attachment = $dataCheck['checklist_required_attachment'] ?? '';
                                        $question->option_answers = ChecklistOptionAnswer::where("default_option_id", $question['default_option_id'])
                                            ->get();
                                    }
                                }
                            }
                        }
                    }
                    $resp =  Responsible::where('object_id', $id)->select('employee_array')->first();

                    $objectData['employee_array'] = $this->getSecurityObject('checklist', $objectData)["employee_array"] ?? '';
                    $objectData['employee_names'] = $this->getSecurityObject('checklist', $objectData)["employee_names"] ?? '';
                    // $objectData['employee_roles'] = $this->getSecurityObject('checklist', $objectData)["employee_roles"] ?? '';
                    $objectData['topic'] = $result;
                    $objectData['connect_to'] =   ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.company_id', $user['company_id'])
                        ->where('objects.id', $objectData['id'])
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                } else if ($objectData['type'] == 'routine') {
                    $task_data = [];
                    $objectData['routine'] = Routine::where('id',  $objectData['source_id'])->first();
                    $taskData = ObjectItem::where('type','task')->where('source','routine')->where('source_id',$objectData['source_id'])->with(['attendee', 'responsible', 'time'])->orderBy('id', 'desc')->take(5)->get();
                    if(isset($taskData) && !$taskData->isEmpty()){
                        foreach ($taskData as $key => $task) {
                            
                            $taskDateTime = $this->getDateTimeBasedOnTimezone($task);
                            $taskObj = $this->getObjectDetailInfo($task, $user);
                            
                            $task['status'] = $this->getObjectStatus($taskObj,$taskDateTime); 
                            if(isset($taskObj['processingInfo']) && !empty($taskObj['processingInfo'])){
                                foreach ($taskObj['processingInfo'] as $key => $attendee) {
                                    $start_date = $attendee['time_info']['start_date'] ?? '';
                                    $start_time = isset($attendee['time_info']['start_time']) && !empty($attendee['time_info']['start_time']) ? $attendee['time_info']['start_time'] : '00:00:00';
                                    $end_date = $attendee['time_info']['deadline'] ?? '';
                                    $end_time = isset($attendee['time_info']['end_time']) && !empty($attendee['time_info']['end_time']) ? $attendee['time_info']['end_time'] : '00:00:00'; 
                                    if(isset($attendee['extended_timeline']) && !empty($attendee['extended_timeline'])){
                                        $end_date = $attendee['extended_timeline']['deadline_date'] ?? $attendee['time_info']['deadline'] ;
                                        $end_time = isset($attendee['extended_timeline']['deadline_time']) && !empty($attendee['extended_timeline']['deadline_time']) ? $attendee['extended_timeline']['deadline_time'] : '00:00:00'; 
                                    }
                                    $task_status = "new";
                                    if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                                        $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                        $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                        $startDay = Carbon::make($start_date . ' ' . $start_time);
                                        $endDay = Carbon::make($end_date . ' ' . $end_time);
                                        if(isset($attendee['status']) && ($attendee['status'] == "closed" || $attendee['status'] == "3")) {
                                            $task_status = "closed";
                                        }else if(isset($attendee['status']) && $attendee['status'] == "Removed" || $attendee['status'] == "Reassigned" || $attendee['status'] == "disapproved_overdue" || $attendee['status'] == "disapproved_with_extended" || $attendee['status'] == "timeline_disapproved" || $attendee['status'] == "overdue" || $attendee['status'] == "request" || $attendee['status'] == "approved_overdue" || $attendee['status'] == "completed" || $attendee['status'] == "approved" || $attendee['status'] == "disapproved" || $attendee['status'] == "completed_overdue") { 
                                            $task_status = $attendee['status'];
                                        }else if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                            $task_status = "new";
                                        } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                            $task_status = "new";
                                        } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                            $task_status = "ongoing";
                                        } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($attendee['status']) && ($attendee['status'] !== "closed" || $attendee['status'] !== "3"))) {
                                            $task_status = "overdue";
                                        } else {
                                            $task_status = "new";
                                        }
                                        $attendee['status'] = $task_status ?? '';
                                    }
                                    
                                    
                                }
                            }
                            $task_data[] = $task; 
                        }
                        $objectData['task_data'] = $task_data;
                    }else{
                        $obj_reminder_data = $this->getDateTimeBasedOnTimezone($objectData);
                        $start_date = $obj_reminder_data['start_date'] ?? '';
                        $start_time = isset($obj_reminder_data['start_time']) && !empty($obj_reminder_data['start_time']) ? $obj_reminder_data['start_time'] : '00:00:00'; 
                        $end_date = $obj_reminder_data['deadline'] ?? '';
                        $end_time = isset($obj_reminder_data['end_time']) && !empty($obj_reminder_data['end_time']) ? $obj_reminder_data['end_time'] : '00:00:00'; 
                        if((!empty($start_date) && !empty($start_time)) && (!empty($end_date) && !empty($end_time))) {
                            $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                            $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                            $startDay = Carbon::make($start_date . ' ' . $start_time);
                            $endDay = Carbon::make($end_date . ' ' . $end_time);
                            if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                $objectData['status'] = "new";
                            } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                $objectData['status'] = "new";
                            } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                $objectData['status'] = "ongoing";
                            } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($objectData->status) && ($objectData->status !== "3" || $objectData->status !== "closed"))) {
                                $objectData['status'] = "closed";
                            }
                        }
                    }
                    $routineAllTask = ObjectItem::where('type','task')->where('source','routine')->where('source_id',$objectData['source_id'])->with(['attendee', 'responsible', 'time'])->get();
                    $objectData['routine_rate'] = 0;
                    if(isset($routineAllTask) && !$routineAllTask->isEmpty()){
                        $closeStatusCnt = 0;
                        foreach ($routineAllTask as $key => $allTask) {
                            $taskDateTime = $this->getDateTimeBasedOnTimezone($allTask);
                            $taskObj = $this->getObjectDetailInfo($allTask, $user);
                            $taskRoutineStatus = $this->getObjectStatus($taskObj,$taskDateTime); 
                            if((isset($taskRoutineStatus) && !empty($taskRoutineStatus)) && ($taskRoutineStatus == 'closed' || $taskRoutineStatus == 3)){
                                $closeStatusCnt++;
                            }
                        }
                        $objectData['routine_rate'] = round($closeStatusCnt * 100 / count($routineAllTask), 2);
                    }
                    $objectData['connect_to'] =   ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.company_id', $user['company_id'])
                        ->where('objects.id', $objectData['id'])
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    $objectData->count_related_object = 0;
                    $objectData->related_objects = '';
                    if ($objectData['is_template']) {
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
                            $objectData->count_related_object = count($relatedObject);
                            $objectData->related_objects = $relatedObject;
                        }
                    }
                   $objectData['object'] =  ObjectItem::where('source_id',$objectData['id'])->first('id');
                    $dateTimeObj = $this->getDateTimeBasedOnTimezone($objectData);
                    if (!empty($objectData['routine']['start_date'])) {
                        $objectData['routine']['start_date'] = $dateTimeObj['start_date'] ?? '';
                        $objectData['start_date'] = $dateTimeObj['start_date'] ?? '';
                    }
                    if (!empty($objectData['routine']['deadline'])) {
                        $objectData['routine']['deadline'] = $dateTimeObj['deadline'] ?? '';
                        $objectData['deadline'] = $dateTimeObj['deadline'] ?? '';
                    }
                    if (!empty($objectData['routine']['start_time'])) {
                        $objectData['routine']['start_time'] = $dateTimeObj['start_time'] ?? '';
                        $objectData['start_time'] = $dateTimeObj['start_time'] ?? '';
                    }

                    $obj_status = $this->getPriorityStatus($objectData['source_id'],$user);
                    if(isset($obj_status) && !empty($obj_status)){
                        $objectData['status'] = $obj_status;
                    }else{
                        $objectData['status'] = $objectData['status'];
                    }
                }
                if (!empty($objectData['question'])) {
                    $objectData['question'] = Question::where('id', $objectData['question'])->first();
                }
                $subObject = [];
                $roleSubObject = [];
                $processingSubObject = [];
                if ($subObjectType) {
                    $subObject = ObjectItem::leftJoin('users', 'objects.added_by', '=', 'users.id')
                        ->leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.company_id', $user['company_id'])
                        ->where('objects.type', $subObjectType)
                        ->where('objects.source_id', $objectData['id'])
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'users.last_name as lastName', 'users.first_name as firstName', 'categories_new.name as categoryName')
                        ->get();
                }
                $objectData = $this->getObjectDetailInfo($objectData, $user);
                if(isset($objectData['my_processing']) && !empty($objectData['my_processing'])){
                    if($objectData['my_processing']['status'] == "Removed" || $objectData['my_processing']['status'] == "Reassigned" ){
                        $objectData['status'] = $objectData['my_processing']['status'];
                    }
                }

                $dateTimeObj = $this->getDateTimeBasedOnTimezone($objectData);
                $objectData->start_date = $dateTimeObj['start_date'] ?? '';
                $objectData->start_time = $dateTimeObj['start_time'] ?? '';
                $objectData->deadline = $dateTimeObj['deadline'] ?? '';
                $objectData->end_time = $dateTimeObj['end_time'] ?? '';
                if($objectData['type'] != 'risk-analysis'){
                    $subObject = [];
                }
                if (!empty($subObject)) {
                    $objectData['totalAttendee'] = 0;
                    $objectData['completeAttendee'] = 0;
                    foreach ($subObject as $key => $item) {
                        $subObject[$key] = $this->getObjectDetailInfo($item, $user);
                        $subTimeObj = $this->getDateTimeBasedOnTimezone($item);
                        $item['status'] = $this->getObjectStatus($item,$subTimeObj);   
                        $objectData->status = $item['status'] ?? '';
                        // $objectTime = TimeManagement::where('object_id', $item->id)->first();
                        // if (!empty($objectTime['start_date'])) {
                        //     $item['start_date'] =   date("Y-m-d", ($objectTime['start_date']));
                        // }
                        // if (!empty($objectTime['deadline'])) {
                        //     $item['deadline'] = date('Y-m-d', $objectTime['deadline']);
                        // }

                        // if (!empty($objectData['routine']['start_time'])) {
                        //     $objectData['routine']['start_time'] = date("H:i A",($objectData['routine']->start_time));
                        //     $objectData['start_time'] =!empty($objectData['routine']->start_time) ?  $objectData['routine']->start_time  :'' ; 
                        // }
                        $objectData['totalAttendee'] += $subObject[$key]['totalAttendee'];
                        $objectData['completeAttendee'] += $subObject[$key]['completeAttendee'];

                        $processingSubObject = array_merge($processingSubObject, $subObject[$key]['processingInfo']);

                        $roleSubObject = array_merge($roleSubObject, $subObject[$key]['role']);
                    }
                    $objectData['subObject'] = $subObject;
                    $objectData['roleSubObject'] = $roleSubObject;
                    $objectData['processingSubObject'] = $processingSubObject;
                }


                if (!empty($objectData['totalAttendee']) && $objectData['totalAttendee'] > 0) {
                    $objectData['rate'] = round($objectData['completeAttendee'] * 100 / $objectData['totalAttendee'], 2);
                } else {
                    $objectData['rate'] = 0;
                }

                $objectData['totalResponsible'] = 0;
                $objectData['completeResponsible'] = 0;
                $objectData['responsible_rate'] = 0;

                if (!empty($objectData['responsible']['processing'])) {
                    // $objectData['totalResponsible'] = count($objectData['responsible']['processing']);
                    $countByStatus =  array_count_values(array_column($objectData['responsible']['processing']->toArray(), 'status'));
                    foreach ($objectData['responsible']['processing'] as $key => $processing) {
                        $userR =  User::where('id', $processing['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                        if (isset($processing->status) && $processing->status == 'completed') {
                            if (empty($this->getResponsibleHistory($objectData['id'], $userR->id))) {
                                $objectData['completeResponsible'] += 1;
                            }
                        }
                        if (empty($this->getResponsibleHistory($objectData['id'], $userR->id))) {
                            $objectData['totalResponsible']  += 1;
                        }
                    }
                    if ($objectData['totalResponsible'] > 0) {
                        $objectData['responsible_rate'] = round($objectData['completeResponsible'] * 100 / $objectData['totalResponsible']);
                    }
                }
                $objectData['total_rate'] = ($objectData['responsible_rate'] + $objectData['rate']) / 2;
                // display status of risk analysis (which has TASK)
                if (!empty($objectData['subObject'])) {
                    if ($objectData['type'] == 'risk-analysis' && count($objectData['subObject']) > 0) {
                        $objectData['status'] = $this->showStatusNewByDate($objectData['subObject'][0]);
                    }
                }

                // RESOURCE object - get number of used time
                if ($objectData['is_template']) {
                    $objectData['number_used_time'] = $this->getObjectNumberOfUsedTime($objectData);
                    if ($user->role_id == 1) {
                        $temp = [];
                        $objectData['industry'] = json_decode($objectData['industry']);
                        foreach ($objectData['industry'] as $industryItem) {
                            $temp[] = $this->getIndustryName($industryItem);
                        }
                        $objectData['industryName'] = $temp;
                    }
                }

                if($objectData['type'] == 'routine') {
                    if(empty($objectData['routine']['deadline'])) {
                        $objectData['deadline'] = "";
                    }
                }

                return $this->responseSuccess($objectData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
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
                if (!empty($responsibleArray)) {
                    foreach ($responsibleArray as $item) {
                        $user = User::where('id', $item)->first();
                        if (!empty($user)) {
                            $responsibleNameArray[] = $user->first_name . ' ' . $user->last_name;
                        }
                    }
                }
            }
        }
        return $responsibleNameArray;
    }




    public function extend_timeline(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $objectData =    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.id', $request->object_id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first();

                $old_deadline = '';
                if (isset($objectData['time']['id'])) {
                    $old_deadline = date("Y-m-d", $objectData['time']['deadline']);
                }
                if (!empty($request->process_id)) {
                    AttendeeProcessing::where('id', $request->process_id)->update([
                        'status' => 'request'
                    ]);
                    $res = ExtendedTimeline::create([
                        'object_id' => $request->object_id ?? '',
                        'process_id' => $request->process_id ?? '',
                        'old_deadline' => $old_deadline ?? '',
                        'deadline_date' => $request->deadline_date ?? null,
                        'deadline_time' => $request->deadline_time ?? null,
                        'reason' => $request->reason ?? '',
                        'requested_by' => $user->id,
                        'requested_by_name' =>  $user->first_name . ' ' . $user->last_name,
                        'type' => 'attendee'
                    ]);
                }
                return $res;
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function update_extended_timeline(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

                $objectData =    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                    ->where('objects.id', $request->object_id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->select('objects.*', 'categories_new.name as categoryName')
                    ->first();
                $status = 0;
                if ($request->status == 'approved') {
                    $status = 1;
                }
                if ($request->status == 'disapproved') {
                    $status = 2;
                }
                $res = ExtendedTimeline::where('id', $request->id)->update([
                    'status' => $status,
                    'extended_by' => $user->id,
                    'extended_by_name' => $user->first_name . ' ' . $user->last_name,
                    'extended_by_reason' => $request->extended_by_reason,
                ]);
                if (!empty($request->process_id) &&  $status == 1) {
                    AttendeeProcessing::where('id', $request->process_id)->update([
                        'status' => 'overdue',
                        'updated_at' => date('y-m-d h:i:s')
                    ]);
                } else if (!empty($request->process_id) &&  $status == 2) {
                    AttendeeProcessing::where('id', $request->process_id)->update([
                        'status' => 'timeline_disapproved',
                        'updated_at' => date('y-m-d h:i:s')
                    ]);
                }


                $res = ExtendedTimeline::where('id', $request->id)->first();
                return $res;
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }



    private function getObjectDetailInfoAllResp($objectData, $userLogin)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];
        $processingArrayResponsible = [];

        $objectData['addedByName'] = $this->getUserName($objectData['added_by'], $users);
        if ($userLogin['id'] == $objectData['added_by']) {
            $roleObject[] = 'creator';
        }
        $employeeName = [];
        $employeeRole = [];
        $employeeArr = [];
        $responsible = [];
        $employeeData = [];


        $assigndate = ''; 
        if (isset($objectData['responsible']['id'])) {
            $objectData['responsible']['addedByName'] = $this->getUserName($objectData['responsible']['added_by'], $users);

            $responsibleArray = json_decode($objectData['responsible']['employee_array']);

            if (in_array($userLogin['id'], $responsibleArray)) {
                $roleObject[] = 'responsible';
            }
            if ($objectData['type'] == 'routine') {
                $objectData['routine'] = Routine::where('id',  $objectData['source_id'])->first();
                if (!empty($objectData['routine']['start_date']) && !empty($objectData['routine']->start_time)) {
                    $assigndate = $objectData['routine']['start_date'] . ' ' . date("H:i:s", ($objectData['routine']->start_time));
                }
            }
            
            if (!empty($objectData['responsible']['processing'])) {
                $objectData['totalResponsible'] = count($objectData['responsible']['processing']);
                foreach ($objectData['responsible']['processing'] as $item) {
                    // $employeeData = [];
                    $user =  User::where('id', $item['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                    if (!empty($user)) {
                        $emp = Employee::where('user_id', $user['id'])->select('department_id')->first();
                        if (!empty($emp)) {
                            $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                            $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                        }
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            if(empty($assigndate)){
                                $assigndate = $this->getOnlyDateTimeBasedOnTimezone($objectData)['start_date'] .' '.$this->getOnlyDateTimeBasedOnTimezone($objectData)['start_time'];
                            }

                            $attendee['id'] =  $user->id;
                            $attendee['employeeName'] = $user['first_name'] . ' ' . $user['last_name'];
                            $attendee['decscription'] = null;
                            $attendee['assign'] = $assigndate;
                            $attendee['revise'] = null;
                            $attendee['status'] = 'New';
                            $attendee['responsible_history'] = null;
                            $attendee['employeeDepartment'] = $this->getDepartmentName($emp->department_id);
                            $attendee['employeeDepartmentID'] = $emp->department_id ?? '';

                            // $det = ([
                            //     'id' =>  $user->id,
                            //     'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                            //     'decscription' => null,
                            //     'assign' => date("Y-m-d h:i a", strtotime($objectData['created_at'])) ?? null,
                            //     'revise' => null,
                            //     'status' => 'New',
                            //     'responsible_history' => null,
                            //     'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                            //     'employeeDepartmentID' => $emp->department_id ?? ''
                            // ]);
                            $employeeRole[] = $user->id;
                            $employeeName[] = $this->getUserName($item['added_by'], $users);
                            // $employeeData = $det;
                            $employeeArr[] = $user->id;
                        } else {
                            $n_data = $this->getResponsibleHistory($objectData['id'], $user->id);
                            $reviseDate = Carbon::make($n_data->created_at,$this->timezone)->format('Y-m-d H:i:s');
                            if(empty($assigndate)){
                                $assigndate = $this->getOnlyDateTimeBasedOnTimezone($objectData)['start_date'] .' '.$this->getOnlyDateTimeBasedOnTimezone($objectData)['start_time'];
                            }
                            // $det = ([
                            //     'id' =>  $user->id,
                            //     'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                            //     'decscription' => $n_data->reason ?? '',
                            //     'assign' =>  date("Y-m-d h:i a", strtotime($objectData['created_at'])) ?? '',
                            //     'revise' =>  date("Y-m-d h:i a", strtotime($n_data->created_at)) ?? '',
                            //     'status' => $n_data->status ?? '',
                            //     'responsible_history' => $this->getResponsibleHistory($objectData['id'], $user->id),
                            //     'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                            //     'employeeDepartmentID' => $emp->department_id ?? ''
                            // ]);
                            $attendee['id'] =  $user->id;
                            $attendee['employeeName'] = $user['first_name'] . ' ' . $user['last_name'];
                            $attendee['decscription'] = $n_data->reason ?? '';
                            $attendee['assign'] = $assigndate ?? '';
                            $attendee['revise'] =  $reviseDate ?? '';
                            $attendee['status'] =  $n_data->status ?? '';
                            $attendee['responsible_history'] = $this->getResponsibleHistory($objectData['id'], $user->id);
                            $attendee['employeeDepartment'] = $this->getDepartmentName($emp->department_id);
                            $attendee['employeeDepartmentID'] = $emp->department_id ?? '';
                            // $employeeData = $det;
                        }
                    }
                    $attendee_history = $this->getResponsibleStatusHistory($objectData['id'], $item['added_by']);
                    $attendee['attendee_history'] = $this->getResponsibleHistory($objectData['id'], $item['added_by']);

                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['old_attendee_addedByName'] =  $this->getUserName($objectData['added_by'], $users);
                    }

                    if (!empty($attendee_history) && $attendee_history->type == 'change') {
                        $attendee['status'] = 'Reassigned';
                    }
                    if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                        $attendee['status'] = 'Removed';
                    }
                    if (empty($attendee_history)) {
                        $start_time = date('H:i:s',$objectData['time']['start_date']) ?? '';
                        $start_date = date('Y-m-d',$objectData['time']['start_date']) ?? '';
                        if(!empty($item['status']) && $item['status'] == 'overdue' || $item['status'] == 'request' || $item['status'] == 'completed_overdue' || $item['status'] == 'disapproved_overdue' || $item['status'] == 'approved_overdue' || $item['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved' || $item['status'] == 'completed' || $item['status'] == 'approved'){
                            $task_status = $item['status'];
                        }else{  
                            $theDay = Carbon::make($start_date . ' ' . $start_time); 
                            if ($theDay->isPast() == true) {
                                $task_status = 'pending';
                            } else if ($theDay->isToday() == true) {
                                $task_status = 'ongoing';
                            } else if ($theDay->isFuture() == true) {
                                $task_status = 'new';
                            }
                        }
                        $attendee['status'] = $task_status; 
                    }
                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    }
                    $attendee['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                    $attendee['attendeeDepartment'] = '';
                    if (!empty($emp)) {
                        $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                        $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                    }
                    $attendee['process_id'] = $item['id'];
                    $attendee['attendee_id'] = $item['added_by'] ?? '';
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
                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['responsibleName'] =  $nameemps;
                        }
                    }

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
                    $attendee['comment'] = $item['comment'] ?? null;
                    $attendee['image'] = null;
                    if (!empty($item['attachment_id'])) {
                        $attachment_image = Attachment::where('id', $item['attachment_id'])->first();
                        $attendee['image'] = !empty($attachment_image) ? $attachment_image->url : '';
                    }
                    // $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment'];
                    $attendee['required_comment'] = $objectData['responsible']['required_comment'];
                    $attendee['required_attachment'] = $objectData['responsible']['required_attachment'];
                    $attendee['extended_timeline'] = $this->getExtendedTimeline($objectData['id'], $item['added_by']);
                    // $attendee['employeeData'] = $employeeData;
                    if ($item['status'] == 'approved' || $item['status'] == 'approved_overdue') {
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            $objectData['completeAttendee'] += 1;
                        }
                    }

                    // if (in_array('responsible', $roleObject) || in_array('creator', $roleObject)) {
                    $processingArrayResponsible[] = $attendee;
                    // } elseif (in_array('attendee', $roleObject)) {
                    //     $processingArrayResponsible[] = $attendee;
                    //     //                        break;
                    // }
                    if ($userLogin['id'] == $item['added_by']) {
                        $objectData['my_processing'] = $attendee;
                    }
                }


                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            }
            // else {
            //     $user =  User::where('id', $objectData['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
            //     $employeeRole[] = $user->id;
            //     $employeeName[] = $this->getUserName($objectData['added_by'], $users);
            //     $objectData['responsible']['employeeName'] = $employeeName;
            //     $objectData['responsible']['employeeRole'] = $employeeRole;
            //     $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            // }
        }
        $objectData['responsible_employeeData'] = $processingArrayResponsible;

        return $objectData;
    }


    private function getObjectDetailInfo($objectData, $userLogin)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];
        $processingArrayResponsible = [];

        $objectData['addedByName'] = $this->getUserName($objectData['added_by'], $users);
        if ($userLogin['id'] == $objectData['added_by']) {
            $roleObject[] = 'creator';
        }
        $employeeName = [];
        $employeeRole = [];
        $employeeArr = [];
        $responsible = [];
        $employeeData = [];
        $attendee = [];


        if (isset($objectData['responsible']['id'])) {
            $objectData['responsible']['addedByName'] = $this->getUserName($objectData['responsible']['added_by'], $users);

            $responsibleArray = json_decode($objectData['responsible']['employee_array']);

            if (in_array($userLogin['id'], $responsibleArray)) {
                $roleObject[] = 'responsible';
            }

            $assigndate = '';
            // if ($objectData['type'] == 'routine') {
            //     $objectData['routine'] = Routine::where('id',  $objectData['source_id'])->first();
            //     if (!empty($objectData['routine']['start_date']) && !empty($objectData['routine']->start_time)) {
            //         $assigndate = $objectData['routine']['start_date'] . ' ' . date("H:i:s", ($objectData['routine']->start_time));
            //     }
            //     if (!empty($objectData['routine']['start_time'])) {
            //         $objectData['routine']['start_time'] = date("H:i:s", ($objectData['routine']->start_time));
            //         $objectData['start_time'] = !empty($objectData['routine']->start_time) ?  $objectData['routine']->start_time  : '';
            //     }
            //     if (!empty($objectData['routine']['deadline'])) {
            //         $objectData['routine']['deadline'] = date("H:i:s", ($objectData['routine']->deadline));
            //         $objectData['deadline'] = !empty($objectData['routine']->deadline) ?  $objectData['routine']->deadline  : '';
            //     }
            // }
          
            
            if (!empty($objectData['responsible']['processing']) && count($objectData['responsible']['processing']) > 0) {
                $objectData['totalResponsible'] = count($objectData['responsible']['processing']);
                foreach ($objectData['responsible']['processing'] as $item) {
                    $user =  User::where('id', $item['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                    if (!empty($user)) {
                        $emp = Employee::where('user_id', $user['id'])->select('department_id')->first();
                        if (!empty($emp)) {
                            $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                            $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                        }
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            if(empty($assigndate)){
                                if(!empty($this->getOnlyDateTimeBasedOnTimezone($objectData))) {
                                    $assigndate = $this->getOnlyDateTimeBasedOnTimezone($objectData)['start_date'] .' '.$this->getOnlyDateTimeBasedOnTimezone($objectData)['start_time'];
                                }
                            }
                            $det = ([
                                'id' =>  $user->id,
                                'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                                'decscription' => null,
                                'assign' => $assigndate,
                                'revise' => null,
                                'status' => 'New',
                                'responsible_history' => null,
                                'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                                'employeeDepartmentID' => $emp->department_id ?? ''
                            ]);
                            $employeeRole[] = $user->id;
                            $employeeName[] = $this->getUserName($item['added_by'], $users);
                            $employeeData[] = $det;
                            $employeeArr[] = $user->id;
                        } else {
                            $n_data = $this->getResponsibleHistory($objectData['id'], $user->id);
                            $det = ([
                                'id' =>  $user->id,
                                'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                                'decscription' => $n_data->reason ?? '',
                                'assign' =>  date("Y-m-d h:i:s", strtotime($objectData['created_at'])) ?? '',
                                'revise' =>  date("Y-m-d h:i:s", strtotime($n_data->created_at)) ?? '',
                                'status' => $n_data->status ?? '',
                                'responsible_history' => $this->getResponsibleHistory($objectData['id'], $user->id),
                                'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                                'employeeDepartmentID' => $emp->department_id ?? ''
                            ]);
                            $employeeData[] = $det;
                        }
                    }
                    $attendee_history = $this->getResponsibleStatusHistory($objectData['id'], $item['added_by']);
                    $attendee['attendee_history'] = $this->getResponsibleHistory($objectData['id'], $item['added_by']);

                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['old_attendee_addedByName'] =  $this->getUserName($objectData['added_by'], $users);
                    }

                    if (!empty($attendee_history) && $attendee_history->type == 'change') {
                        $attendee['status'] = 'Reassigned';
                    }
                    if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                        $attendee['status'] = 'Removed';
                    }
                   
                    if (empty($attendee_history)) {
                        $dateTimeObject = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                        $start_date = $dateTimeObject['start_date'] ?? '';
                        $start_time = $dateTimeObject['start_time'] ?? '';


                        $obj_start_date = $dateTimeObject['start_date']?? '';
                        $obj_start_time = isset($dateTimeObject['start_time']) && !empty($dateTimeObject['start_time']) ? $dateTimeObject['start_time'] : '00:00:00';
                        $obj_end_date = $dateTimeObject['deadline'] ?? '';
                        $obj_end_time = isset($dateTimeObject['end_time']) && !empty($dateTimeObject['end_time']) ? $dateTimeObject['end_time'] : '00:00:00'; 
                        if(!empty($item['status']) && ($item['status'] == 'overdue'|| $item['status'] == 'disapproved_with_extended' || $item['status'] == 'request' || $item['status'] == 'completed_overdue'  || $item['status'] == 'disapproved_overdue' || $item['status'] == 'approved_overdue' || $item['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved' || $item['status'] == 'completed' || $item['status'] == 'approved')){
                            $task_status = $item['status'];
                        }else{  
                            // $theDay = Carbon::make($start_date . ' ' . $start_time); 
                            // if ($theDay->isPast() == true) {
                            //     $task_status = 'new';
                            // } else if ($theDay->isToday() == true) {
                            //     $task_status = 'ongoing';
                            // } else if ($theDay->isFuture() == true) {
                            //     $task_status = 'new';
                            // }

                            if((!empty($obj_start_date) && !empty($obj_start_time)) && (!empty($obj_end_date) && !empty($obj_end_time))) {
                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                $startDay = Carbon::make($obj_start_date . ' ' . $obj_start_time);
                                $endDay = Carbon::make($obj_end_date . ' ' . $obj_end_time);

                                if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                    $task_status = "new";
                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                    $task_status = "new";
                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                    $task_status = "ongoing";
                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($item['status']) && ($item['status'] !== "closed" || $item['status'] !== "3"))) {
                                    $task_status = "overdue";
                                } else {
                                    $task_status = "new";
                                }
                            }
                        }
                        if((isset($userLogin->role_id) && $userLogin->role_id == 4) && ($userLogin->id == $item->added_by)) {
                            $objectData['status'] = $task_status ?? '';
                        }
                        $attendee['status'] = $task_status ?? ''; 
                    }
                 
                    // if($attendee['status'] == 'overdue' || $attendee['status'] == 'approved_overdue' || $attendee['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved'){
                    //     $task_status = $attendee['status'];
                    // }else{  
                    //     $start_time = date('H:i:s',$objectData['time']['start_date']) ?? '';
                    //     $start_date = date('Y-m-d',$objectData['time']['start_date']) ?? '';
                    //     $theDay = Carbon::make($start_date . ' ' . $start_time); 
                    //     if ($theDay->isPast() == true) {
                    //         $task_status = 'pending';
                    //     } else if ($theDay->isToday() == true) {
                    //         $task_status = 'ongoing';
                    //     } else if ($theDay->isFuture() == true) {
                    //         $task_status = 'new';
                    //     }
                    // }
                    // $attendee['status'] = $task_status;

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    }
                    $attendee['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                    $attendee['attendeeDepartment'] = '';
                    if (!empty($emp)) {
                        $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                        $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                    }
                    $attendee['process_id'] = $item['id'];
                    $attendee['attendee_id'] = $item['added_by'] ?? '';
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
                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['responsibleName'] =  $nameemps;
                        }
                    }

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
                    $attendee['comment'] = $item['comment'] ?? null;
                    $attendee['image'] = null;
                    if (!empty($item['attachment_id'])) {
                        $attachment_image = Attachment::where('id', $item['attachment_id'])->first();
                        $attendee['image'] = !empty($attachment_image) ? $attachment_image->url : '';
                    }
                    // $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment'];
                    // $attendee['required_comment'] = $objectData['responsible']['required_comment'];
                    // $attendee['required_attachment'] = $objectData['responsible']['required_attachment'];

                    $employee_array = json_decode($objectData['responsible']['employee_array'], true);
                    $required_comments_array = explode(",", $objectData['responsible']['required_comments_array']);
                    $required_attachments_array = explode(",", $objectData['responsible']['required_attachments_array']);
                    $attendee['required_comment'] = 0;
                    if((isset($employee_array) && isset($required_comments_array)) && count($employee_array) == count($required_comments_array)) {
                        $main_responsible_comments_array = array_combine($employee_array, $required_comments_array);
                        $attendee['required_comment'] = $main_responsible_comments_array[Auth::id()] ?? 0;
                    }
                    $attendee['required_attachment'] = 0;
                    if((isset($employee_array) && isset($required_attachments_array)) && count($employee_array) == count($required_attachments_array)) {
                        $main_responsible_attachments_array = array_combine($employee_array, $required_attachments_array);
                        $attendee['required_attachment'] = $main_responsible_attachments_array[Auth::id()] ?? 0;
                    }
                    // $attendee['required_comments_array'] = $objectData['responsible']['required_comments_array'];
                    // $attendee['required_attachments_array'] = $objectData['responsible']['required_attachments_array'];
                    $attendee['extended_timeline'] = $this->getExtendedTimeline($objectData['id'], $item['added_by']);
                    if ($item['status'] == 'completed') {
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            $objectData['totalResponsible'] += 1;
                        }
                    }

                    if (in_array('responsible', $roleObject) || in_array('creator', $roleObject)) {
                        $processingArrayResponsible[] = $attendee;
                    } elseif (in_array('attendee', $roleObject)) {
                        $processingArrayResponsible[] = $attendee;
                        //                        break;
                    }
                
                    if ($userLogin['id'] == $item['added_by']) {
                        $objectData['my_processing'] = $attendee;
                    }
                }

                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
                
            } else if (!empty($objectData['responsible'])) {
                if (!empty($objectData['responsible']->employee_array)) {
                    $encode = json_decode($objectData['responsible']->employee_array);

                    $objectData['totalResponsible'] = count($encode);

                    if (!empty($encode)) {
                        foreach ($encode as $item) {
                            $user =  User::where('id', $item)->select('id', 'first_name', 'last_name', 'role_id')->first();
                            if (!empty($user)) {
                                $emp = Employee::where('user_id', $user['id'])->select('department_id')->first();
                                if (!empty($emp)) {
                                    $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                                    $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                                }
                                if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                                    $det = ([
                                        'id' =>  $user->id,
                                        'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                                        'decscription' => null,
                                        'assign' => $assigndate,
                                        'revise' => null,
                                        'status' => 'New',
                                        'responsible_history' => null,
                                        'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                                        'employeeDepartmentID' => $emp->department_id ?? ''
                                    ]);
                                    $employeeRole[] = $user->id;
                                    $employeeName[] = $this->getUserName($item, $users);
                                    $employeeData[] = $det;
                                    $employeeArr[] = $user->id;
                                }
                            }
                        }
                    }
                }

                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            } else {
                $user =  User::where('id', $objectData['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                $employeeRole[] = $user->id;
                $employeeName[] = $this->getUserName($objectData['added_by'], $users);
                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            }
        }

        $objectData['employee_array'] = json_decode(json_encode($employeeArr));
        $objectData['responsible_employeeData'] = $employeeData;


        $objectData['processingInfoResponsible'] = $processingArrayResponsible;
        $objectData['processingInfo'] = $processingArrayResponsible;



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
            if (isset($objectData['attendee']['processing']) && !empty($objectData['attendee']['processing'])) {
                // $objectData['totalAttendee'] = count($objectData['attendee']['processing']);
                foreach ($objectData['attendee']['processing'] as $item) {
                  
                    $user =  User::where('id', $item['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();

                    $attendee_history = $this->getAttendeStatusHistory($objectData['id'], $item['added_by']);
                    $attendee['attendee_history'] = $this->getAttendeHistory($objectData['id'], $item['added_by']);


                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['old_attendee_addedByName'] =  $this->getUserName($objectData['added_by'], $users);
                    }

                    if (!empty($attendee_history) && $attendee_history->type == 'change') {
                        $attendee['status'] = 'Reassigned';
                    }
                    if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                        $attendee['status'] = 'Removed';
                    }
                    if (empty($attendee_history)) {
                        $dateTimeObject = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                        $start_date = $dateTimeObject['start_date'] ?? '';
                        $start_time = $dateTimeObject['start_time'] ?? '';
                        // $end_date = $dateTimeObject['deadline'] ?? '';
                        // $end_time = $dateTimeObject['end_time'] ?? '';
                        $obj_start_date = $dateTimeObject['start_date']?? '';
                        $obj_start_time = isset($dateTimeObject['start_time']) && !empty($dateTimeObject['start_time']) ? $dateTimeObject['start_time'] : '00:00:00';
                        $obj_end_date = $dateTimeObject['deadline'] ?? '';
                        $obj_end_time = isset($dateTimeObject['end_time']) && !empty($dateTimeObject['end_time']) ? $dateTimeObject['end_time'] : '00:00:00'; 
                        if(!empty($item['status']) && ($item['status'] == 'overdue'|| $item['status'] == 'disapproved_with_extended' || $item['status'] == 'request'  || $item['status'] == 'completed_overdue'  || $item['status'] == 'disapproved_overdue' || $item['status'] == 'approved_overdue' || $item['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved' || $item['status'] == 'completed' || $item['status'] == 'approved')){
                            $task_status = $item['status'];
                        }else{ 
                            // $theDay = Carbon::make($start_date . ' ' . $start_time); 
                            // if ($theDay->isPast() == true) {
                            //     $task_status = 'new';
                            // } else if ($theDay->isToday() == true) {
                            //     $task_status = 'new';
                            // } else if ($theDay->isFuture() == true) {
                            //     $task_status = 'new';
                            // }
                            if((!empty($obj_start_date) && !empty($obj_start_time)) && (!empty($obj_end_date) && !empty($obj_end_time))) {
                                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                                $startDay = Carbon::make($obj_start_date . ' ' . $obj_start_time);
                                $endDay = Carbon::make($obj_end_date . ' ' . $obj_end_time);

                                if($startDay->format('Y-m-d H:i:s') == $todayDate || $startDay->format('Y-m-d H:i:s') == $tomorrowDate) {
                                    $task_status = "new";
                                } else if($startDay->isToday() == false && $startDay->isPast() == false && $startDay->isFuture() == true) {
                                    $task_status = "new";
                                } else if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                                    $task_status = "ongoing";
                                } else if($todayDate > $endDay->format('Y-m-d H:i:s') && (isset($item['status']) && ($item['status'] !== "closed" || $item['status'] !== "3"))) {
                                    $task_status = "overdue";
                                } else {
                                    $task_status = "new";
                                }
                            }
                        }
                        if((isset($userLogin->role_id) && $userLogin->role_id == 4) && ($userLogin->id == $item->added_by)) {
                            $objectData['status'] = $task_status ?? '';
                        }
                        $attendee['status'] = $task_status ?? '';
                    }

                   
                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    }
                    $attendee['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                    $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                    $attendee['attendeeDepartment'] = '';
                    if (!empty($emp)) {
                        $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                        $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                    }
                    $attendee['process_id'] = $item['id'];
                    $attendee['attendee_id'] = $item['added_by'] ?? '';
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
                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['responsibleName'] =  $nameemps;
                        }
                    }

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
                    $attendee['comment'] = $item['comment'] ?? null;
                    $attendee['image'] = null;
                    if (!empty($item['attachment_id'])) {
                        $attachment_image = Attachment::where('id', $item['attachment_id'])->first();
                        $attendee['image'] = !empty($attachment_image) ? $attachment_image->url : '';
                    }
                    // $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment_id'];
                    // $attendee['required_comment'] = $objectData['attendee']['required_comment'];
                    // $attendee['required_attachment'] = $objectData['attendee']['required_attachment'];
                    $employee_array = json_decode($objectData['attendee']['employee_array'], true);
                    $required_comments_array = explode(",", $objectData['attendee']['required_comments_array']);
                    $required_attachments_array = explode(",", $objectData['attendee']['required_attachments_array']);
                    $attendee['required_comment'] = 0;
                    if((isset($employee_array) && isset($required_comments_array)) && count($employee_array) == count($required_comments_array)) {
                        $main_comments_array = array_combine($employee_array, $required_comments_array);
                        $attendee['required_comment'] = $main_comments_array[Auth::id()] ?? 0;
                    }
                    $attendee['required_attachment'] = 0;
                    if((isset($employee_array) && isset($required_attachments_array)) && count($employee_array) == count($required_attachments_array)) {
                        $main_attachments_array = array_combine($employee_array, $required_attachments_array);
                        $attendee['required_attachment'] = $main_attachments_array[Auth::id()] ?? 0;
                    }
                    // $attendee['required_comments_array'] = $objectData['attendee']['required_comments_array'];
                    // $attendee['required_attachments_array'] = $objectData['attendee']['required_attachments_array'];
                    $attendee['extended_timeline'] = $this->getExtendedTimeline($objectData['id'], $item['added_by']);

                    if ($item['status'] == 'approved' || $item['status'] == 'approved_overdue') {
                        if (empty($attendee['attendee_history'])) {
                            $objectData['completeAttendee'] += 1;
                        }
                    }

                    if (empty($attendee['attendee_history']) || $item['status'] == 'approved' || $item['status'] == 'approved_overdue') {
                        $objectData['totalAttendee']  += 1;
                    }
                    if (in_array('responsible', $roleObject) || in_array('creator', $roleObject)) {
                        $processingArray[] = $attendee;
                    } elseif (in_array('attendee', $roleObject)) {
                        $processingArray[] = $attendee;
                        //                        break;
                    }
                    if ($userLogin['id'] == $item['added_by']) {
                        $objectData['my_processing'] = $attendee;
                    }
                }
            }
            if ($objectData['totalAttendee'] > 0) {
                $objectData['rate'] = round($objectData['completeAttendee'] * 100 / $objectData['totalAttendee'], 2);
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

    private function getObjectTimeInfo($objectData)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];

        // $newdata['addedByName'] = $this->getUserName($objectData['added_by'], $users); 
        $newdata = [];
        if (isset($objectData['time']['id'])) {
            $newdata['start_date'] = date("Y-m-d", $objectData['time']['start_date']);
            if(isset($objectData['time']['start_time']) && !empty($objectData['time']['start_time'])) {
                $newdata['start_time'] = $objectData['time']['start_time'].":00"; 
            } else {
                $newdata['start_time'] = "00:00:00";
            }
            $newdata['deadline'] = date("Y-m-d", $objectData['time']['deadline']);
            if(isset($objectData['time']['end_time']) && !empty($objectData['time']['end_time'])) {
                $newdata['end_time'] = $objectData['time']['end_time'].":00"; 
            } else {
                $newdata['end_time'] = "00:00:00";
            }
        }

        return $newdata;
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
    private function getResponsibleStatusHistory($task_id, $user)
    {

        $attendee = ResponsibleHistory::where('object_id', $task_id)->where('old_responsible_employee', $user)->first();
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
    private function getExtendedTimeline($object, $user)
    {
        $attendee = ExtendedTimeline::where('object_id', $object)->where('requested_by', $user)->latest()->first();
        if (!empty($attendee)) {
            if ($attendee->status == 0) {
                $attendee->status = 'Pending';
            } else if ($attendee->status == 1) {
                $attendee->status = 'Approved';
            } else if ($attendee->status == 2) {
                $attendee->status = 'Disapproved';
            } else {
                $attendee->status = '';
            }
            return $attendee;
        } else {
            return [];
        }
    }

    private function getResponsibleHistory($id, $user)
    {
        $attendee = ResponsibleHistory::where('object_id', $id)->where('old_responsible_employee', $user)->first();
        if (!empty($attendee->old_responsible_department)) {
            $attendee->old_responsible_department =  $this->getDepartmentName($attendee->old_responsible_department);
        }
        if (!empty($attendee->old_responsible_employee)) {
            $attendee->old_responsible_employee =   $this->getUserEmpName($attendee->old_responsible_employee);
        }
        if (!empty($attendee->new_responsible_department)) {
            $attendee->new_responsible_department =  $this->getDepartmentName($attendee->new_responsible_department);
        }
        if (!empty($attendee->new_responsible_employee)) {
            $attendee->new_responsible_employee =  $this->getUserEmpName($attendee->new_responsible_employee);
        }

        if (!empty($attendee)) {
            if ($attendee->type == 'change') {
                $attendee->status = 'Reassigned';
            } else if ($attendee->type == 'remove') {
                $attendee->status = 'Removed';
            } else {
                $attendee->status = '';
            }
            return $attendee;
        } else {
            return '';
        }
    }


    private function getDepartmentName($id): string
    {
        $department = Department::where('id', $id)->first();
        return $department->name ?? 'Company Admin';
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

    private function getUserEmpName($id): string
    {
        $user = User::where('id', $id)->first();
        $f = $user->first_name ?? '';
        $l = $user->last_name ?? '';
        return $f . ' ' . $l;
    }


    private function getObjectDetailInfoSingleResp($objectData, $userLogin, $processing_id)
    {
        $users = User::where('company_id', $objectData['company_id'])->get();
        $roleObject = [];
        $processingArray = [];
        $processingArrayResponsible = [];

        $objectData['addedByName'] = $this->getUserName($objectData['added_by'], $users);
        if ($userLogin['id'] == $objectData['added_by']) {
            $roleObject[] = 'creator';
        }
        $employeeName = [];
        $employeeRole = [];
        $employeeArr = [];
        $responsible = [];
        $employeeData = [];


        if (isset($objectData['responsible']['id'])) {
            $objectData['responsible']['addedByName'] = $this->getUserName($objectData['responsible']['added_by'], $users);

            $responsibleArray = json_decode($objectData['responsible']['employee_array']);

            if (in_array($userLogin['id'], $responsibleArray)) {
                $roleObject[] = 'responsible';
            }

            $assigndate = '';
            if ($objectData['type'] == 'routine') {
                $objectData['routine'] = Routine::where('id',  $objectData['source_id'])->first();
                if (!empty($objectData['routine']['start_date']) && !empty($objectData['routine']->start_time)) {
                    $assigndate = $objectData['routine']['start_date'] . ' ' . date("H:i:s", ($objectData['routine']->start_time));
                }
            }
            if (!empty($objectData['responsible']['processing'])) {
                $objectData['totalResponsible'] = count($objectData['responsible']['processing']);
                foreach ($objectData['responsible']['processing'] as $item) {
                    $user =  User::where('id', $item['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                    if (!empty($user)) {
                        $emp = Employee::where('user_id', $user['id'])->select('department_id')->first();
                        if (!empty($emp)) {
                            $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                            $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                        }
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            if(empty($assigndate)){
                                $assigndate = $this->getDateTimeBasedOnTimezone($objectData)['start_date'] .' '.$this->getDateTimeBasedOnTimezone($objectData)['start_time'];
                            }
                            $det = ([
                                'id' =>  $user->id,
                                'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                                'decscription' => null,
                                'assign' => $assigndate,
                                'revise' => null,
                                'status' => 'New',
                                'responsible_history' => null,
                                'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                                'employeeDepartmentID' => $emp->department_id ?? ''
                            ]);
                            $employeeRole[] = $user->id;
                            $employeeName[] = $this->getUserName($item['added_by'], $users);
                            $employeeData[] = $det;
                            $employeeArr[] = $user->id;
                        } else {
                            $n_data = $this->getResponsibleHistory($objectData['id'], $user->id);
                            $det = ([
                                'id' =>  $user->id,
                                'employeeName' => $user['first_name'] . ' ' . $user['last_name'],
                                'decscription' => $n_data->reason ?? '',
                                'assign' =>  date("Y-m-d h:i:s", strtotime($objectData['created_at'])) ?? '',
                                'revise' =>  date("Y-m-d h:i:s", strtotime($n_data->created_at)) ?? '',
                                'status' => $n_data->status ?? '',
                                'responsible_history' => $this->getResponsibleHistory($objectData['id'], $user->id),
                                'employeeDepartment' => $this->getDepartmentName($emp->department_id),
                                'employeeDepartmentID' => $emp->department_id ?? ''
                            ]);
                            $employeeData[] = $det;
                        }
                    }
                    $attendee_history = $this->getResponsibleStatusHistory($objectData['id'], $item['added_by']);
                    $attendee['attendee_history'] = $this->getResponsibleHistory($objectData['id'], $item['added_by']);

                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['old_attendee_addedByName'] =  $this->getUserName($objectData['added_by'], $users);
                    }

                    if (!empty($attendee_history) && $attendee_history->type == 'change') {
                        $attendee['status'] = 'Reassigned';
                    }
                    if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                        $attendee['status'] = 'Removed';
                    }
                    if (empty($attendee_history)) {
                        $start_time = date('H:i:s',$objectData['time']['start_date']) ?? '';
                        $start_date = date('Y-m-d',$objectData['time']['start_date']) ?? '';
                        if(!empty($item['status']) && $item['status'] == 'overdue'|| $item['status'] == 'request'  || $item['status'] == 'completed_overdue'  || $item['status'] == 'disapproved_overdue' || $item['status'] == 'approved_overdue' || $item['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved' || $item['status'] == 'completed' || $item['status'] == 'approved'){
                            $task_status = $item['status'];
                        }else{  
                            $theDay = Carbon::make($start_date . ' ' . $start_time); 
                            if ($theDay->isPast() == true) {
                                $task_status = 'pending';
                            } else if ($theDay->isToday() == true) {
                                $task_status = 'ongoing';
                            } else if ($theDay->isFuture() == true) {
                                $task_status = 'new';
                            }
                        }
                        $attendee['status'] = $task_status; 
                    }

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    if (!empty($attendee['attendee_history'])) {
                        $attendee['attendee_history']['time_info'] = $this->getDateTimeBasedOnTimezone($objectData);
                    }
                    $attendee['time_info'] = $this->getDateTimeBasedOnTimezone($objectData);
                    $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                    $attendee['attendeeDepartment'] = '';
                    if (!empty($emp)) {
                        $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                        $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                    }
                    $attendee['process_id'] = $item['id'];
                    $attendee['attendee_id'] = $item['added_by'] ?? '';
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
                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['responsibleName'] =  $nameemps;
                        }
                    }

                    $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                    // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
                    $attendee['comment'] = $item['comment'] ?? null;
                    $attendee['image'] = null;
                    if (!empty($item['attachment_id'])) {
                        $attachment_image = Attachment::where('id', $item['attachment_id'])->first();
                        $attendee['image'] = !empty($attachment_image) ? $attachment_image->url : '';
                    }
                    // $attendee['status'] = $item['status'];
                    $attendee['responsible_comment'] = $item['responsible_comment'];
                    $attendee['responsible_attachment'] = $item['responsible_attachment'];
                    $attendee['required_comment'] = $objectData['responsible']['required_comment'];
                    $attendee['required_attachment'] = $objectData['responsible']['required_attachment'];
                    $attendee['extended_timeline'] = $this->getExtendedTimeline($objectData['id'], $item['added_by']);
                    if ($item['status'] == 'closed') {
                        if (empty($this->getResponsibleHistory($objectData['id'], $user->id))) {
                            $objectData['completeAttendee'] += 1;
                        }
                    }

                    if (in_array('responsible', $roleObject) || in_array('creator', $roleObject)) {
                        $processingArrayResponsible[] = $attendee;
                    } elseif (in_array('attendee', $roleObject)) {
                        $processingArrayResponsible[] = $attendee;
                        //                        break;
                    }
                    if ($userLogin['id'] == $item['added_by']) {
                        $objectData['my_processing'] = $attendee;
                    }
                }

                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            } else {
                $user =  User::where('id', $objectData['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                $employeeRole[] = $user->id;
                $employeeName[] = $this->getUserName($objectData['added_by'], $users);
                $objectData['responsible']['employeeName'] = $employeeName;
                $objectData['responsible']['employeeRole'] = $employeeRole;
                $objectData['responsible']['employee_array'] = json_encode($employeeArr);
            }
        }


        $objectData['responsible_employeeData'] = $employeeData;


        $objectData['processingInfoResponsible'] = $processingArrayResponsible;
        $objectData['processingInfo'] = $processingArrayResponsible;

        return $objectData;
    }


    private function getObjectDetailInfoSingle($objectData, $userLogin, $processing_id)
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

            if (!empty($responsibleArray)) {
                foreach ($responsibleArray as $item) {
                    $user =  User::where('id', $item)->select('id', 'first_name', 'last_name', 'role_id')->first();
                    if (!empty($user)) {
                        $employeeRole[] = $user->id;
                        $employeeName[] = $this->getUserName($item, $users);
                    }
                }
            } else {
                $user =  User::where('id', $objectData['added_by'])->select('id', 'first_name', 'last_name', 'role_id')->first();
                $employeeRole[] = $user->id;
                $employeeName[] = $this->getUserName($objectData['added_by'], $users);
            }
            $objectData['responsible']['employeeName'] = $employeeName;
            $objectData['responsible']['employeeRole'] = $employeeRole;
            $objectData['responsible']['prcess'] = $employeeRole;
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
                // $objectData['totalAttendee'] = count($objectData['attendee']['processing']);
                foreach ($objectData['attendee']['processing'] as $item) {
                    if ((int)$item['id'] == (int)$processing_id) {
                        $attendee_history = $this->getAttendeStatusHistory($objectData['id'], $item['added_by']);

                        $attendee['attendee_history'] = $this->getAttendeHistory($objectData['id'], $item['added_by']);

                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['old_attendee_addedByName'] =  $this->getUserName($objectData['added_by'], $users);
                        }

                        if (!empty($attendee_history) && $attendee_history->type == 'change') {
                            $attendee['status'] = 'Reassigned';
                        }
                        if (!empty($attendee_history) && $attendee_history->type == 'remove') {
                            $attendee['status'] = 'Removed';
                        }
                        if (empty($attendee_history)) {
                            $start_time = date('H:i:s',$objectData['time']['start_date']) ?? '';
                            $start_date = date('Y-m-d',$objectData['time']['start_date']) ?? '';
                            if(!empty($item['status']) && $item['status'] == 'overdue'|| $item['status'] == 'disapproved_with_extended' || $item['status'] == 'request'  || $item['status'] == 'completed_overdue'  || $item['status'] == 'disapproved_overdue' || $item['status'] == 'approved_overdue' || $item['status'] == 'disapproved' || $item['status'] == 'timeline_disapproved' || $item['status'] == 'completed' || $item['status'] == 'approved'){
                                $task_status = $item['status'];
                            }else{  
                                $theDay = Carbon::make($start_date . ' ' . $start_time); 
                                if ($theDay->isPast() == true) {
                                    $task_status = 'pending';
                                } else if ($theDay->isToday() == true) {
                                    $task_status = 'ongoing';
                                } else if ($theDay->isFuture() == true) {
                                    $task_status = 'new';
                                }
                            }
                            $attendee['status'] = $task_status; 
                        }
                        $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                        if (!empty($attendee['attendee_history'])) {
                            $attendee['attendee_history']['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                        }
                        $attendee['time_info'] = $this->getOnlyDateTimeBasedOnTimezone($objectData);
                        $emp = Employee::where('user_id', $item['added_by'])->select('department_id')->first();
                        $attendee['attendeeDepartment'] = '';
                        if (!empty($emp)) {
                            $attendee['attendeeDepartment'] =  $this->getDepartmentName($emp->department_id);
                            $attendee['attendeeDepartmentId'] = $emp->department_id ?? '';
                        }
                        $attendee['attendee_id'] = $item['added_by'] ?? '';
                        $attendee['process_id'] = $item['id'];
                        // $attendee['user_id'] = $item['added_by'];
                        // $attendee['responsible_id'] = $item['responsible_id'];
                        // $attendee['attendeeName'] = $this->getUserName($item['added_by'], $users);
                        // $attendee['responsibleName'] = $this->getUserName($item['responsible_id'], $users);
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
                            if (!empty($attendee['attendee_history'])) {
                                $attendee['attendee_history']['responsibleName'] =  $nameemps;
                            }
                        }
                        $attendee['comment'] = $item['comment'];
                        // $attendee['image'] = $item['attachment_id'];
                        if (!empty($item['attachment_id'])) {
                            $attachment_image = Attachment::where('id', $item['attachment_id'])->first();
                            $attendee['image'] = !empty($attachment_image) ? $attachment_image->url : '';
                        }
                        // $attendee['status'] = $item['status'];
                        $attendee['responsible_comment'] = $item['responsible_comment'];
                        $attendee['responsible_attachment'] = $item['responsible_attachment_id'];
                        $attendee['required_comment'] = $objectData['attendee']['required_comment'];
                        $attendee['required_attachment'] = $objectData['attendee']['required_attachment'];
                        if ($item['status'] == 'approved' || $item['status'] == 'approved_overdue') {
                            if (empty($attendee['attendee_history'])) {
                                $objectData['completeAttendee'] += 1;
                            }
                        }
                        if (empty($attendee['attendee_history'])) {
                            $objectData['totalAttendee'] += 1;
                        }

                        $processingArray[] = $attendee;
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



        return $objectData;
    }

    private function getUserName($id, $usersList): string
    {
        $username = '';
        $key = array_search($id, array_column($usersList->toArray(), 'id'));

        if ($key > -1) {
            $user =  $usersList[$key];
            $username = ' ' . $user['first_name'] . ' ' . $user['last_name'];
        }

        return $username;
    }

    private function getIndustryName($id): string
    {
        $industryName = '';
        $list = Industry::get();
        $key = array_search($id, array_column($list->toArray(), 'id'));

        if ($key > -1) {
            $industry =  $list[$key];
            $industryName = ' ' . $industry['name'];
        }

        return $industryName;
    }

    private function getObjectNumberOfUsedTime($object)
    {
        $item = ObjectOption::where('object_id', $object['id'])->first();
        if (empty($item)) {
            return null;
        }
        return $item['number_used_time'];
    }

    private function getRiskElementDetail($object)
    { // popup review Risk element
        //        $item = ObjectOption::where('object_id', $object['id'])->first();
        //        if (empty($item)) {
        //            return null;
        //        }
        //        $object['number_used_time'] = $item['number_used_time'];

        $object['number_used_time'] = $this->getObjectNumberOfUsedTime($object);
        if (!empty($item['image_id'])) {
            $object['hasImage'] = true;
            $image = Attachment::where('id', $item['image_id'])
                ->where('object_id', $object['id'])->first();
            $object['imageUrl'] = $image['url'];
        } else {
            $object['hasImage'] = false;
        }
        if (!empty($item['risk_analysis_array'])) {
            $listRiskAnalysis = json_decode($item['risk_analysis_array']);
            $object['riskAnalysisArray'] = ObjectItem::leftJoin('users', 'objects.added_by', '=', 'users.id')
                ->leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                ->whereIn('objects.id', $listRiskAnalysis)
                ->with(['attendee', 'responsible', 'time'])
                ->select('objects.*', 'users.last_name as lastName', 'users.first_name as firstName', 'categories_new.name as categoryName')
                ->get();

            $users = User::where('company_id', $object['company_id'])->get();
            foreach ($object['riskAnalysisArray'] as $temp) {
                $responsibleNameArray = [];
                if (!empty($temp['responsible']['employee_array'])) {
                    $responsibleArray = json_decode($temp['responsible']['employee_array']);
                    foreach ($responsibleArray as $tempItem) {
                        $responsibleNameArray[] = $this->getUserName($tempItem, $users);
                    }
                }
                $subObject = ObjectItem::where('source', 'risk-analysis')
                    ->where('source_id', $temp['id'])->first();
                if (!empty($subObject)) {
                    $temp['status'] = $this->showStatusNewByDate($subObject);
                }
                $temp['responsibleName'] = $responsibleNameArray;
            }
        } else {
            $object['riskAnalysisArray'] = [];
        }
        return $object;
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
            $input = $request->all();

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($input['type'] == 'routine') {
                    $objectData = ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                        ->where('objects.id', $id)
                        ->where('objects.is_valid', 1)
                        ->with(['attendee', 'responsible', 'time'])
                        ->select('objects.*', 'categories_new.name as categoryName')
                        ->first();
                    if (!empty($objectData)) {
                        $routineData = Routine::where('id',  $objectData['source_id'])->first();
                        $pushNotificationUpdateArray = [];
                        $pushNotificationResponsibleArray = [];
                        $pushNotificationAssignedArray = [];
                        $inputRoutine = $this->getRoutineData($input);
                        $inputRoutine['added_by'] = $objectData['added_by'];
                        $inputRoutine['company_id'] = $objectData['company_id'];

                        
                        // Handle to save Reminder/ start date - due date
                        if (!empty($input['start_time'])) {
                            $inputRoutine['start_time'] = strtotime($input['start_time']);
                        } else {
                            $inputRoutine['start_time'] = strtotime("today");
                        }
                        $inputRoutine['is_activated'] = $input['is_activated'];
       
                        if (!empty($input['start_time'])) {
                            $inputRoutine['start_time'] =  strtotime($input['start_time']);
                        } else {
                            // $inputRoutine['start_time'] = strtotime("today");
                        }
                        if (!$input['is_activated']) {
                            $inputRoutine['deadline'] = null;
                            $inputRoutine['recurring'] = 'indefinite';
                        } else {
                            if (!empty($input['deadline'])) {
                                $inputRoutine['deadline'] = strtotime($input['deadline']);
                            } else {
                                $inputRoutine['deadline'] = null;
                                // $inputRoutine['recurring'] = 'indefinite';
                            }
                            $inputRoutine['recurring'] = !empty($input['recurring']) ? ucfirst($input['recurring']) : '';
                        } 
                        // if (!$input['is_activated']) {
                        //     $inputRoutine['deadline'] = null;
                        //     $inputRoutine['recurring'] = 'indefinite';
                        // } else {
                        //     if (!empty($input['deadline'])) {
                        //         $inputRoutine['deadline'] = strtotime($input['deadline']);
                        //         $inputRoutine['recurring'] = $input['recurring'];
                        //     } else {
                        //         $inputRoutine['deadline'] = null;
                        //         $inputRoutine['recurring'] = 'indefinite';
                        //     }
                        // }
                      
                        $rules = Routine::$updateRules;
                        $validator = Validator::make($inputRoutine, $rules);
                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($validator);
                            return $this->responseError($errors, 400);
                        }
                        if (!empty($inputRoutine['responsible_id'])) {
                            if ($inputRoutine['responsible_id'] != $objectData['responsible_id']) {
                                array_push($pushNotificationResponsibleArray, $inputRoutine['responsible_id']);
                            } else {
                                array_push($pushNotificationUpdateArray, $inputRoutine['responsible_id']);
                            }
                        }

                        if (!$objectData['attending_emps']) {
                            $newAttendingEmpsArray = $inputRoutine['attendingEmpsArray'];
                        } else {
                            $newAttendingEmpsArray = array_diff($inputRoutine['attendingEmpsArray'], json_decode($objectData['attending_emps']));
                        }
                        if (!empty($newAttendingEmpsArray)) {
                            $pushNotificationAssignedArray = $newAttendingEmpsArray;

                            $oldAttendingEmpsArray = array_diff($inputRoutine['attendingEmpsArray'], $newAttendingEmpsArray);
                            if (!empty($oldAttendingEmpsArray)) {
                                $pushNotificationUpdateArray = array_merge($pushNotificationUpdateArray, $oldAttendingEmpsArray);
                            }
                        }
                        $routineData->update($inputRoutine);

                        $schedule = Schedule::where('routine_id',  $objectData['source_id'])->first();
                        if ($schedule) {
                            if (!empty($input['start_time'])) {
                                $inputRoutine['start_time'] = ($input['start_time']);
                                $inputRoutine['start_time_pre'] = ($input['start_time']);
                            }
                            if (!empty($input['deadline'])) {
                                $inputRoutine['deadline'] = $input['deadline'];
                                $inputRoutine['deadline_pre'] = $input['deadline'];
                            }
                            if (!empty($input['start_date'])) {
                                $inputRoutine['start_date'] = ($input['start_date']);
                                $inputRoutine['start_date_pre'] = ($input['start_date']);
                            }
                            $scheduleInput = json_decode($schedule->schedule_data,true);
                            $updatedScheduleArray = array_replace($scheduleInput, $inputRoutine);
                            $updatedScheduleArray = json_encode($updatedScheduleArray);
                            $schedule->update(['schedule_data' => $updatedScheduleArray]);
                        }
                    }
                }

                if (!$input['requestEdit']) return null;
               
                unset($input['topics']);
                return $objectData = $this->updateObject($id, $input, $user);

                //                if ($objectData['type'] == 'sub-goal') {
                //                    ObjectItem::where('source', $objectData['type'])
                //                        ->where('source_id', $objectData['id'])
                //                        ->delete();
                //
                //                    $item = $objectData;
                //                    $item['type'] = 'task';
                //                    $item['source'] = $objectData['type'];
                //                    $item['source_id'] = $objectData['id'];
                //
                //                    $this->createObject($item, $user);
                //                }

                //                if ($user['role_id'] == 1) {
                //                    $this->pushNotificationToAllCompanies('Category', $categoryData['id'], $categoryData['name'],'update', $categoryData['type']);
                //                }
                //                if (!empty($input['subGoal']) || !empty($input['activities'])) {
                //                    $subObject = [];
                //                    $subObjectArray = [];
                //                    if ($objectData['type'] == 'goal') {
                //                        $subObjectArray = $input['subGoal'];
                //                    } elseif ($objectData['type'] == 'instruction') {
                //                        $subObjectArray = $input['activities'];
                //                    }
                //
                //                    if (!empty($subObjectArray)) {
                //                        ObjectItem::where('source', $objectData['type'])
                //                            ->where('source_id', $objectData['id'])
                //                            ->delete();
                //
                //                        foreach ($subObjectArray as $item) {
                //                            $item['category_id'] = $objectData['category_id'];
                //                            $item['source'] = $objectData['type'];
                //                            $item['source_id'] = $objectData['id'];
                //                            $item['is_template'] = $objectData['is_template'];
                //
                //                            $newSubObject = $this->createObject($item, $user);
                //
                //                            $newSubObject['task'] = '';
                //
                //                            if ($objectData['type'] == 'goal') {
                //                                $item['type'] = 'task';
                //                                $item['source'] = $newSubObject['type'];
                //                                $item['source_id'] = $newSubObject['id'];
                //
                //                                $newSubObject['task'] = $this->createObject($item, $user);
                //                            }
                //
                //                            $subObject[] = $newSubObject;
                //                        }
                //                    }
                //                    $objectData['subObject'] = $subObject;
                //                }

                return $this->responseSuccess($objectData, 201);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function removearray($array, $value)
    {
        $arr = array_diff($array, array($value));
        return ($arr);
    }

    public function update_responsible(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

                $responsible = Responsible::where('object_id', $id)->first();
                $objects = ObjectItem::where('id', $id)->first();

                $decoded = !empty($responsible->department_array) ? json_decode($responsible->department_array) : '';

                $departs = [];
                $emps = [];
                if (is_array($decoded)) {
                    $departs = $decoded;
                }
                if (!empty($request->new_responsible_department)) {
                    array_push($departs, (int)$request->new_responsible_department);
                }
                $departs = array_unique($departs);
                if (!empty($request->old_responsible_department)) {
                    $newdeps = [];
                    if (!empty($departs)) {
                        foreach ($departs as $e) {
                            if ($e != $request->old_responsible_department) {
                                $newdeps[] = $e;
                            }
                        }
                        $departs = array_unique($newdeps);
                    }
                }


                $decodedemps = !empty($responsible->employee_array) ? json_decode($responsible->employee_array) : '';
                if (is_array($decodedemps)) {
                    $emps =  $decodedemps;
                }
                if (!empty($request->old_responsible_employee)) {
                    $newdeps = [];
                    if (!empty($decodedemps)) {
                        foreach ($decodedemps as $e) {
                            if ($e != $request->old_responsible_employee) {
                                $newdeps[] = $e;
                            }
                        }

                        
                        $emps = array_unique($newdeps);
                    }
                }

                if (!empty($request->new_responsible_employee)) {
                    array_push($emps, (int)$request->new_responsible_employee);
                    if (!empty($request->new_responsible_employee)) {
                        array_push($emps, (int)$request->new_responsible_employee);
                        $new_emp = $request->new_responsible_employee ?? '';
                        $array  = array_map('intval', str_split($new_emp));
                        $this->requestPushNotification($user['id'], $user['company_id'], $array, 'notification', $objects, 'responsible');
                    }
                }


                $emps = array_unique($emps);

                if (!empty($request->old_responsible_employee)) {
                    // ResponsibleProcessing::where('attendee_id', $responsible->id)->where('added_by', $request->old_responsible_employee)->delete();
                    // $newemps = [];
                    // if(!empty($emps)){
                    //     foreach($emps as $e){  
                    //         if($e != $request->old_responsible_employee){
                    //             $newemps[] = $e; 
                    //         }
                    //     } 
                    // }
                    // $emps = array_unique($newemps); 
                }

                $objects = DB::table('objects')->where('id', $id)->first();
                if (!empty($objects)) {
                    if(isset($emps) && !empty($emps)) {
                        $responsible_required_comments = $responsible->required_comments_array;
                        $responsible_required_attachments = $responsible->required_attachments_array;

                        $responsible_required_comments_array = [];
                        if(isset($responsible_required_comments) && !empty($responsible_required_comments)) {
                            $responsible_required_comments_array = explode(",", $responsible_required_comments);
                            $transfer_feedback = isset($request->transfer_feedback) && $request->transfer_feedback == "true" ? "1" : "0";
                            array_push($responsible_required_comments_array, $transfer_feedback);
                        }

                        $responsible_required_attachments_array = [];
                        if(isset($responsible_required_attachments) && !empty($responsible_required_attachments)) {
                            $responsible_required_attachments_array = explode(",", $responsible_required_attachments);
                            $transfer_attachment = isset($request->transfer_attachment) && $request->transfer_attachment == "true" ? "1" : "0";
                            array_push($responsible_required_attachments_array, $transfer_attachment);
                        }
                        unset($responsible_required_attachments_array[array_search($request->old_responsible_employee,$decodedemps)]);
                        unset($responsible_required_comments_array[array_search($request->old_responsible_employee,$decodedemps)]);

                        $required_comment = implode(',', $responsible_required_comments_array);
                        $required_attachment = implode(',', $responsible_required_attachments_array);
                    }

                    Responsible::where('object_id', $id)->update([
                        'employee_array' => !empty($emps) ? json_encode($emps) : null,
                        'department_array' => !empty($departs) ? json_encode($departs) : null,
                        'required_comment' => $request->transfer_feedback ?? 0,
                        'required_attachment' => $request->transfer_attachment ?? 0,
                        'required_comments_array' => $required_comment ?? null,
                        'required_attachments_array' => $required_attachment ?? null
                    ]);

                    $res = ResponsibleHistory::create([
                        'object_id' => $id,
                        'type' => $request->type ?? '',
                        'reason' => $request->reason ?? '',
                        'old_responsible_department' => $request->old_responsible_department ?? '',
                        'old_responsible_employee' => $request->old_responsible_employee ?? '',
                        'new_responsible_department' => $request->new_attendee_department ?? '',
                        'new_responsible_employee' => $request->new_responsible_employee ?? '',
                        'transfer_information' => $request->transfer_information ?? '',
                        'transfer_feedback' => $request->transfer_feedback ?? '',
                        'transfer_attachment' => $request->transfer_attachment ?? '',
                        'updated_by' => $user->id,
                    ]);
                }
                $deta = Responsible::where('object_id', $id)->first();

                $r = $this->createObjectResponsibleProcessing($deta, $user);
                $data = ResponsibleHistory::where('id', $res->id)->first();
                return ($data);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }


    public function update_attendee(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

                $objects = ObjectItem::where('id', $id)->first();


                if (!empty($objects)) {
                    $attendee = Attendee::where('object_id', $id)->first();

                    if (!empty($attendee)) {

                        $decoded = !empty($attendee->department_array) ? json_decode($attendee->department_array) : '';
                        $departs = [];
                        $emps = [];
                        if (is_array($decoded)) {
                            $departs = $decoded;
                        }
                        if (!empty($request->new_attendee_department)) {
                            array_push($departs, (int)$request->new_attendee_department);
                        }
                        $departs = array_unique($departs);

                        if (!empty($request->old_attendee_department)) {
                            $newdeps = [];
                            if (!empty($departs)) {
                                foreach ($departs as $e) {
                                    if ($e != $request->old_attendee_department) {
                                        $newdeps[] = $e;
                                    }
                                }
                                $departs = array_unique($newdeps);
                            }
                        }




                        $decodedemps = !empty($attendee->employee_array) ? json_decode($attendee->employee_array) : '';

                        if (is_array($decodedemps)) {
                            $emps =  $decodedemps;
                        }
                        if (!empty($request->new_attendee_employee)) {
                            array_push($emps, (int)$request->new_attendee_employee);
                            $new_emp = $request->new_attendee_employee ?? '';
                            $array  = array_map('intval', str_split($new_emp));
                            $this->requestPushNotification($user['id'], $user['company_id'], $array, 'notification', $objects, 'attendee');
                        }
                        $emps = array_unique($emps);

                        if (!empty($request->old_attendee_employee)) {
                            // AttendeeProcessing::where('attendee_id', $attendee->id)->where('added_by', $request->old_attendee_employee)->delete();
                            // $newemps = [];
                            // if(!empty($emps)){
                            //     foreach($emps as $e){   
                            //         if($e != $request->old_attendee_employee){
                            //             $newemps[] = $e; 
                            //         }
                            //     } 
                            // }
                            // $emps = array_unique($newemps);
                        }

                        // return response()->json([
                        //     'emps'=>$emps,
                        //     'departs'=>$departs,
                        //     'new_attendee_employee'=>$request->new_attendee_employee,
                        // ]);
                        // $emps = array_unique($emps);
                        $object =   ObjectItem::leftJoin('categories_new', 'objects.category_id', '=', 'categories_new.id')
                            ->where('objects.id', $id)
                            ->with(['attendee', 'responsible', 'time'])
                            ->select('objects.*', 'categories_new.name as categoryName')
                            ->first();

                        AttendeeHistory::where('object_id',$id)->where('old_attendee_department',$request->new_attendee_department)
                        ->where('old_attendee_employee',$request->new_attendee_employee)->delete();

                        AttendeeHistory::create([
                            'object_id' => $id,
                            'type' => $request->type ?? '',
                            'reason' => $request->reason ?? '',
                            'old_attendee_department' => $request->old_attendee_department ?? '',
                            'old_attendee_employee' => $request->old_attendee_employee ?? '',
                            'new_attendee_department' => $request->new_attendee_department ?? '',
                            'new_attendee_employee' => $request->new_attendee_employee ?? '',
                            'transfer_information' => $request->transfer_information ?? '',
                            'transfer_feedback' => $request->transfer_feedback ?? '',
                            'transfer_attachment' => $request->transfer_attachment ?? '',
                            'updated_by' => $user->id,
                        ]);

                        if(isset($emps) && !empty($emps)) {
                            $attendee_required_comments = $attendee->required_comments_array;
                            $attendee_required_attachments = $attendee->required_attachments_array;

                            $attendee_required_comments_array = [];
                            if(isset($attendee_required_comments) && !empty($attendee_required_comments)) {
                                $attendee_required_comments_array = explode(",", $attendee_required_comments);
                                $transfer_feedback = isset($request->transfer_feedback) && $request->transfer_feedback == "true" ? 1 : 0;
                                array_push($attendee_required_comments_array, $transfer_feedback);
                            }

                            $attendee_required_attachments_array = [];
                            if(isset($attendee_required_attachments) && !empty($attendee_required_attachments)) {
                                $attendee_required_attachments_array = explode(",", $attendee_required_attachments);
                                $transfer_attachment = isset($request->transfer_attachment) && $request->transfer_attachment == "true" ? 1 : 0;
                                array_push($attendee_required_attachments_array, $transfer_attachment);
                            }

                            $required_comment = implode(',', $attendee_required_comments_array);
                            $required_attachment = implode(',', $attendee_required_attachments_array);
                        }

                        Attendee::where('object_id', $id)->update([
                            'department_array' => !empty($departs) ? json_encode($departs) : '',
                            'employee_array' => !empty($emps) ? json_encode($emps) : '',
                            'required_comment' => $request->transfer_feedback ?? '',
                            'required_attachment' => $request->transfer_attachment ?? '',
                            'required_comments_array' => $required_comment ?? null,
                            'required_attachments_array' => $required_attachment ?? null
                        ]);
                        $deta = Attendee::where('object_id', $id)->first();
                        $routine = Routine::where('id', $object['source_id'])->first();

                        $this->createUpdateObjectAttendeeProcessing($deta, $user, false, $routine, $objects);

                        //     $attendee = $this->getObjectDetailInfo($data, $user);
                        return $this->responseSuccess($deta, 201);
                    }
                    return $this->responseException('Attendee not found!.', 404);
                }
                return $this->responseException('Object not found!.', 404);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function processObject(Request $request, $objectID)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $newStatus = $request->newStatus;
                $processID = $request->processID;
                $statusArray = ['pending', 'new', 'in-progress', 'done', 'verify', 'reopened', 'closed', 'cancelled'];
                if (!$processID || !$newStatus || in_array($newStatus, $statusArray)) $this->responseException('Invalid data', 404);

                $objectData = ObjectItem::where('objects.company_id', $user['company_id'])
                    ->where('objects.id', $objectID)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->first();


                if (empty($objectData)) {
                    return $this->responseException('Not found object', 404);
                }


                $processingInfo = AttendeeProcessing::find($processID);

                if (empty($processingInfo)) {
                    return $this->responseException('Not found object', 404);
                }

                if ($processingInfo['status'] == 'closed' || $processingInfo['status'] == $request->newStatus) {
                    return $this->responseException('This action is denied', 404);
                }
                $checkResponsible = false;
                if (!empty($objectData['responsible']) && $objectData['responsible']['employee_array']) {
                    $responsibleArray = json_decode($objectData['responsible']['employee_array']);
                    if (in_array($user['id'], $responsibleArray)) {
                        $checkResponsible = true;
                    }
                }

                if (!empty($objectData['time'])) {
                    $startDate = date("Y-m-d H:i:s", $objectData['time']['start_date']);
                    $today = date("Y-m-d H:i:s");
                    if ($newStatus != 'in-progress' && $startDate > $today) {
                        // return $this->responseException('Task cannot be approved on same day!', 404);
                    }
                }


                if ($newStatus == 'done') {
                    if ($processingInfo['added_by'] == $user['id']) {
                        $processingInfo->update(['status' => $newStatus, 'comment' => $request->comment]);
                        //                        if ($newStatus == 'done') {
                        //                            $this->requestPushNotification($user['id'], $user['company_id'], [$processingInfo['added_by']], 'notification', $objectData['type'], $objectData['id'], $objectData['name'], 'attendee_done');
                        //                        }

                        return $this->responseSuccess($processingInfo, 201);
                    }
                    return $this->responseException('This action is not allowed', 404);
                } elseif ($newStatus == 'verify') {
                    $status = 'approved';
                    // if (!empty($request->processID) && $processingInfo->added_by) {
                    //     $resultTimeline = ExtendedTimeline::where('process_id', $request->processID)->where('requested_by', $processingInfo->added_by)->first();
                    //     if (!empty($resultTimeline)) {
                    //         $status = 'approved_overdue';
                    //     }
                    // }
                        if($processingInfo['status'] == 'completed_overdue') {
                            $status = "approved_overdue";
                        }
                
                    // if ($checkResponsible) {
                    $processingInfo->update(['status' => $status, 'responsible_id' => $user['id'], 'responsible_comment' => $request->responsible_comment]);
                    return $this->responseSuccess($processingInfo, 201);
                    // }
                    $this->responseException('This action is not allowed', 404);
                } elseif ($newStatus == 'closed') {
                    if ($objectData['added_by'] == $user['id']) {
                        $processingInfo->update(['status' => $newStatus, 'responsible_id' => $user['id']]);

                        return $this->responseSuccess($processingInfo, 201);
                    }
                    $this->responseException('This action is not allowed', 404);
                } elseif ($newStatus == 'reopened') {
                    // if ($checkResponsible) {
                    $status = 'disapproved';
                    if($processingInfo['status'] == 'completed_overdue') {
                        $status = "disapproved_overdue";
                    }
                    if (!empty($request->extend_date) && !empty($request->extend_time)) {
                        $status = 'disapproved_with_extended';
                    }
                    $processingInfo->update(['status' => $status, 'responsible_id' => $user['id'], 'responsible_comment' => $request->responsible_comment, 'updated_at' => date('y-m-d h:i:s')]);

                    if (isset($objectData['time']['id'])) {
                        $old_deadline = date("Y-m-d", $objectData['time']['deadline']);
                    }
                    if (!empty($request->processID)) {
                        // AttendeeProcessing::where('id', $request->processID )->update([
                        //     'status'=>$status
                        // ]); 
                        $res = ExtendedTimeline::create([
                            'object_id' => $objectData['id'] ?? '',
                            'process_id' => $request->processID ?? '',
                            'old_deadline' => $old_deadline ?? '',
                            'deadline_date' => $request->extend_date ?? null,
                            'deadline_time' => $request->extend_time ?? null,
                            'status' => 2,
                            'requested_by' => $processingInfo->added_by ?? '',
                            'extended_by_reason' => $request->responsible_comment ?? '',
                            'extended_by' => $user->id,
                            'extended_by_name' =>  $user->first_name . ' ' . $user->last_name,
                            'type' => 'responsible'
                        ]);
                    }

                    // $this->requestPushNotification($user['id'], $user['company_id'], [$processingInfo['added_by']], 'notification', $objectData, 'decline');
                    // $this->requestPushNotification($user['id'], $user['company_id'], [$processingInfo['added_by']], 'email', $objectData, 'decline');

                    return $this->responseSuccess($processingInfo, 201);
                    // }
                    return $this->responseException('This action is not allowed', 404);
                }

                $this->responseException('Invalid data', 404);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getSourceObject($item){
        $sourceData = [];
        $deviationObj = ObjectItem::where('id',$item->source_id)->first(['type','source','source_id']);
        if(isset($deviationObj->type) && $deviationObj->type == 'deviation'){
            $sourceData = Deviation::where('id',$deviationObj['source_id'])->first();
            $reportedUser = User::find($sourceData['added_by']);
            $sourceData['added_by_name'] = $reportedUser['first_name'] . " " . $reportedUser['last_name'];
            $sourceData['place'] = Places::select('id', 'place_name', 'added_by', 'is_deleted')->find($sourceData['place']);
            $sourceData['consequence_for'] = Consequences::select('id', 'name')->find($sourceData['consequence_for']);
            return $sourceData;
        }
        return $sourceData;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete category API",
     *     description="Delete category API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteCategoryAPI",
     *     @OA\Parameter(
     *         description="category id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $objectData = ObjectItem::where('objects.company_id', $user['company_id'])
                    ->where('objects.id', $id)
                    ->where('objects.is_valid', 1)
                    ->with(['attendee', 'responsible', 'time'])
                    ->first();
                if (empty($objectData)) {
                    return $this->responseException('Not found object', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, $objectData['type'], $objectData['id'], $objectData['name'])) {
                    $objectData->update(['is_valid' => 0]);

                    return $this->responseSuccess("Object deleted successfully");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
