<?php namespace App\Helpers;

use App\Models\Attendee;
use App\Models\AttendeeProcessing;
use App\Models\Billing;
use App\Models\DocumentAttachment;
use App\Models\EmailLog;
use App\Models\Employee;
use App\Models\ObjectItem;
use App\Models\Repository;
use App\Models\Responsible;
use App\Models\Routine;
use App\Models\Setting;
use App\Models\User;
use App\Models\Report;
use App\Notifications\NotifyIssue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class Helper
{

    public static function get_user_name(int $id=null)
    {
        if($id){

            $name= \DB::Table("users")->where("id",$id)->first();
            return isset($name->name) ? $name->name :'' ;

        }
    }
    public static function get_job_title(int $id)
    {
        if($id){
            $name= \DB::Table("companyroles")->select('jobtitle')->where("id",$id)->first();
            return isset($name->jobtitle) ? $name->jobtitle :'' ;
        }
    }

    public static function get_department(int $id=null)
    {
        if($id){

            $name = \DB::Table("companydepartment")->select('name')->where("id",$id)->first();
            return isset($name->name) ? $name->name : '';
        }
    }

    public static function SendEmailIssue($message)
    {
        $setting = Setting::where('key', 'email_system')->where('is_disabled', 1)->first();
        foreach (@$setting->value_details as $email) {
            try {
                Notification::route('mail', $email)
                    ->notify(new NotifyIssue($message));
                $emailStatus = EmailLog::SENT;
            } catch (\Exception $e) {
                Log::debug('issue mail issue : ', ['error' => $e]);
                $emailStatus = EmailLog::FAIL;
            }
            EmailLog::create([
                'type' => 'Third party API Service Issue',
                'description' => 'Third party API Service issue',
                'status' => $emailStatus,
                'for_admin' => 1,
            ]);
        }
    }

    public static function billing($subscription)
    {
        $storageUpload = DocumentAttachment::leftJoin('documents_new', 'documents_attachments.document_id', 'documents_new.id')
        ->where('documents_new.company_id', $subscription->company_id)
        ->where('documents_new.delete_status', 0)
        ->sum('documents_attachments.file_size');

        $storageRepo = Repository::where('company_id', $subscription->company_id)
            ->whereNotNull('attachment_uri')
            ->whereNull('restore_date')
            ->sum('attachment_size');

        $numberOfEmployee = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->where('users.company_id', $subscription->company_id)
            ->where('employees.disable_status', 0)
            ->get()
            ->count();

        $input['name'] = Helper::getInvoiceID();
        $input['company_id'] = $subscription->company_id;
        $input['company_name'] = $subscription->company->name;
        $input['added_by'] = $subscription->user->id;
        $input['storage_upload'] = $storageUpload;
        $input['storage_repo'] = $storageRepo;
        $input['employee'] = $numberOfEmployee;
        $input['subscription_id'] = $subscription->id;

        return $input;
    }

    public static function getInvoiceID()
    {
        $latestItem = Billing::orderBy('id', 'DESC')->first();
        $currentYear = date("Y");
        $newNumber = '00001-' . $currentYear;

        if ($latestItem) {
            $billingName = $latestItem->name;
            $year = substr($billingName, 6, 4);
            if ($year == $currentYear) {
                $billingName = str_replace('-' . $year, '', $billingName);
                $billingName = number_format($billingName) + 1;
                $billingName = sprintf("%05d", $billingName);
                $newNumber = $billingName . '-' . $year;
            }
        }

        return $newNumber;
    }

    public static function checkDocumentDisplayAccess($document)
    {
        if(isset($document['is_public']) && $document['is_public'] == 1) {
            $accessArr = User::select('id')->pluck('id')->toArray();
        } else {
            $accessArr = isset($document['added_by']) ? [$document['added_by'], 2] : [2];
            if(isset($document['security_employee_array']) && !empty($document['security_employee_array'])) {
                $document['security_employee_array'] = str_replace('"', '', $document['security_employee_array']);
                $security_employee_array = isset($document['security_employee_array']) ? json_decode($document['security_employee_array']) : [];
                $accessArr = array_merge($accessArr, $security_employee_array);
            }
        }

        if(isset($document['task_id']) && !empty($document['task_id'])) {
            $responsible = Responsible::where('object_id', $document['task_id'])->first();
            $responsible_employee_array = isset($responsible->employee_array) ? json_decode($responsible->employee_array) : [];
            $accessArr = array_merge($accessArr, $responsible_employee_array);

            $attendee = Attendee::where('object_id', $document['task_id'])->first();
            $attendee_employee_array = isset($attendee->employee_array) ? json_decode($attendee->employee_array) : [];
            $accessArr = array_merge($accessArr, $attendee_employee_array);
        }

        if(isset($document['routine_id']) && !empty($document['routine_id'])) {
            $responsible = Responsible::where('object_id', $document['routine_id'])->first();
            $responsible_employee_array = isset($responsible->employee_array) ? json_decode($responsible->employee_array) : [];
            $accessArr = array_merge($accessArr, $responsible_employee_array);

            $attendee = Attendee::where('object_id', $document['routine_id'])->first();
            $attendee_employee_array = isset($attendee->employee_array) ? json_decode($attendee->employee_array) : [];
            $accessArr = array_merge($accessArr, $attendee_employee_array);
        }

        $accessArr = array_unique($accessArr);

        return $accessArr;
    }

    public static function checkDeviationDisplayAccess($deviation)
    {
        $accessArr = [];
        $deviationObject = ObjectItem::where('type', 'deviation')->where('source_id', $deviation)->with('deviationSecurity', 'responsible', 'attendee')->first();

        if(isset($deviationObject) && !empty($deviationObject)) {
            if(isset($deviationObject->deviationSecurity->is_public) && $deviationObject->deviationSecurity->is_public == 1) {
                $accessArr = User::select('id')->pluck('id')->toArray();
            } else {
                $accessArr = isset($deviationObject->deviationSecurity->added_by) ? [$deviationObject->deviationSecurity->added_by, 2] : [2];
                if(isset($deviationObject->deviationSecurity->employee_array) && !empty($deviationObject->deviationSecurity->employee_array)) {
                    $deviationObject->deviationSecurity->employee_array = str_replace('"', '', $deviationObject->deviationSecurity->employee_array);
                    $employee_array = isset($deviationObject->deviationSecurity->employee_array) ? json_decode($deviationObject->deviationSecurity->employee_array) : [];
                    $accessArr = array_merge($accessArr, $employee_array);
                }
            }

            if(isset($deviationObject->responsible->employee_array) && !empty($deviationObject->responsible->employee_array)) {
                $deviationObject->responsible->employee_array = str_replace('"', '', $deviationObject->responsible->employee_array);
                $responsible_employee_array = isset($deviationObject->responsible->employee_array) ? json_decode($deviationObject->responsible->employee_array) : [];
                $accessArr = array_merge($accessArr, $responsible_employee_array);
            }

            if(isset($deviationObject->attendee->employee_array) && !empty($deviationObject->attendee->employee_array)) {
                $deviationObject->attendee->employee_array = str_replace('"', '', $deviationObject->attendee->employee_array);
                $attendee_employee_array = isset($deviationObject->attendee->employee_array) ? json_decode($deviationObject->attendee->employee_array) : [];
                $accessArr = array_merge($accessArr, $attendee_employee_array);
            }

            $taskObject = ObjectItem::where('type', 'task')->where('source', 'deviation')->where('source_id', $deviationObject->id)->with('responsible', 'attendee')->first();

            if(isset($taskObject) && !empty($taskObject)) {
                if(isset($taskObject->responsible->employee_array) && !empty($taskObject->responsible->employee_array)) {
                    $taskObject->responsible->employee_array = str_replace('"', '', $taskObject->responsible->employee_array);
                    $responsible_employee_array = isset($taskObject->responsible->employee_array) ? json_decode($taskObject->responsible->employee_array) : [];
                    $accessArr = array_merge($accessArr, $responsible_employee_array);
                }

                if(isset($taskObject->attendee->employee_array) && !empty($taskObject->attendee->employee_array)) {
                    $taskObject->attendee->employee_array = str_replace('"', '', $taskObject->attendee->employee_array);
                    $attendee_employee_array = isset($taskObject->attendee->employee_array) ? json_decode($taskObject->attendee->employee_array) : [];
                    $accessArr = array_merge($accessArr, $attendee_employee_array);
                }
            }

            $riskObject = ObjectItem::where('type', 'risk-analysis')->where('source', 'deviation')->where('source_id', $deviationObject->id)->with('riskAnalysisSecurity', 'responsible', 'attendee')->first();

            if(isset($riskObject) && !empty($riskObject)) {
                if(isset($riskObject->riskAnalysisSecurity->is_public) && $riskObject->riskAnalysisSecurity->is_public == 1) {
                    $accessArr = User::select('id')->pluck('id')->toArray();
                } else {
                    $accessArr = isset($riskObject->riskAnalysisSecurity->added_by) ? [$riskObject->riskAnalysisSecurity->added_by, 2] : [2];
                    if(isset($riskObject->riskAnalysisSecurity->employee_array) && !empty($riskObject->riskAnalysisSecurity->employee_array)) {
                        $riskObject->riskAnalysisSecurity->employee_array = str_replace('"', '', $riskObject->riskAnalysisSecurity->employee_array);
                        $employee_array = isset($riskObject->riskAnalysisSecurity->employee_array) ? json_decode($riskObject->riskAnalysisSecurity->employee_array) : [];
                        $accessArr = array_merge($accessArr, $employee_array);
                    }
                }

                if(isset($riskObject->responsible->employee_array) && !empty($riskObject->responsible->employee_array)) {
                    $riskObject->responsible->employee_array = str_replace('"', '', $riskObject->responsible->employee_array);
                    $responsible_employee_array = isset($riskObject->responsible->employee_array) ? json_decode($riskObject->responsible->employee_array) : [];
                    $accessArr = array_merge($accessArr, $responsible_employee_array);
                }

                $riskTaskObject = ObjectItem::where('type', 'task')->where('source', 'risk-analysis')->where('source_id', $riskObject->id)->with('responsible', 'attendee')->first();

                if(isset($riskTaskObject) && !empty($riskTaskObject)) {
                    if(isset($riskTaskObject->responsible->employee_array) && !empty($riskTaskObject->responsible->employee_array)) {
                        $riskTaskObject->responsible->employee_array = str_replace('"', '', $riskTaskObject->responsible->employee_array);
                        $responsible_employee_array = isset($riskTaskObject->responsible->employee_array) ? json_decode($riskTaskObject->responsible->employee_array) : [];
                        $accessArr = array_merge($accessArr, $responsible_employee_array);
                    }
    
                    if(isset($riskTaskObject->attendee->employee_array) && !empty($riskTaskObject->attendee->employee_array)) {
                        $riskTaskObject->attendee->employee_array = str_replace('"', '', $riskTaskObject->attendee->employee_array);
                        $attendee_employee_array = isset($riskTaskObject->attendee->employee_array) ? json_decode($riskTaskObject->attendee->employee_array) : [];
                        $accessArr = array_merge($accessArr, $attendee_employee_array);
                    }
                }
            }
        }

        $accessArr = array_unique($accessArr);

        return $accessArr;
    }
    public static function checkRiskAnalysisDisplayAccess($riskAnalysis)
    {
        $accessArr = [];
        $riskObject = ObjectItem::where('type', 'risk-analysis')->where('id', $riskAnalysis)->with('riskAnalysisSecurity', 'responsible', 'attendee')->first();
        if(isset($riskObject) && !empty($riskObject)) {
            if(isset($riskObject->riskAnalysisSecurity->is_public) && $riskObject->riskAnalysisSecurity->is_public == 1) {
                $accessArr = User::select('id')->pluck('id')->toArray();
            } else {
                $accessArr = isset($riskObject->riskAnalysisSecurity->added_by) ? [$riskObject->riskAnalysisSecurity->added_by, 2] : [2];
                if(isset($riskObject->riskAnalysisSecurity->employee_array) && !empty($riskObject->riskAnalysisSecurity->employee_array)) {
                    $riskObject->riskAnalysisSecurity->employee_array = str_replace('"', '', $riskObject->riskAnalysisSecurity->employee_array);
                    $employee_array = isset($riskObject->riskAnalysisSecurity->employee_array) ? json_decode($riskObject->riskAnalysisSecurity->employee_array) : [];
                    $accessArr = array_merge($accessArr, $employee_array);
                }
            }

            if(isset($riskObject->responsible->employee_array) && !empty($riskObject->responsible->employee_array)) {
                $riskObject->responsible->employee_array = str_replace('"', '', $riskObject->responsible->employee_array);
                $responsible_employee_array = isset($riskObject->responsible->employee_array) ? json_decode($riskObject->responsible->employee_array) : [];
                $accessArr = array_merge($accessArr, $responsible_employee_array);
            }
            $riskTaskObject = ObjectItem::where('type', 'task')->where('source', 'risk-analysis')->where('source_id', $riskObject->id)->with('responsible', 'attendee')->first();
            if(isset($riskTaskObject) && !empty($riskTaskObject)) {
                if(isset($riskTaskObject->responsible->employee_array) && !empty($riskTaskObject->responsible->employee_array)) {
                    $riskTaskObject->responsible->employee_array = str_replace('"', '', $riskTaskObject->responsible->employee_array);
                    $responsible_employee_array = isset($riskTaskObject->responsible->employee_array) ? json_decode($riskTaskObject->responsible->employee_array) : [];
                    $accessArr = array_merge($accessArr, $responsible_employee_array);
                }

                if(isset($riskTaskObject->attendee->employee_array) && !empty($riskTaskObject->attendee->employee_array)) {
                    $riskTaskObject->attendee->employee_array = str_replace('"', '', $riskTaskObject->attendee->employee_array);
                    $attendee_employee_array = isset($riskTaskObject->attendee->employee_array) ? json_decode($riskTaskObject->attendee->employee_array) : [];
                    $accessArr = array_merge($accessArr, $attendee_employee_array);
                }
            }
        }

        $accessArr = array_unique($accessArr);

        return $accessArr;
    }
    public static function checkChecklistDisplayAccess($checklist)
    {
        $accessArr = [];
        $checklistObject = ObjectItem::where('type', 'checklist')->where('id', $checklist)->with('checklistSecurity', 'responsible', 'attendee')->first();
        if(isset($checklistObject) && !empty($checklistObject)) {
            if(isset($checklistObject->checklistSecurity->is_public) && $checklistObject->checklistSecurity->is_public == 1) {
                $accessArr = User::select('id')->pluck('id')->toArray();
            } else {
                $accessArr = isset($checklistObject->checklistSecurity->added_by) ? [$checklistObject->checklistSecurity->added_by, 2] : [2];
                if(isset($checklistObject->checklistSecurity->employee_array) && !empty($checklistObject->checklistSecurity->employee_array)) {
                    $checklistObject->checklistSecurity->employee_array = str_replace('"', '', $checklistObject->checklistSecurity->employee_array);
                    $employee_array = isset($checklistObject->checklistSecurity->employee_array) ? json_decode($checklistObject->checklistSecurity->employee_array) : [];
                    $accessArr = array_merge($accessArr, $employee_array);
                }
            }

            if(isset($checklistObject->responsible->employee_array) && !empty($checklistObject->responsible->employee_array)) {
                $checklistObject->responsible->employee_array = str_replace('"', '', $checklistObject->responsible->employee_array);
                $responsible_employee_array = isset($checklistObject->responsible->employee_array) ? json_decode($checklistObject->responsible->employee_array) : [];
                $accessArr = array_merge($accessArr, $responsible_employee_array);
            }
        }

        $accessArr = array_unique($accessArr);
        return $accessArr;
    }
    public static function checkReportDisplayAccess($report)
    {
        $accessArr = [];
        $accessTestArr = [];
        $reportObject = Report::where('id', $report)->with('reportSecurity')->first();
        if(isset($reportObject) && !empty($reportObject)) {
            if(isset($reportObject->reportSecurity->is_public) && $reportObject->reportSecurity->is_public == 1) {
                $accessArr = User::select('id')->pluck('id')->toArray();
            } else {
                $accessArr = isset($reportObject->reportSecurity->added_by) ? [$reportObject->reportSecurity->added_by, 2] : [2];
                if(isset($reportObject->reportSecurity->employee_array) && !empty($reportObject->reportSecurity->employee_array)) {
                    $reportObject->reportSecurity->employee_array = str_replace('"', '', $reportObject->reportSecurity->employee_array);
                    $employee_array = isset($reportObject->reportSecurity->employee_array) ? json_decode($reportObject->reportSecurity->employee_array) : [];
                    $accessArr = array_merge($accessArr, $employee_array);
                }
            }
            $taskObject = ObjectItem::where('type', 'task')->where('source', 'report')->where('source_id', $report)->with('responsible', 'attendee')->get();
                if(isset($taskObject) && !empty($taskObject)) {
                    foreach ($taskObject as $key => $tasks) {
                        if(isset($tasks->responsible->employee_array) && !empty($tasks->responsible->employee_array)) {
                            $tasks->responsible->employee_array = str_replace('"', '', $tasks->responsible->employee_array);
                            $responsible_employee_array = isset($tasks->responsible->employee_array) ? json_decode($tasks->responsible->employee_array) : [];
                            $accessArr = array_merge($accessArr, $responsible_employee_array);
                        }
                        if(isset($tasks->attendee->employee_array) && !empty($tasks->attendee->employee_array)) {
                            $tasks->attendee->employee_array = str_replace('"', '', $tasks->attendee->employee_array);
                            $attendee_employee_array = isset($tasks->attendee->employee_array) ? json_decode($tasks->attendee->employee_array) : [];
                            $accessArr = array_merge($accessArr, $attendee_employee_array);
                        }
                    }
                    
                }
           
            $riskObject = ObjectItem::where('type', 'risk-analysis')->where('source', 'report')->where('source_id', $report)->with('riskAnalysisSecurity', 'responsible', 'attendee')->get();
                if(isset($riskObject) && !empty($riskObject)) {
                    foreach ($riskObject as $key => $risk) {
                        if(isset($risk->riskAnalysisSecurity->is_public) && $risk->riskAnalysisSecurity->is_public == 1) {
                            $accessTestArr = User::select('id')->pluck('id')->toArray();
                        } else {
                            $accessTestArr = isset($risk->riskAnalysisSecurity->added_by) ? [$risk->riskAnalysisSecurity->added_by, 2] : [2];
                            if(isset($risk->riskAnalysisSecurity->employee_array) && !empty($risk->riskAnalysisSecurity->employee_array)) {
                                $risk->riskAnalysisSecurity->employee_array = str_replace('"', '', $risk->riskAnalysisSecurity->employee_array);
                                $employee_array = isset($risk->riskAnalysisSecurity->employee_array) ? json_decode($risk->riskAnalysisSecurity->employee_array) : [];
                                $accessTestArr = array_merge($accessTestArr, $employee_array);
                            }
                        }

                        if(isset($risk->responsible->employee_array) && !empty($risk->responsible->employee_array)) {
                            $risk->responsible->employee_array = str_replace('"', '', $risk->responsible->employee_array);
                            $responsible_employee_array = isset($risk->responsible->employee_array) ? json_decode($risk->responsible->employee_array) : [];
                            $accessTestArr = array_merge($accessTestArr, $responsible_employee_array);
                        }
                        $riskTaskObject = ObjectItem::where('type', 'task')->where('source', 'risk-analysis')->where('source_id', $risk->id)->with('responsible', 'attendee')->first();

                        if(isset($riskTaskObject) && !empty($riskTaskObject)) {
                            if(isset($riskTaskObject->responsible->employee_array) && !empty($riskTaskObject->responsible->employee_array)) {
                                $riskTaskObject->responsible->employee_array = str_replace('"', '', $riskTaskObject->responsible->employee_array);
                                $responsible_employee_array = isset($riskTaskObject->responsible->employee_array) ? json_decode($riskTaskObject->responsible->employee_array) : [];
                                $accessTestArr = array_merge($accessTestArr, $responsible_employee_array);
                            }

                            if(isset($riskTaskObject->attendee->employee_array) && !empty($riskTaskObject->attendee->employee_array)) {
                                $riskTaskObject->attendee->employee_array = str_replace('"', '', $riskTaskObject->attendee->employee_array);
                                $attendee_employee_array = isset($riskTaskObject->attendee->employee_array) ? json_decode($riskTaskObject->attendee->employee_array) : [];
                                $accessTestArr = array_merge($accessTestArr, $attendee_employee_array);
                            }
                        }
                    }
                    
                }
        }
        $accessArr = array_merge($accessArr, $accessTestArr); 
        $accessArr = array_unique($accessArr);

        return $accessArr;
    }
    public static function checkReportTaskDisplayAccess($report,$user)
    {
        $accessArr = [];
            // $taskObject = ObjectItem::where('type', 'task')->where('source', 'report')->where('source_id', $report)->with('responsible', 'attendee')->get();
            $taskObject = ObjectItem::where('source', 'report')->where('source_id', $report)->with('responsible', 'attendee')->get();
                if(isset($taskObject) && !empty($taskObject)) {
                    foreach ($taskObject as $key => $tasks) {
                        if($tasks->type == 'task'){
                            $attendee_info = Attendee::where('object_id', $tasks->id)->latest()->first();
                                if(isset($attendee_info) && !empty($attendee_info)){
                                    $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                    if(isset($attendee_processing) && !empty($attendee_processing)){
                                        $accessArr[] = $tasks->id;
    
                                    }
                                }
                        }
                        if($tasks->type == 'risk-analysis'){
                            $accessArr[] = $tasks->id;
                        }
                    }
                    
                }
                   
        $accessArr = array_unique($accessArr);

        return $accessArr;
    }
    public static function checkReportTaskRiskDisplayAccess($report,$user)
    {
        $accessArr = [];
            // $taskObject = ObjectItem::where('type', 'task')->where('source', 'report')->where('source_id', $report)->with('responsible', 'attendee')->get();
            $taskRiskObject = ObjectItem::where('source', 'report')->where('source_id', $report)->with('responsible', 'attendee')->get();
                if(isset($taskRiskObject) && !empty($taskRiskObject)) {
                    foreach ($taskRiskObject as $key => $obj) {
                        if($obj->type == 'task'){
                            $attendee_info = Attendee::where('object_id', $obj->id)->latest()->first();
                                if(isset($attendee_info) && !empty($attendee_info)){
                                    $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                    if(isset($attendee_processing) && !empty($attendee_processing)){
                                        $accessArr[] = $obj->id;
    
                                    }
                                }
                                if($user->role_id != 4){
                                    $accessArr[] = $obj->id;
                                }
                        }
                        if($obj->type == 'risk-analysis'){
                            $taskObject = ObjectItem::where('type', 'task')->where('source', 'risk-analysis')->where('source_id', $obj->id)->with('responsible', 'attendee')->first();
                            if(isset($taskObject) && !empty($taskObject)) {
                                $attendee_info = Attendee::where('object_id', $taskObject->id)->latest()->first();
                                if(isset($attendee_info) && !empty($attendee_info)){
                                    $attendee_processing = AttendeeProcessing::where('attendee_id', $attendee_info->id)->where('added_by', $user->id)->first();
                                    if(isset($attendee_processing) && !empty($attendee_processing)){
                                        $accessArr[] = $taskObject->id;
    
                                    }
                                }
                                if($user->role_id != 4){
                                    $accessArr[] = $taskObject->id;
                                }
                            }
                        }
                    }
                    
                }
        $accessArr = array_unique($accessArr);

        return $accessArr;
    }

    public static function checkTaskDisplayAccess($task)
    {
        $accessArr = [];
        $taskObject = ObjectItem::where('type', 'task')->where('id', $task)->with('responsible', 'attendee')->first();
        if(isset($taskObject) && !empty($taskObject)) {
            $accessArr = isset($taskObject->added_by) ? [$taskObject->added_by, 2] : [2];
            if(isset($taskObject->responsible->employee_array) && !empty($taskObject->responsible->employee_array)) {
                $taskObject->responsible->employee_array = str_replace('"', '', $taskObject->responsible->employee_array);
                $responsible_employee_array = isset($taskObject->responsible->employee_array) ? json_decode($taskObject->responsible->employee_array) : [];
                $accessArr = array_merge($accessArr, $responsible_employee_array);
            }

            if(isset($taskObject->attendee->employee_array) && !empty($taskObject->attendee->employee_array)) {
                $taskObject->attendee->employee_array = str_replace('"', '', $taskObject->attendee->employee_array);
                $attendee_employee_array = isset($taskObject->attendee->employee_array) ? json_decode($taskObject->attendee->employee_array) : [];
                $accessArr = array_merge($accessArr, $attendee_employee_array);
            }
        }

        $accessArr = array_unique($accessArr);
        return $accessArr;
    }
    public static function checkRoutineDisplayAccess($routine)
    {
        $accessArr = [];
        $routineObject = ObjectItem::where('id', $routine)->where('type', 'routine')->with('routineSecurity', 'responsible', 'attendee')->first();
    
        if(isset($routineObject) && !empty($routineObject)) {
            if(isset($routineObject->routineSecurity->is_public) && $routineObject->routineSecurity->is_public == 1) {
                $accessArr = User::select('id')->pluck('id')->toArray();
            } else {
                $accessArr = isset($routineObject->routineSecurity->added_by) ? [$routineObject->routineSecurity->added_by, 2] : [2];
                if(isset($routineObject->routineSecurity->employee_array) && !empty($routineObject->routineSecurity->employee_array)) {
                    $routineObject->routineSecurity->employee_array = str_replace('"', '', $routineObject->routineSecurity->employee_array);
                    $employee_array = isset($routineObject->routineSecurity->employee_array) ? json_decode($routineObject->routineSecurity->employee_array) : [];
                    $accessArr = array_merge($accessArr, $employee_array);
                }
            }

            if(isset($routineObject->responsible->employee_array) && !empty($routineObject->responsible->employee_array)) {
                $routineObject->responsible->employee_array = str_replace('"', '', $routineObject->responsible->employee_array);
                $responsible_employee_array = isset($routineObject->responsible->employee_array) ? json_decode($routineObject->responsible->employee_array) : [];
                $accessArr = array_merge($accessArr, $responsible_employee_array);
            }
            
            if(isset($routineObject->attendee->employee_array) && !empty($routineObject->attendee->employee_array)) {
                $routineObject->attendee->employee_array = str_replace('"', '', $routineObject->attendee->employee_array);
                $attendee_employee_array = isset($routineObject->attendee->employee_array) ? json_decode($routineObject->attendee->employee_array) : [];
                $accessArr = array_merge($accessArr, $attendee_employee_array);
            }
            $routineTaskObject = ObjectItem::where('type', 'task')->where('source', 'routine')->where('source_id', $routineObject->source_id)->with('responsible', 'attendee')->first();
            if(isset($routineTaskObject) && !empty($routineTaskObject)) {
                if(isset($routineTaskObject->responsible->employee_array) && !empty($routineTaskObject->responsible->employee_array)) {
                    $routineTaskObject->responsible->employee_array = str_replace('"', '', $routineTaskObject->responsible->employee_array);
                    $responsible_employee_array = isset($routineTaskObject->responsible->employee_array) ? json_decode($routineTaskObject->responsible->employee_array) : [];
                    $accessArr = array_merge($accessArr, $responsible_employee_array);
                }

                if(isset($routineTaskObject->attendee->employee_array) && !empty($routineTaskObject->attendee->employee_array)) {
                    $routineTaskObject->attendee->employee_array = str_replace('"', '', $routineTaskObject->attendee->employee_array);
                    $attendee_employee_array = isset($routineTaskObject->attendee->employee_array) ? json_decode($routineTaskObject->attendee->employee_array) : [];
                    $accessArr = array_merge($accessArr, $attendee_employee_array);
                }
            }
        }

        $accessArr = array_unique($accessArr);
        return $accessArr;
    }
    
}
?>