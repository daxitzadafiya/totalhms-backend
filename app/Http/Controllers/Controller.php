<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Mail\DeclinedTaskMail;
use App\Mail\WelcomeMail;
use App\Models\AbsenceReason;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\Company;
use App\Models\ConnectTo;
use App\Models\Department;
use App\Models\Responsible;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentNew;
use App\Models\Employee;
use App\Models\EmployeeRelation;
use App\Models\Goal;
use App\Models\HelpCenter;
use App\Models\HelpCenterQuestion;
use App\Models\Instruction;
use App\Models\IntervalSetting;
use App\Models\JobTitle;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ObjectItem;
use App\Models\Reminder;
use App\Models\Repository;
use App\Models\RequestPushNotification;
use App\Models\RiskAnalysis;
use App\Models\RiskElement;
use App\Models\RiskElementSource;
use App\Models\Routine;
use App\Models\Security;
use App\Models\Statement;
use App\Models\SubGoal;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TimeManagement;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use app\helpers\ErrorFormat;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Response;
use Validator;
use JWTAuth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @desc Response success
     *
     * @param array|object|string $data
     *
     * @return \Illuminate\Http\Response Response
     * */

    public $timezone; 

    public function __construct()
    {
        if(Auth::check()){
            $company = Company::find(Auth::user()->company_id);
            $this->timezone = $company->time_zone ?? 'Europe/Oslo';
        }
    } 

    public static function responseSuccess($data)
    {
        return Response::json(array(
            'error' => false,
            'data' => $data,
            'errors' => null
        ), 200);
    }

    /**
     * @desc Response error
     *
     * @param int $statusCode
     * @param  \app\helpers\ErrorFormat[] $errors
     *
     * @return \Illuminate\Http\Response Response
     * */
    public static function responseError($errors, $statusCode = null)
    {
        if (!isset($statusCode)) {
            $statusCode = 400;
        }
        $parseErrors = array();
        foreach ($errors as $error) {
            $parseErrors[] = new ErrorFormat($error[0], $error[1]);
        }

        $response = array(
            'error' => true,
            'data' => null,
            'errors' => $parseErrors
        );
        return Response::json($response, $statusCode);
    }

    public static function responseException($messages, $statusCode = null)
    {
        if (!isset($statusCode)) {
            $statusCode = 400;
        }
        $parseErrors = array();
        $parseErrors[] = new ErrorFormat($messages, 5000);

        $response = array(
            'error' => true,
            'data' => null,
            'errors' => $parseErrors
        );
        return Response::json($response, $statusCode);
    }

    public function calRemainingTime($deadline, $getBy = false)
    {
        $rem = strtotime($deadline) - time(); // change date and time to suit.
        $day = floor($rem / 86400);
        $hr  = floor(($rem % 86400) / 3600);
        $min = floor(($rem % 3600) / 60);
        $sec = ($rem % 60);

        if ($getBy && $getBy == 'day') {
            $result = -1;
            if ($day > 0) {
                $result = $day;
            }
        } else {
            $result = 'EXPIRED';
            if ($day > 0) {
                $result = $day . ' day(s) left';
            } else {
                if ($hr > 0) {
                    $result = $hr . ' hour(s) left';
                } else {
                    if ($min > 0) {
                        $result = $min . ' minute(s) left';
                    } else {
                        if ($sec > 0) {
                            $result = $sec . ' second(s) left';
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function pushNotificationToAllCompanies($feature, $feature_id, $name, $action, $option = null)
    {
        if (!$feature || !$feature_id || !$name || !$action) return null;

        $rules = RequestPushNotification::$rules;

        $input['send_from'] = 1;
        $input['feature'] = $feature;
        $input['feature_id'] = $feature_id;
        $input['send_to_option'] = 'company';

        if ($action == 'create') {
            $input['short_description'] = '<b>' . $name . '</b> ' . strtolower($feature) . ' has been created';
        } elseif ($action == 'update') {
            $input['short_description'] = '<b>' . $name . '</b> ' . strtolower($feature) . ' has been updated';
        }

        if ($feature == 'Instruction') {
            $input['url'] = '/company/instructions?type=resource&id=' . $input['feature_id'];
        } elseif ($feature == 'Checklist') {
            $input['url'] = '/company/checklists?type=resource&id=' . $input['feature_id'];
        } elseif ($feature == 'Routine') {
            $input['url'] = '/company/routines?type=resource&id=' . $input['feature_id'];
        } elseif ($feature == 'Category') {
            $input['url'] = '/settings/categories?type=' . $option . '&id=' . $input['feature_id'];
        } elseif ($feature == 'Goal') {
            $input['url'] = '/company/goals?type=resource&id=' . $input['feature_id'];
        } elseif ($feature == 'Document') {
            $input['url'] = '/documents/documents?type=resource&id=' . $input['feature_id'];
        } elseif ($feature == 'Role' || $feature == 'Job title') {
            $input['url'] = '/settings/jobTitles?type=jobTitle';
        }

        $sendToArray = Company::where('id', '>', 0)->pluck('id')->toArray();

        $countSendTo = count($sendToArray);
        if ($countSendTo > 0) {
            $input['send_to'] = json_encode($sendToArray);
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        $newRequestPushNotification = RequestPushNotification::create($input);

        if ($countSendTo < 10) {
            foreach ($sendToArray as $item) {
                $adminOfCompany = User::where('company_id', $item)->where('added_by', 1)->first();
                if ($adminOfCompany) {
                    $this->createNotification($adminOfCompany->id, $newRequestPushNotification->id);
                }
                //                foreach ($adminOfCompany as $userAdmin) {
                //                }
            }

            $input['sending_time'] = Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 3;

            $newRequestPushNotification->update($input);
        }

        return $this->responseSuccess($newRequestPushNotification, 201);
    }

    public function pushNotification($send_from, $company_id, $message_id, $sendToArray = [], $page = null, $object_type = null, $object_id = null, $name = null, $action = null, $url = null, $deadline = null)
    {
        if (!$send_from || !$message_id || empty($sendToArray) || ($object_type && !$object_id)) {
            return null;
        }

        $rules = RequestPushNotification::$rules;

        $input['company_id'] = $company_id;
        $input['send_from'] = $send_from;
        $input['message_id'] = $message_id;
        $input['feature'] = '';
        $input['feature_id'] = '';
        if ($object_type) {
            $input['feature'] = $object_type;
            $input['feature_id'] = $object_id;
        }

        //        $message = Message::find($message_id);
        //        if (empty($message)) {
        //            return $this->responseException('Not found message', 404);
        //        }

        $input['short_description'] = 'test';
        //        $input['description'] = $message->content;

        if ($page == 'task') {
            //            $input['short_description'] = '[' . strtoupper($page) . '] ' . $input['short_description'];
            if ($action == 'assigned') {
                $input['short_description'] = 'You have been assigned a task: <b>' . $name . '</b>';
            } elseif ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for task: <b>' . $name . '</b>';
            } elseif ($action == 'assignee_done') {
                $input['short_description'] = 'Task <b>' . $name . '</b> has been done by all assignees.';
            }

            $input['url'] = '/company/tasks?type=' . $input['feature'] . '&id=' . $input['feature_id'];
        } elseif ($page == 'deviation') {
            if ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for report: <b>' . $name . '</b>';
            } elseif ($action == 'action_done') {
                $input['short_description'] = 'All actions for report <b>' . $name . '</b> has been done.';
            }
            $input['url'] = '/company/deviations?id=' . $input['feature_id'];
        } elseif ($page == 'report') {
            if ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for report: <b>' . $name . '</b>';
            } elseif ($action == 'action_done') {
                $input['short_description'] = 'All actions for report <b>' . $name . '</b> has been done.';
            }
            $input['url'] = '/reports/reportedChecklists?id=' . $input['feature_id'];
        } elseif ($page == 'risk') {
            if ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for report: <b>' . $name . '</b>';
            } elseif ($action == 'action_done') {
                $input['short_description'] = 'All actions for report <b>' . $name . '</b> has been done.';
            }
            $input['url'] = '/reports/reportedRiskanalysis?id=' . $input['feature_id'];
        } elseif ($page == 'absence processing') {
            $input['short_description'] = '[' . strtoupper($page) . '] ' . $input['short_description'];
            $input['url'] = '/employees/absences';
        } elseif ($page == 'document') {
            if ($action == 'reminder') {
                $input['short_description'] = 'Document <b>' . $name . '</b> need to be updated before ' . $deadline;
                $input['url'] = '/documents/documents?id=' . $input['feature_id'];
            }
        } elseif ($page == 'routine') {
            if ($action == 'update') {
                $input['short_description'] = 'There is an update with the routine <b>' . $name . '</b>. Please review it.';
            } elseif ($action == 'assigned') {
                $input['short_description'] = 'Please attend to your assigned <b>' . $name . '</b>';
            } elseif ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for routine: <b>' . $name . '</b>';
            }
            $input['url'] = '/company/routines?id=' . $input['feature_id'];
        } elseif ($page == 'goal') {
            if ($action == 'update') {
                $input['short_description'] = 'There is an update with the goal <b>' . $name . '</b>. Please review it.';
            } elseif ($action == 'assigned') {
                $input['short_description'] = 'Please attend to your assigned <b>' . $name . '</b>';
            } elseif ($action == 'responsible') {
                $input['short_description'] = 'You are responsible person for goal: <b>' . $name . '</b>';
            }
            $input['url'] = '/company/goals?id=' . $input['feature_id'];
        } elseif ($page == 'plan_subscription') {
            if ($action == 'create') {
                $input['short_description'] = 'The company has purchased a new plan <b>' .$name.'</b>.';
            } elseif ($action == 'update') {
                $input['short_description'] = 'The company has changed a plan <b>' . $name . '</b>.';
            } elseif ($action == 'renew') {
                $input['short_description'] = 'I have renewed your plan <b>' . $name . '</b>.';
            } elseif ($action == 'cancel') {
                $input['short_description'] = 'The company has canceled its <b>' . $name . '</b> plan.';
            }
            $input['url'] = '/settings/billings?type=' . $input['feature'] . '&id=' . $input['feature_id'];
        } elseif ($page == 'addon_subscription') {
            if ($action == 'create') {
                $input['short_description'] = 'The company has purchased a new addon <b>' .$name.'</b>.';
            } elseif ($action == 'renew') {
                $input['short_description'] = 'I have renewed your addon <b>' . $name . '</b>.';
            } elseif ($action == 'cancel') {
                $input['short_description'] = 'The company has canceled its <b>' . $name . '</b> addon.';
            }
            $input['url'] = '/settings/billings?type=' . $input['feature'] . '&id=' . $input['feature_id'];
        } elseif ($page == 'reminder') {
            if ($action == 'reminder_invoice') {
                $input['short_description'] = ' This is reminder for invoice in <b>' .$name.'</b>.';
            } elseif($action == 'reminder_free_trails') {
                $input['short_description'] = ' This is reminder for free trail end in last 5 days <b>' .$name.'</b>.';
            }
            $input['url'] = '/invoices/invoices';
        } elseif ($page == 'coupon') {
            if ($action == 'invite') {
                $input['short_description'] = ' You have assigned a coupon: <b>' .$name.'</b>.';
            } 
        } elseif ($page == 'invite') {
            if ($action == 'accept') {
                $input['short_description'] = ' Accepted Invitation from: <b>' .$name.'</b>.';
            } elseif($action == 'send') {
                $input['short_description'] = 'Sending Invitation user for <b>' .$name.'</b>.';
            } elseif($action == 'resend') {
                $input['short_description'] = 'Sending Invitation user for <b>' .$name.'</b>.';
            }
            $input['url'] = '/admin/overview/invites?id=' . $input['feature_id'];
        } elseif ($page == 'cs_invite') {
            if($action == 'send') {
                $input['short_description'] = 'Sending Invitation Cs user for <b>' .$name.'</b>.';
            } elseif($action == 'resend') {
                $input['short_description'] = 'Sending Invitation Cs user for <b>' .$name.'</b>.';
            }
            $input['url'] = 'admin/overview/customerServices?id=' . $input['feature_id'];
        } elseif ($page == 'cancel') {
            if($action == 'plan_cancel') {
                $input['short_description'] = '<b>' .$name. '</b> plan has been deleted by supper admin please change your plan before <b>' . $deadline. '</b>.';
            } elseif($action == 'addon_cancel') {
                $input['short_description'] = '<b>' .$name. '</b> addon has been deleted by supper admin please change your addon before <b>' . $deadline. '</b>.';
            }
            $input['url'] = '/settings/billings';
        }

        if ($url) {
            $input['url'] =  $url;
        }

        $countSendTo = count($sendToArray);
        if ($countSendTo > 0) {
            $input['send_to'] = json_encode($sendToArray);
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $newRequestPushNotification = RequestPushNotification::create($input);

        if ($countSendTo < 10) {
            foreach ($sendToArray as $item) {
                $this->createNotification($item, $newRequestPushNotification->id);
            }

            $input['sending_time'] = Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 3;

            $newRequestPushNotification->update($input);
        }

        return $this->responseSuccess($newRequestPushNotification, 201);
    }

    public function pushAlert($send_from, $send_to_option, $description, $sendToArray = [])
    {
        if (!$send_from || !$send_to_option || !$description || empty($sendToArray)) {
            return null;
        }

        $rules = RequestPushNotification::$rules;

        $input['send_from'] = $send_from;
        $input['send_to_option'] = $send_to_option;
        $input['type'] = 'alert';

        $countSendTo = count($sendToArray);
        if ($countSendTo > 0) {
            $input['send_to'] = json_encode($sendToArray);
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }

        $newRequestPushNotification = RequestPushNotification::create($input);

        return $this->responseSuccess($newRequestPushNotification, 201);
    }

    public function createNotification($user_id, $request_push_notification_id, $show_action = false)
    {
        if (!$user_id || !$request_push_notification_id) {
            return null;
        }
        $rulesNotification = Notification::$rules;

        $inputNotification['user_id'] = $user_id;
        $inputNotification['request_push_notification_id'] = $request_push_notification_id;
        if ($show_action) {
            $checkExist = Notification::where('user_id', $user_id)
                ->where('show_action', 1)
                ->exists();

            if ($checkExist) {
                return null;
            }

            $inputNotification['show_action'] = 1;
        }

        $validatorNotification = Validator::make($inputNotification, $rulesNotification);

        if ($validatorNotification->fails()) {
            $errorsNotification = ValidateResponse::make($validatorNotification);
            return $this->responseError($errorsNotification, 400);
        }

        Notification::create($inputNotification);
    }

    public function updateDaysOffSickChild($user_id)
    {
        if (!$user_id) return null;

        $employee = Employee::where('user_id', $user_id)->first();
        if (empty($employee)) {
            return $this->responseException('Not found employee', 404);
        }
        $company_id = User::find($user_id)->company_id;

        $absenceSetting = AbsenceReason::where('company_id', $company_id)
            ->where('type', 'Sick child')
            ->where('sick_child', 1)
            ->first();
        if (empty($absenceSetting)) {
            return $this->responseException('Not found absence setting', 404);
        }

        $dependants = EmployeeRelation::where('user_id', $user_id)->where('relation', 'Children')->get();
        if (empty($dependants)) {
            return null;
        }

        $numberOfChildren = 0;
        $aloneCustody = false;
        $absence_info = json_decode($employee->absence_info);

        $key = array_search($absenceSetting->id, array_column($absence_info, 'absence_reason_id'));

        //        $usedDaysOffOfEmployee = $absence_info[$key]['used_days_off'] + $absence_info[$key]['pending_days_off'];

        $maxDaysOff = $absenceSetting->days_off;
        $maxDaysOffException = $absenceSetting->days_off_exception;
        $aloneCustodyExtra = $absenceSetting->extra_alone_custody;
        $maxAge = $absenceSetting->sick_child_max_age;
        $maxAgeHandicapped = $absenceSetting->sick_child_max_age_handicapped;

        foreach ($dependants as $dependant) {
            $birthDate  = new \DateTime($dependant->dob);
            $today   = new \DateTime('today');
            $age = $birthDate->diff($today)->y;

            if ($age <= $maxAge || ($age <= $maxAgeHandicapped && $dependant->handicapped)) {
                $numberOfChildren += 1;
                if (!$aloneCustody && $dependant->alone_custody) {
                    $aloneCustody = true;
                }
            }
        }

        if ($numberOfChildren > 0) {
            if ($numberOfChildren <= 2) {
                $daysOff = $maxDaysOff;
            } else {
                $daysOff = $maxDaysOffException;
            }
        } else {
            $daysOff = 0;
        }

        if ($aloneCustody) {
            $daysOff *= $aloneCustodyExtra;
        }

        $daysOff = (float)$daysOff;

        $absence_info[$key]->max_days_off = $daysOff;

        $inputEmployee['absence_info'] = json_encode($absence_info);

        $employee->update($inputEmployee);

        return true;
    }

    public function calculateProgressRateByType($result)
    {
        foreach ($result as $item) {
            $totalTask = 0;
            $doneTask = 0;
            $rate = 0;
            if ($item->status == 1) {
                $rate = 0;
            } elseif ($item->status == 3) {
                $rate = 100;
            } else {
                if (!empty($item->tasks)) {
                    foreach ($item->tasks as $task) {
                        if (!empty($task->task_assignees)) {
                            foreach ($task->task_assignees as $assignee) {
                                if ($assignee->responsible == 1) {
                                    $totalTask += 1;
                                    if ($assignee->status == 3) {
                                        $doneTask += 1;
                                    }
                                }
                            }
                            $rate = $doneTask / $totalTask * 100;
                        }
                    }
                }
            }
            $item->rate = $rate;
        }
    }

    public function createTaskItem($taskItem, $input, $userID, $companyID, $type, $typeID)
    {
        $taskRules = Task::$rules;
        if (!empty($inputTask['assigned_employee'])) {
            $inputTask['assigned_employee'] = null;
        }
        if (!empty($inputTask['assigned_department'])) {
            $inputTask['assigned_department'] = null;
        }
        $inputTask['name'] = $taskItem['name'];
        if (!empty($companyID)) {
            $inputTask['company_id'] = $companyID;
        }
        $inputTask['update_history'] = json_encode($this->setUpdateHistory('created', $userID, [], 'object', 'task', $inputTask['name']));
        $inputTask['added_by'] = $userID;
        $inputTask['industry_id'] = $input['industry_id'];
        $inputTask['department_id'] = $input['department_id'];
        $inputTask['job_title_id'] = $input['job_title_id'];
        $inputTask['is_public'] = $input['is_public'];
        $inputTask['type'] = $type;
        $inputTask['type_id'] = $typeID;
        $inputTask['status'] = 1;
        $inputTask['responsible_id'] = $input['responsible_id'];
        $inputTask['start_time'] = $input['start_time'] ?? '';
        $inputTask['deadline'] = $input['deadline'] ?? '';
        $inputTask['recurring'] = $input['recurring'];

        if (!empty($input['description'])) {
            $inputTask['description'] = $input['description'];
        }
        if (!empty($input['type_main_id'])) {
            $inputTask['type_main_id'] = $input['type_main_id'];
        }
        $inputTask['assigned_company'] = $taskItem['assigned_company'];
        if (!$taskItem['assigned_company'] && !empty($taskItem['assigned_employee'])) {
            $inputTask['assigned_employee'] = json_encode($taskItem['assigned_employee']);
        }
        if (!$taskItem['assigned_company'] && !empty($taskItem['assigned_department'])) {
            $inputTask['assigned_department'] = json_encode($taskItem['assigned_department']);
        }

        $taskValidator = Validator::make($inputTask, $taskRules);
        if ($taskValidator->fails()) {
            $errors = ValidateResponse::make($taskValidator);
            return $this->responseError($errors, 400);
        }
        return Task::create($inputTask);
    }

    public function addTasksByType($taskItem, $input, $userID, $companyID, $type, $typeID, $requestApproval = false)
    {
        //Handle to create task
        $newTask = $this->createTaskItem($taskItem, $input, $userID, $companyID, $type, $typeID);
        if (!$newTask) {
            return null;
        }

        if ($taskItem['assigned_company']) {
            $employees = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->where('users.company_id', $companyID)
                ->get();
            if (!empty($employees)) {
                foreach ($employees as $assignee) {
                    $this->createNewAssignee($assignee->user_id, $companyID, $newTask->id, $requestApproval);
                }
                $this->pushNotification($userID, $companyID, 2, $employees, 'task', $type, $typeID, $taskItem['name'], 'assigned');
            }
        } else {
            if (empty($taskItem['assigned_employee']) && empty($taskItem['assigned_department'])) {
                $this->createNewAssignee($input['responsible_id'], $companyID, $newTask->id, $requestApproval);
            } else {
                $assigned_employee = $taskItem['assigned_employee'];
                $department_employees = Employee::whereIn('department_id', $taskItem['assigned_department'])->pluck('user_id')->toArray();
                $assigneeDiff = array_diff($department_employees, $assigned_employee);
                $assignees = array_merge($assigneeDiff, $assigned_employee);
                if (!empty($assignees)) {
                    foreach ($assignees as $assignee) {
                        $this->createNewAssignee($assignee, $companyID, $newTask['id'], $requestApproval);
                        //                                    $email = $newTaskAssignee->user->email;
                        //                                    $data = array(
                        //                                        'name' => $newTaskAssignee->user->first_name . ' ' . $newTaskAssignee->user->last_name,
                        //                                        'assigned_by' => $user->first_name . ' ' . $user->last_name,
                        //                                        'deadline' => $newTask->deadline,
                        //                                        'url' => config('app.site_url') . '/employee/tasks',
                        //                                    );
                        //                                    Mail::to($email)->send(new AssignedTaskMail($data));
                    }
                    $this->pushNotification($userID, $companyID, 2, $assignees, 'task', $type, $typeID, $taskItem['name'], 'assigned');
                }
            }
        }
        return $newTask;
    }

    //    public function assignTaskToCompany($userID, $companyID, $newTaskID) {
    //        $employees = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
    //            ->where('users.company_id', $companyID)
    //            ->get();
    //        if (!empty($employees)) {
    //            foreach ($employees as $assignee) {
    //                $this->createNewAssignee($assignee->user_id, $companyID, $newTaskID);
    //            }
    //            $this->pushNotification($userID, $companyID, 2, $employees, 'task');
    //        }
    //    }

    public function createNewAssignee($assignee, $companyID, $newTaskID, $requestApproval = false)
    {
        $rulesTaskAssignee = TaskAssignee::$rules;
        $inputTaskAssignee['company_id'] = $companyID;
        $inputTaskAssignee['task_id'] = $newTaskID;
        $inputTaskAssignee['user_id'] = $assignee;
        if ($requestApproval) {
            $inputTaskAssignee['status'] = 4; //4: pending
        }

        $validatorTaskAssignee = Validator::make($inputTaskAssignee, $rulesTaskAssignee);
        if ($validatorTaskAssignee->fails()) {
            $errors = ValidateResponse::make($validatorTaskAssignee);
            return $this->responseError($errors, 400);
        }
        return TaskAssignee::create($inputTaskAssignee);
    }

    public function updateTaskByType($typeOfTask, $input, $oldTasks, $task, $user, $dataFromType, $resetTask, $historyArray)
    {
        if (!empty($oldTasks)) {
            foreach ($oldTasks as $oldTask) {
                $key = array_search($oldTask, array_column($task, 'id'));
                if ($typeOfTask == 'Goal' && $dataFromType['is_template']) {
                    //                    unset($task[$key]['responsible_id']);
                    //                    unset($task[$key]['deadline']);
                    //                    unset($task[$key]['start_time']);
                    $task[$key]['responsible_id'] = null;
                    $task[$key]['start_time'] = null;
                    $task[$key]['deadline'] = null;
                    $task[$key]['recurring'] = null;
                    $task[$key]['assigned_company'] = false;
                    unset($task[$key]['assigned_employee']);
                    unset($task[$key]['assigned_department']);
                }
                if ($key > -1) {
                    //update task
                    $this->updateTaskInfo($user['id'], $oldTask, $task[$key]);
                    $task[$key]['updated'] = true;
                    $company_employees = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                        ->where('users.company_id', $user['company_id'])
                        ->pluck('employees.user_id')->toArray();
                    $assigned_employees = TaskAssignee::where('task_id', $task[$key]['id'])->pluck('user_id')->toArray();

                    if ($task[$key]['assigned_company']) {
                        $this->checkAssignees($task[$key], $company_employees, $user, $assigned_employees);
                    } else {
                        // not choose BOTH
                        if (empty($task[$key]['assigned_employee']) && empty($task[$key]['assigned_department'])) {
                            $assignees = TaskAssignee::where('task_id', $task[$key]['id'])
                                ->where('user_id', '<>', $task[$key]['responsible_id'])->get();
                            foreach ($assignees as $assignee) {
                                TaskAssignee::destroy($assignee['id']);
                            }
                        } // choose DEPARTMENT
                        else if (empty($task[$key]['assigned_employee']) && !empty($task[$key]['assigned_department'])) {
                            $department_employees = Employee::whereIn('department_id', $task[$key]['assigned_department'])->pluck('user_id')->toArray();
                            $assigneeDiff = array_diff($assigned_employees, $department_employees);
                            foreach ($assigneeDiff as $assignee) {
                                TaskAssignee::where('task_id', $task[$key]['id'])->where('user_id', $assignee)->delete();
                            }
                            $this->checkAssignees($task[$key], $department_employees, $user, $assigned_employees);
                        } // choose EMPLOYEE
                        else if (!empty($task[$key]['assigned_employee']) && empty($task[$key]['assigned_department'])) {
                            $assigneeDiff = array_diff($assigned_employees, $task[$key]['assigned_employee']);
                            foreach ($assigneeDiff as $assignee) {
                                TaskAssignee::where('task_id', $task[$key]['id'])->where('user_id', $assignee)->delete();
                            }
                            $this->checkAssignees($task[$key], $task[$key]['assigned_employee'], $user, $assigned_employees);
                        } // choose BOTH
                        else if (!empty($task[$key]['assigned_employee']) && !empty($task[$key]['assigned_department'])) {
                            $department_employees = Employee::whereIn('department_id', $task[$key]['assigned_department'])->pluck('user_id')->toArray();
                            $assigneeDiff = array_diff($department_employees, $task[$key]['assigned_employee']);
                            $current_assignees = array_merge($assigneeDiff, $task[$key]['assigned_employee']);
                            $deleteDiff = array_diff($assigned_employees, $current_assignees);
                            foreach ($deleteDiff as $assignee) {
                                TaskAssignee::where('task_id', $task[$key]['id'])->where('user_id', $assignee)->delete();
                            }
                            $this->checkAssignees($task[$key], $current_assignees, $user, $assigned_employees);
                        }
                    }
                    $final_assignees = TaskAssignee::where('task_id', $task[$key]['id'])->get();
                    foreach ($final_assignees as $final_assignee) {
                        if ($resetTask) {
                            $data['status'] = 1;
                        } else {
                            $data['status'] = $this->updateTaskStatus($user['id'], $final_assignee, $task[$key]);
                        }
                    }
                    //                    if (!($typeOfTask == 'Goal' && !empty($dataFromType['is_template']) && $dataFromType['is_template'])) {
                    //                        $oldTaskAssignees = $oldTask->task_assignees;
                    //                        foreach ($oldTaskAssignees as $taskAssignee) {
                    //                            $keyTaskAssignee = array_search($taskAssignee['user_id'], $task[$key]['assigned_employee']);
                    //                            if ($keyTaskAssignee > -1) {
                    //                                $data = array();
                    //                                if ($resetTask) {
                    //                                    $data['status'] = 1;
                    //                                } else {
                    //                                    $data['status'] = $this->updateTaskStatus($user['id'], $taskAssignee, $task[$key]);
                    //                                }
                    //                                $taskAssignee->update($data);
                    //                                unset($task[$key]['assigned_employee'][$keyTaskAssignee]);
                    //                            } else {
                    //                                TaskAssignee::destroy($taskAssignee->id);
                    //                            }
                    //                        }
                    //                        foreach ($task[$key]['assigned_employee'] as $newAssignee) {
                    //                            $this->createNewAssignee($newAssignee, $user['company_id'], $task[$key]['id']);
                    //                        }
                    //                    }
                } else {
                    //delete task
                    $taskData = Task::where("id", $oldTask)->first();
                    if (empty($taskData)) {
                        return $this->responseException('Not found task', 404);
                    }
                    Task::destroy($oldTask);
                }
            }
        }
        if (!empty($task)) {
            foreach ($task as $taskItem) {
                if (!isset($taskItem['updated'])) {
                    if ($typeOfTask == 'Goal') {
                        if ($dataFromType['is_template']) {
                            $input['responsible_id'] = null;
                            $input['deadline'] = null;
                            $input['start_time'] = null;
                            $taskItem['assigned_company'] = false;
                            unset($taskItem['assigned_employee']);
                            unset($taskItem['assigned_department']);
                        }
                    }
                    $this->addTasksByType($taskItem, $input, $user['id'], $user['company_id'], $typeOfTask, $dataFromType['id']);

                    //                    if ($typeOfTask == 'Goal') {
                    //                        if (!$dataFromType['is_template']) {
                    //                            $this->addTasksByType($taskItem, $input, $user['id'], $user['company_id'], $typeOfTask, $dataFromType['id']);
                    //                        }
                    //                    } else {
                    //                        $this->addTasksByType($taskItem, $input, $user['id'], $user['company_id'], $typeOfTask, $dataFromType['id']);
                    //                    }
                }
            }
        }
    }

    public function checkAssignees($task, $input_assignees, $user, $assigned_employees)
    {
        $diff = array_diff($input_assignees, $assigned_employees);
        if (!empty($diff)) {
            foreach ($diff as $assignee) {
                $this->createNewAssignee($assignee, $user['company_id'], $task['id']);
            }
            //            $this->pushNotification($user['id'], $user['company_id'], 2, $diff, 'task', $task['type'], $task['type_id']);
        }
    }

    public function updateTaskInfo($userId, $oldTask, $taskKey)
    {
        //        $taskData = Task::where("id", $oldTask)->first();
        $taskData = Task::find($oldTask);
        if (empty($taskData)) {
            return $this->responseException('Not found task', 404);
        }

        $historyArray = json_decode($taskData->update_history);

        if ($taskData['name'] != $taskKey['name']) {
            $inputTask['name'] = $taskKey['name'];
            $historyArray = $this->setUpdateHistory('updated', $userId, $historyArray, 'name', 'task name', $taskData['name'], $taskKey['name']);
        }
        $inputTask['company_id'] = $taskKey['company_id'];
        $inputTask['added_by'] = $taskKey['added_by'];
        $inputTask['industry_id'] = $taskKey['industry_id'];
        $inputTask['department_id'] = $taskKey['department_id'];
        $inputTask['job_title_id'] = $taskKey['job_title_id'];
        //        $inputTask['status'] = $taskKey['status'];

        if ($taskData['start_time'] != $taskKey['start_time']) {
            $inputTask['start_time'] = $taskKey['start_time'];
            $historyArray = $this->setUpdateHistory('updated', $userId, $historyArray, 'start_time', 'start date', $taskData['start_time'], $taskKey['start_time']);
        }
        if ($taskData['deadline'] != $taskKey['deadline']) {
            $inputTask['deadline'] = $taskKey['deadline'];
            $historyArray = $this->setUpdateHistory('updated', $userId, $historyArray, 'deadline', 'due date', $taskData['deadline'], $taskKey['deadline']);
        }
        if ($taskData['responsible_id'] != $taskKey['responsible_id']) {
            $inputTask['responsible_id'] = $taskKey['responsible_id'];
            $historyArray = $this->setUpdateHistory('updated', $userId, $historyArray, 'responsible_id', 'responsible person', $taskData['responsible_id'], $taskKey['responsible_id']);
        }
        $inputTask['assigned_company'] = $taskKey['assigned_company'];
        if (!$taskKey['assigned_company'] && !empty($taskKey['assigned_employee'])) {
            $inputTask['assigned_employee'] = json_encode($taskKey['assigned_employee']);
        } else {
            $inputTask['assigned_employee'] = null;
        }
        if (!$taskKey['assigned_company'] && !empty($taskKey['assigned_department'])) {
            $inputTask['assigned_department'] = json_encode($taskKey['assigned_department']);
        } else {
            $inputTask['assigned_department'] = null;
        }

        $inputTask['update_history'] = json_encode($historyArray);

        $taskRules = Task::$updateRules;
        $taskValidator = Validator::make($inputTask, $taskRules);
        if ($taskValidator->fails()) {
            $errors = ValidateResponse::make($taskValidator);
            return $this->responseError($errors, 400);
        }
        $taskData->update($inputTask);
    }

    public function updateTaskStatus($userId, $taskAssignee, $taskKey)
    {
        $statusUpdated = $taskAssignee['status'];
        if ($userId == $taskAssignee['user_id'] && $taskKey['id'] == $taskAssignee['task_id']) {
            if (!empty($taskKey['checkDone']) && $taskKey['checkDone']) {
                $status = 3;
            } else {
                $status = 2;
            }
            $statusUpdated = $status;
        }
        return $statusUpdated;
    }

    public function processTaskByType($typeOfTask, $oldTasks, $task, $user)
    {
        foreach ($oldTasks as $oldTask) {
            $key = array_search($oldTask['id'], array_column($task, 'id'));
            if ($key > -1) {
                //update task
                $taskData = $this->updateTaskInfo($user['id'], $oldTask, $task[$key]);
                $task[$key]['updated'] = true;

                $oldTaskAssignees = $oldTask->task_assignees;
                foreach ($oldTaskAssignees as $taskAssignee) {
                    $keyTaskAssignee = array_search($taskAssignee['user_id'], $task[$key]['taskAssignees']);
                    if ($keyTaskAssignee > -1) {
                        $keyResponsible = array_search($taskAssignee['user_id'], $task[$key]['responsiblePerson']);
                        $data = array();
                        if ($keyResponsible > -1) {
                            $data['status'] = $this->updateTaskStatus($user['id'], $taskAssignee, $task[$key]);
                        }
                        $taskAssignee->update($data);
                    }
                }
            }
        }
    }

    public function checkSuperAdmin($user)
    {
        if ($user->role->level == 0 && is_null($user->role->company_id)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkCustomerServiceAdmin($user)
    {
        if ($user->role->name == 'Customer service' && is_null($user->role->company_id) && $user->role->level == 4) {
            return true;
        } else {
            return false;
        }
    }

    public function checkCompanyAdmin($user)
    {
        if ($user->role->name == 'Company admin' && is_null($user->role->company_id) && $user->role->level == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getAuthorizedUser($function, $permissionName, $functionDetail, ...$options)
    { //$options = []
        if (!$user = JWTAuth::parseToken()->authenticate()) {
            return null;
        } elseif (!$function || !$options || !$permissionName) {
            return null;
        } else {
            $user->company_id = $user->company_id ?: 0;
            $user->full_name = $user->first_name . ' ' . $user->last_name;
            $user->editPermission = false;
            if ($user->role->level == 0 || $user->role->level == 4) { //check super admin
                $user->editPermission = true;
                $user->filterBy = 'super admin';
                return $user;
            } elseif ($user->role->level == 1) { //check company admin
                $user->editPermission = true;
                $user->filterBy = 'company admin';
                return $user;
            } else {
                $permissions = $user->permissions->permission;
                $permissions = json_decode($permissions);
                if (empty($permissions)) {
                    return null;
                }
                $userDepartment = $user->employee->department_id;
                $user->department = $userDepartment;
                $user->jobTitle = $user->employee->job_title_id;
                $objectDepartment = '';
                $objectProject = '';
                $objectJobTitle = '';
                $objectType = '';
                $objectItem = '';
                $objectAddedBy = '';
                $keyObjectInfo = '';
                $validDepartment = [];
                $objectPublic = '';

                array_push($validDepartment, $userDepartment);
                $isSuper = $user->permissions->is_super;
                if ($isSuper) {
                    $departments = Department::get();
                    $subDepartments = $this->getSubDepartment($departments, $userDepartment);
                    if (!empty($subDepartments)) {
                        foreach ($subDepartments as $department) {
                            array_push($validDepartment, $department->id);
                        }
                    }
                }
                $user->isSuper = $isSuper;
                $user->validDepartment = $validDepartment;

                $security = Security::where('company_id', $user->company_id)
                    ->where('object_type', $function)
                    ->where(function ($query) use ($user, $validDepartment) {
                        $query->whereJsonContains('employee_array', $user->id)
                            ->orWhereJsonContains('department_array', $validDepartment)
                            ->orWhere('is_public', 1);
                    })
                    ->select('object_type', 'object_id', 'is_public')
                    ->get();
                $user->security = $security;

                $keyFunction = array_search($function, array_column($permissions, 'name')); //function: goal, routine,...
                if ($keyFunction > -1) {
                    if (!in_array(1, $options)) {
                        //check objectInfo: ['name' => 'objectInfo', 'objectType'=> '', 'objectItem'=> '']
                        $keyObjectInfo = array_search('objectInfo', array_column($options, 'name'));
                        if ($keyObjectInfo > -1) {
                            //                            $objectType = $options[$keyObjectInfo]['objectType'] ? $options[$keyObjectInfo]['objectType'] : '';
                            //                            $objectItem = $options[$keyObjectInfo]['objectItem'] ? $options[$keyObjectInfo]['objectItem'] : '';
                            if ($options[$keyObjectInfo]['objectType']) {
                                $objectType = $options[$keyObjectInfo]['objectType'];
                            } else {
                                $objectType = '';
                            }

                            if ($options[$keyObjectInfo]['objectItem']) {
                                $objectItem = $options[$keyObjectInfo]['objectItem'];
                            } else {
                                $objectItem = '';
                            }

                            if (!empty($objectItem->department_id)) {
                                $objectDepartment = $objectItem->department_id;
                            }

                            if (!empty($objectItem->job_title_id)) {
                                $objectJobTitle = $objectItem->job_title_id;
                            }

                            if (!empty($objectItem->added_by)) {
                                $objectAddedBy = $objectItem->added_by;
                            }

                            //                            if (!empty($objectItem->is_public)) {
                            //                                $objectPublic = $objectItem->is_public;
                            //                            }

                            $objectPublic = $this->checkSecurityOfObject($function, $objectItem, $user->security);
                        }
                    }

                    $keyUserPermission = array_search($permissionName, array_column($permissions[$keyFunction]->userPermission, 'name'));
                    if ($keyUserPermission > -1) {
                        $type = $permissions[$keyFunction]->userPermission[$keyUserPermission]->type;
                        $value = $permissions[$keyFunction]->userPermission[$keyUserPermission]->value;
                        $apply = $permissions[$keyFunction]->userPermission[$keyUserPermission]->apply;
                       
                        if ($type == 'boolean') {
                            if ($apply == 'personal') {
                                $checkPersonalPermission = $this->checkPersonalPermission($user->id, $isSuper, $objectPublic, $permissionName, $functionDetail, $validDepartment, $objectType, $objectItem, $objectAddedBy, $objectDepartment);
                                if ($value && $checkPersonalPermission) {  
                                    $user->filterBy = 'personal';   
                                    return $user;
                                }
                            } elseif ($apply == 'group') {
                                if ($value) {
                                    $checkGroupPermission = $this->checkGroupPermission($user->id, $objectPublic, $permissionName, $functionDetail, $validDepartment, $objectType, $objectItem, $objectAddedBy, $objectDepartment);
                                    if ($checkGroupPermission) {
                                        $user->filterBy = 'group';
                                        if (!empty($objectItem->editPermission) && $objectItem->editPermission) {
                                            $user->editPermission = true;
                                        }
                                        return $user;
                                    }
                                } else {
                                    //                                    $exceptFunction = ['goal', 'routine', 'instruction', 'contact', 'checklist'];
                                    //                                    if (!(($permissionName == 'basic' || $permissionName == 'resource')
                                    //                                        && in_array($function, $exceptFunction))) {
                                    //                                    }
                                    $checkPersonalPermission = $this->checkPersonalPermission($user->id, $isSuper, $objectPublic, $permissionName, $functionDetail, $validDepartment, $objectType, $objectItem, $objectAddedBy, $objectDepartment);
                                    if ($checkPersonalPermission) {
                                        if (!empty($objectItem->editPermission) && $objectItem->editPermission) {
                                            $user->editPermission = true;
                                        }
                                        $user->filterBy = 'personal';
                                        return $user;
                                    }
                                }
                            } elseif ($apply == 'company') {
                                if ($value) {
                                    $user->filterBy = 'company';
                                    return $user;
                                }
                            }
                        }
                    }
                }
            }
            return null;
        }
    }

    public function checkSecurityOfObject($function, $item, $validSecurity)
    {
        if (!empty($validSecurity)) {
            $keyId = array_search($item->id, array_column($validSecurity->toArray(), 'object_id'));
            $keyType = array_search($function, array_column($validSecurity->toArray(), 'object_type'));
            if ($keyId == $keyType && $keyId > -1) {
                return true;
            }
        }
        return false;
    }

    public function getSubDepartment($departments, $parent_id = null)
    {
        $result = array();
        foreach ($departments as $key => $item) {
            if ($item['parent_id'] == $parent_id) {
                array_push($result, $item);

                $temp = $this->getSubDepartment($departments, $item['id']);
                $result = array_merge($result, $temp);
            }
        }
        return $result;
    }

    public function checkValidGroup($objectDepartment, $objectJobTitle, $objectProject, $departmentDefineGroup, $jobTitleDefineGroup)
    {
        if (!$objectDepartment && !$objectJobTitle && !$objectProject) {
            return false;
        }
        $connectToDepartment = false;
        $connectToJobTitle = false;
        $connectToProject = false;

        if ($objectDepartment && in_array($objectDepartment, $departmentDefineGroup)) {
            $connectToDepartment = true;
        }

        if ($objectJobTitle && in_array($objectJobTitle, $jobTitleDefineGroup)) {
            $connectToJobTitle = true;
        }

        if (($objectDepartment && !$connectToDepartment)
            || ($objectJobTitle && !$connectToJobTitle)
        ) {
            return false;
        }

        return true;
    }

    public function checkAssignee($userId, $objectType, $objectItem)
    {
        if (!$userId || !$objectType || !$objectItem) return false;

        if ($objectType == 'task' && array_search($userId, array_column($objectItem->task_assignees->toArray(), 'user_id')) > -1) {
            return true;
        } elseif (in_array($objectType, ['deviation', 'risk analysis', 'report checklist']) && !empty($objectItem->tasks)) {
            foreach ($objectItem->tasks as $task) {
                $keyAssignee = array_search($userId, array_column($task->task_assignees->toArray(), 'user_id'));
                if ($keyAssignee > -1) {
                    return true;
                }
            }
        } elseif ($objectType == 'goal' && !empty($objectItem->sub_goals)) {
            foreach ($objectItem->sub_goals as $sub_goal) {
                if (!empty($sub_goal->tasks)) {
                    foreach ($sub_goal->tasks as $task) {
                        $keyAssignee = array_search($userId, array_column($task->task_assignees->toArray(), 'user_id'));
                        if ($keyAssignee > -1) {
                            return true;
                        }
                    }
                }
            }
        } elseif ($objectType == 'routine') {
            $attendingEmps = json_decode($objectItem->attending_emps);
            if (!empty($attendingEmps) && in_array($userId, $attendingEmps)) {
                return true;
            }
        }

        return false;
    }

    public function checkResponsiblePerson($userId, $objectType, $objectItem)
    {
        if (!$userId || !$objectType || !$objectItem) return false;

        if (($objectType == 'task' || $objectType == 'goal' || $objectType == 'routine') && $objectItem->responsible_id == $userId) {
            return true;
        } elseif (in_array($objectType, ['deviation'])) {
            $exist = DB::table('objects')->select('id','added_by')->where('source_id',$objectItem->id)->where('type','deviation')->first();
            if(!empty($exist)){ 
                $responsible_emp = Responsible::where('object_id', $exist->id)->first();  
                if(!empty($exist->employee_array) && in_array($userId,json_decode($exist->employee_array)) ){ 
                    return true;
                }else  if(!empty($responsible_emp->employee_array) &&   is_array(json_decode($responsible_emp->employee_array)) && in_array($userId,json_decode($responsible_emp->employee_array)) ){
                    return true;
                }else  if(!empty($exist->added_by) && $userId == $exist->added_by ){
                    return true;
                }
            } 
        } elseif (in_array($objectType, [ 'risk analysis', 'report checklist'])) {
            if (
                $objectItem->responsible == $userId
                || (!empty($objectItem->tasks)
                    && array_search($userId, array_column($objectItem->tasks->toArray(), 'responsible_id')) > -1)
            ) {
                return true;
            }
        }
        //        elseif ($objectType == 'goal' && !empty($objectItem->sub_goals)) {
        //            foreach ($objectItem->sub_goals as $sub_goal) {
        //                if (!empty($sub_goal->tasks)) {
        //                    $keyResponsible = array_search($userId, array_column($sub_goal->tasks->toArray(), 'responsible_id'));
        //                    if ($keyResponsible > -1) {
        //                        return true;
        //                    }
        //                }
        //            }
        //        }
        elseif ($objectType == 'document') {
            //check assigned employee in Reminder
            if ($objectItem->renewed_employee_array) {
                $reminderArray = explode(',', $objectItem->renewed_employee_array);
                if (in_array($userId, $reminderArray)) {
                    return true;
                }
            }
        }
    }

    public function checkPersonalPermission($userId, $isSuper, $objectPublic, $permissionName, $functionDetail, $validDepartment, $objectType, $objectItem, $objectAddedBy, $objectDepartment)
    {
        if ($functionDetail == 'index' || $functionDetail == 'store' || ($objectAddedBy && ($objectAddedBy == $userId))) {
            $checkOwner = true;
        } else {
            $checkOwner = false;
        }
        if ($permissionName == 'view' || $permissionName == 'detail') {
            //            || ($isSuper && $objectAddedBy && in_array($this->getDepartmentOfObject($objectAddedBy), $validDepartment))
            if ($permissionName == 'detail' && $checkOwner) {
                $objectItem->editPermission = true;
            }
            if (
                $checkOwner
                || $objectPublic
                || $this->checkAssignee($userId, $objectType, $objectItem)
                || ($isSuper && $objectDepartment && in_array($objectDepartment, $validDepartment))
            ) {
                return true;
            }
        } elseif (($permissionName == 'basic') && $checkOwner) {
            return true;
        } elseif ($permissionName == 'process' && $this->checkAssignee($userId, $objectType, $objectItem)) {
            return true;
        }
        return false;
    }

    public function checkGroupPermission($userId, $objectPublic, $permissionName, $functionDetail, $validDepartment, $objectType, $objectItem, $objectAddedBy, $objectDepartment)
    {
        if ($functionDetail == 'index' || $functionDetail == 'store' || ($objectAddedBy && ($objectAddedBy == $userId))) {
            $checkOwner = true;
        } else {
            $checkOwner = false;
        }
        //        if ($objectType == 'task' && $objectItem && $objectItem->responsible_id == $userId) {
        //            $checkResponsiblePerson = true;
        //        } else {
        //            $checkResponsiblePerson = false;
        //        }
        if ($permissionName == 'view' || $permissionName == 'detail') {
            if ($permissionName == 'detail') {
                $objectItem->editPermission = $this->checkEditGroupPermissions($objectType, $checkOwner, $objectItem, $validDepartment, $objectDepartment, $userId);
            }
            if ($objectType == 'job title') {
                if ($checkOwner || $this->checkValidJobTitle($objectItem->department, $validDepartment)) {
                    return true;
                }
            } elseif ($objectType == 'user permission') {
                if ($checkOwner || $this->checkValidJobTitle($objectItem->job_title->department, $validDepartment)) {
                    return true;
                }
            } else {
                if (
                    $checkOwner
                    || $objectPublic
                    || $this->checkResponsiblePerson($userId, $objectType, $objectItem)
                    || $this->checkAssignee($userId, $objectType, $objectItem)
                    || ($objectDepartment && in_array($objectDepartment, $validDepartment))
                ) {
                    return true;
                }
            }
        } elseif ($permissionName == 'basic' || $permissionName == 'resource') {
            return $this->checkEditGroupPermissions($objectType, $checkOwner, $objectItem, $validDepartment, $objectDepartment, $userId);
        } elseif ($permissionName == 'process') {
            if (
                $this->checkResponsiblePerson($userId, $objectType, $objectItem)
                || ($objectDepartment && in_array($objectDepartment, $validDepartment))
            ) {
                return true;
            }
        }
        return false;
    }

    public function checkEditGroupPermissions($objectType, $checkOwner, $objectItem, $validDepartment, $objectDepartment, $userId)
    {
        if ($objectType == 'job title') {
            if ($checkOwner || $this->checkValidJobTitle($objectItem->department, $validDepartment)) {
                return true;
            }
        } else {
            if (
                $checkOwner
                || ($objectDepartment && in_array($objectDepartment, $validDepartment))
                || $this->checkResponsiblePerson($userId, $objectType, $objectItem)
            ) {
                return true;
            }
        }
        return false;
    }

    public function checkValidJobTitle($departments, $validDepartment)
    {
        $departmentsArray = json_decode($departments);
        if (!empty($departmentsArray)) {
            foreach ($departmentsArray as $departmentId) {
                if (in_array($departmentId, $validDepartment)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function showResponsibleName($list)
    {
        $list_responsible_users = '';
        foreach ($list as $responsible) {
            $userInfo = User::find($responsible);
            $username = $userInfo->first_name . ' ' . $userInfo->last_name . ', ';
            $list_responsible_users .= $username;
        }
        $list_responsible_users = trim($list_responsible_users);
        $list_responsible_users = substr($list_responsible_users, 0, strlen($list_responsible_users) - 1);
        return $list_responsible_users;
    }

    public function filterViewList($function, $user, $filterBy, $list, $orderBy = null, $limit = null)
    {
        if (!$function || !$user || !$filterBy || !$list) {
            return null;
        }

        $result = [];
        if ($filterBy == 'super admin' || $filterBy == 'company admin') {
            //            $list = $list->toArray();
            if ($orderBy && $limit) {
                foreach ($list as $item) {
                    if (empty($item->is_template) || $item->is_template == 0) {
                        array_push($result, $item);
                    }
                }
                if ($orderBy == 'latest') {
                    $keys = array_column($result, 'created_at');

                    array_multisort($keys, SORT_DESC, $result);
                }
                array_splice($result, (int)$limit);

                return $result;
            } else {
                return $list;
            }
        }

        $userId = $user->id;
        $isSuper = $user->isSuper;
        $validDepartment = $user->validDepartment;
        $validSecurity = $user->security;
        //        $userJobTitle = $user->jobTitle;
        foreach ($list as $item) {
            if ($filterBy == 'company') {
                if ((!$orderBy || !$limit) || (empty($item->is_template) || $item->is_template == 0)) {
                    array_push($result, $item);
                }
            } elseif ($filterBy == 'group' || $filterBy == 'personal') {
                if ((!$orderBy || !$limit) || (empty($item->is_template) || $item->is_template == 0)) { 
                    if ($this->checkViewListPersonal($filterBy, $function, $userId, $isSuper, $validDepartment, $item, $validSecurity)) {
                        array_push($result, $item);
                    }
                }
            }
        }


        if ($orderBy && $orderBy == 'latest') {
            $keys = array_column($result, 'created_at');

            array_multisort($keys, SORT_DESC, $result);
        }

        if ($limit) {
            array_splice($result, (int)$limit);
        }

        return $result;
    }

    public function getOrderByAndLimitList($list, $orderBy = null, $limit = null)
    {
        if (!empty($list)) return false;

        if ($orderBy && $orderBy == 'latest') {
            $keys = array_column($list, 'created_at');

            array_multisort($keys, SORT_DESC, $list);
        }

        if ($limit) {
            array_splice($list, (int)$limit);
        }

        return $list;
    }

    public function checkViewListPersonal($filterBy, $function, $userId, $isSuper, $validDepartment, $item, $validSecurity)
    {
        //        $checkOwner = false;
        //        $checkPublic = false;
        //        $checkAssignee = false;
        //        $checkConnectTo = false;

        if ($item->added_by && $item->added_by == $userId) {
            return true;
        }
        //        if ($item->is_public) {
        //            return true;
        //        }

        //        if (!empty($validSecurity)) {
        //            $keyId = array_search($item->id, array_column($validSecurity->toArray(), 'object_id'));
        //            $keyType = array_search($function, array_column($validSecurity->toArray(), 'object_type'));
        //            if ($keyId == $keyType && $keyId > -1) {
        //                return true;
        //            }
        //        }

        if ($this->checkSecurityOfObject($function, $item, $validSecurity)) {
            return true;
        }

        if (($isSuper || $filterBy == 'group') && ($item->department_id && in_array($item->department_id, $validDepartment))) {
            return true;
        }

        //        if ($function == 'task') {
        //            $keyAssignee = array_search($userId, array_column($item->task_assignees->toArray(), 'user_id'));
        //            if ($keyAssignee > -1) {
        //                return true;
        //            }
        //        }
        //
        //        if (!empty($item->tasks)) {
        //            foreach ($item->tasks as $task) {
        //                $keyAssignee = array_search($userId, array_column($task->task_assignees->toArray(), 'user_id'));
        //                if ($keyAssignee > -1) {
        //                    return true;
        //                }
        //            }
        //        }

        if ($this->checkAssignee($userId, $function, $item)) {
            return true;
        } 
        if ($this->checkResponsiblePerson($userId, $function, $item)) {
            return true;
        }
        return false;
    }

    public function getDepartmentOfObject($objectAddedBy)
    {
        $employee = Employee::where('user_id', $objectAddedBy)->first();
        if (empty($employee)) {
            return null;
        }
        return $employee->department_id;
    }

    public function moveToRepository($userId, $companyId, $permanentDeletion, $objectType, $objectId, $objectName, $attachmentObject = null)
    {
        if (!$userId || !$objectId || !$objectType || !$objectName) {
            return false;
        }

        if ($permanentDeletion) {
            if (!$companyId) {
                $intervalSetting = IntervalSetting::whereNull('company_id')->where('type', 'repository')->first();
            } else {
                $intervalSetting = IntervalSetting::where('company_id', $companyId)->where('type', 'repository')->first();
            }

            $today = new \DateTime('now');
            $year = '';
            $month = '';
            $day = '';
            if ($intervalSetting->year) {
                $year = $intervalSetting->year . 'Y';
            }

            if ($intervalSetting->month) {
                $month = $intervalSetting->month . 'M';
            }

            if ($intervalSetting->day) {
                $day = $intervalSetting->day . 'D';
            }

            $dateOfPermanentDeletion = $today->add(new \DateInterval('P' . $year . $month . $day));

            if ($attachmentObject) {
                $input['attachment_id'] = $attachmentObject->id;
                if ($attachmentObject->uri) {
                    $filePath = storage_path('app/uploads/' . $attachmentObject->uri);
                    if ($filePath) {
                        $path_parts = pathinfo($filePath);
                        $file_name =  str_replace('documents/', '', $attachmentObject->uri);

                        $zip = new \ZipArchive();
                        $zip->open(storage_path('app/uploads/documents/' . $path_parts['filename'] . '.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                        $zip->addFile($filePath, $file_name);
                        $zip->close();

                        \File::delete($filePath);

                        $input['attachment_uri'] = $attachmentObject->uri;
                        $file_size = filesize(storage_path('app/uploads/documents/' . $path_parts['filename'] . '.zip'));
                        $input['attachment_size'] = round($file_size / 1024, 2); //convert byte to KB
                    }
                }
                $attachmentObject->update(['delete_status' => 1]);
            }
            $input['date_of_permanent_deletion'] = $dateOfPermanentDeletion;
        }

        if ($companyId) {
            $input['company_id'] = $companyId;
        }
        $input['added_by'] = $userId;
        $input['object_type'] = $objectType;
        $input['object_id'] = $objectId;
        $input['object_name'] = $objectName;

        $rules = Repository::$rules;
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $newRepository = Repository::create($input);

        return true;
    }

    public function moveOutRepository($repository)
    {
        if (!$repository) {
            return false;
        }
        $objectType = $repository->object_type;
        $objectId = $repository->object_id;
        $attachmentId = $repository->attachment_id;
        $attachmentUri = $repository->attachment_uri;
        $permanentDeletion = $repository->date_of_permanent_deletion;

        if ($attachmentId) {
            if ($attachmentUri) {
                $path_parts = pathinfo($attachmentUri);

                $filePath = storage_path('app/uploads/documents/' . $path_parts['filename'] . '.zip');
                if ($filePath) {
                    $zip = new \ZipArchive();
                    $zip->open($filePath);
                    $zip->extractTo(storage_path('app/uploads/documents/'));
                    $zip->close();

                    \File::delete($filePath);
                }
            }
            //            $attachment = Document::find($attachmentId);
            $attachment = DocumentNew::find($attachmentId);
            $attachment->update(['delete_status' => 0]);
        }

        if ($objectType == 'Risk element') {
            $object = RiskElementSource::find($objectId);
        } elseif ($objectType == 'Routine') {
            $object = Routine::find($objectId);
        } elseif ($objectType == 'Instruction') {
            $object = Instruction::find($objectId);
        } elseif ($objectType == 'Checklist') {
            $object = Checklist::find($objectId);
        } elseif ($objectType == 'Goal') {
            $object = Goal::find($objectId);
        } elseif ($objectType == 'Document') {
            //            $object = Document::find($objectId);
            $object = DocumentNew::find($objectId);
        } elseif ($objectType == 'Category') {
            $object = Category::find($objectId);
        } elseif ($objectType == 'Department') {
            $object = Department::find($objectId);
        } elseif ($objectType == 'Job title') {
            $object = JobTitle::find($objectId);
        } elseif ($objectType == 'Employee') {
            $object = Employee::where('user_id', $objectId)->first();
        } elseif ($objectType == 'Statement') {
            $object = Statement::find($objectId);
        } elseif ($objectType == 'Help center') {
            $object = HelpCenter::find($objectId);
        } elseif ($objectType == 'Help question') {
            $object = HelpCenterQuestion::find($objectId);
        }

        if (empty($object)) {
            return false;
        }
        if ($permanentDeletion) {
            $object->update(['delete_status' => 0]);
        } else {
            $object->update(['disable_status' => 0]);
        }
        return true;
    }

    public function filterRepositoryList($repositoryArray)
    {
        if (empty($repositoryArray)) {
            return null;
        }

        $result = [];
        $today = new \DateTime('now');

        foreach ($repositoryArray as $item) {
            $dateOfPermanentDeletion = new \DateTime($item->date_of_permanent_deletion);
            if ($today > $dateOfPermanentDeletion) {
                if ($item->object_type == 'risk element') {
                    $object = RiskElementSource::find($item->object_id);
                    if ($object) {
                        RiskElementSource::destroy($object->id);
                    }
                } elseif ($item->object_type == 'routine') {
                    $object = Routine::find($item->object_id);
                    if ($object) {
                        Routine::destroy($object->id);
                    }
                } elseif ($item->object_type == 'instruction') {
                    $object = Instruction::find($item->object_id);
                    if ($object) {
                        Instruction::destroy($object->id);
                    }
                } elseif ($item->object_type == 'checklist') {
                    $object = Checklist::find($item->object_id);
                    if ($object) {
                        Checklist::destroy($object->id);
                    }
                } elseif ($item->object_type == 'goal') {
                    $object = Goal::find($item->object_id);
                    if ($object) {
                        Goal::destroy($object->id);
                    }
                } elseif ($item->object_type == 'document') {
                    //                    $object = Document::find($item->object_id);
                    $object = DocumentNew::find($item->object_id);
                    if ($object) {
                        //                        Document::destroy($object->id);
                        DocumentNew::destroy($object->id);
                    }
                }

                if ($item->attachment_id) {
                    //                    $object = Document::find($item->attachment_id);
                    $object = DocumentNew::find($item->attachment_id);
                    if ($object) {
                        //                        Document::destroy($item->attachment_id);
                        DocumentNew::destroy($item->attachment_id);
                    }
                    if ($item->attachment_uri) {
                        $path_parts = pathinfo($item->attachment_uri);

                        $filePath = storage_path('app/uploads/documents/' . $path_parts['filename'] . '.zip');
                        if ($filePath) {
                            \File::delete($filePath);
                        }
                    }
                }

                $repository = Repository::find($item->id);
                $repository->update(['deleted_date' => $today]);
            } else {
                if ($item->attachment_uri) {
                    $baseUrl = config('app.app_url');
                    $path_parts = pathinfo($item->attachment_uri);
                    $item->file_name = $path_parts['filename'] . '.zip';
                    $item->attachment_url = $baseUrl . "/api/v1/uploads/documents/" .  $path_parts['filename'] . '.zip';
                }
                array_push($result, $item);
            }
        }

        return $result;
    }

    // Risk analysis
    public function createRiskAnalysis($inputRiskAnalysis, $userID, $companyID)
    {
        $rules = RiskAnalysis::$rules;
        $riskInput = $inputRiskAnalysis;
        $riskInput['added_by'] = $userID;
        $riskInput['company_id'] = $companyID;

        $validator = Validator::make($riskInput, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        return RiskAnalysis::create($riskInput);
    }

    // Risk element
    public function createRiskElement($element, $riskAnalysisID, $userID)
    {
        $rules = RiskElement::$rules;
        $element['risk_analysis_id'] = $riskAnalysisID;
        $element['added_by'] = $userID;
        $validator = Validator::make($element, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        return RiskElement::create($element);
    }

    public function sendActiveAccountEmail($user)
    {
        if ($user->status != 'pending') {
            return null;
        }

        $code = sha1(time() . $user->id);
        $today = new \DateTime('now');

        $dataVerificationCodeItem['company_id'] = @$user->company_id;
        $dataVerificationCodeItem['user_id'] = $user->id;
        $dataVerificationCodeItem['email'] = $user->email;
        $dataVerificationCodeItem['action'] = 'active account';
        $dataVerificationCodeItem['expired_time'] = $today->add(new \DateInterval('P30D')); //30 day
        $dataVerificationCodeItem['code'] = $code;

        VerificationCode::create($dataVerificationCodeItem);

        $data = ([
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'password' => str_replace(' ', '', $user->phone_number),
            'url' => config('app.site_url') . '/verify-email/' . $code,
        ]);
        try {
            Mail::to($user->email)->send(new WelcomeMail($data));
        } catch (\Exception $e) {
            Log::debug('Failed to send email: ', ['error' => $e]);
        }
    }

    public function setUpdateHistory($action, $userId, $historyArray, $field = '', $field_name = '', $old_content = '', $new_content = '')
    {
        if (!$field) {
            $field = 'object';
            $field_name = 'object';
        }

        $updateData = $this->updateUpdateHistory($field, $old_content, $new_content);

        $historyItem = [
            'action' => $action,
            'field' => $field,
            'field_name' => $field_name,
            'user_id' => $userId,
            'updated_at' => date_format(date_create(), 'd.m.Y, h:i A'),
            'old_content' => $updateData['old_content'],
            'new_content' => $updateData['new_content']
        ];
        array_push($historyArray, $historyItem);

        return $historyArray;
    }

    public function updateUpdateHistory($field, $old_content = '', $new_content = '')
    {
        $result = [
            'old_content' => '',
            'new_content' => '',
        ];
        $normalFieldArray = ['name', 'start_time', 'deadline', 'description'];
        if (in_array($field, $normalFieldArray)) {
            if ($old_content) {
                $result['old_content'] = $old_content;
            } else {
                $result['old_content'] = 'None';
            }
            if ($new_content) {
                $result['new_content'] = $new_content;
            } else {
                $result['new_content'] = 'None';
            }
        } elseif ($field == 'responsible_id') {
            $responsible = User::find($old_content);
            if ($responsible) {
                $result['old_content'] = $responsible->first_name . ' ' . $responsible->last_name;
            } else {
                $result['old_content'] = 'N/A';
            }

            $newResponsible = User::find($new_content);
            if ($newResponsible) {
                $result['new_content'] = $newResponsible->first_name . ' ' . $newResponsible->last_name;
            } else {
                $result['new_content'] = 'N/A';
            }
        } elseif ($field == 'is_public') {
            if ($new_content == 1) {
                $result['old_content'] = 'No';
                $result['new_content'] = 'Yes';
            } else {
                $result['old_content'] = 'Yes';
                $result['new_content'] = 'No';
            }
        } elseif ($field == 'job_title_id') {
            if ($old_content) {
                $jobTitle = JobTitle::find($old_content);
                if ($jobTitle) {
                    $result['old_content'] = $jobTitle->name;
                } else {
                    $result['old_content'] = 'N/A';
                }
            } else {
                $result['old_content'] = 'None';
            }
            if ($new_content) {
                $newJobTitle = JobTitle::find($new_content);
                if ($newJobTitle) {
                    $result['new_content'] = $newJobTitle->name;
                } else {
                    $result['new_content'] = 'N/A';
                }
            } else {
                $result['new_content'] = 'None';
            }
        } elseif ($field == 'department_id') {
            if ($old_content) {
                $department = Department::find($old_content);
                if ($department) {
                    $result['old_content'] = $department->name;
                } else {
                    $result['old_content'] = 'N/A';
                }
            } else {
                $result['old_content'] = 'None';
            }
            if ($new_content) {
                $newDepartment = Department::find($new_content);
                if ($newDepartment) {
                    $result['new_content'] = $newDepartment->name;
                } else {
                    $result['new_content'] = 'N/A';
                }
            } else {
                $result['new_content'] = 'None';
            }
        } else {
            if ($old_content) {
                $result['old_content'] = $old_content;
            } else {
                $result['old_content'] = 'None';
            }
            if ($new_content) {
                $result['new_content'] = $new_content;
            } else {
                $result['new_content'] = 'None';
            }
        }

        return $result;
    }

    public function getUpdateHistory($updateHistory)
    {
        $history = [];
        if (!empty($updateHistory)) {
            foreach ($updateHistory as $item) {
                $user = User::find($item->user_id);
                if (!$user) {
                    $userName = 'Unknown user';
                } else {
                    $userName = $user['first_name'] . ' ' . $user['last_name'];
                }
                if ($item->action == 'deleted' || $item->action == 'created') {
                    $history['title'] = '<b>' . $userName . '</b> ' . $item->action . ' the <b>' . $item->field_name . '</b> "' . $item->old_content . '" <i>' . $item->updated_at . '</i>';
                } else {
                    $history['title'] = '<b>' . $userName . '</b> ' . $item->action . ' the <b>' . $item->field_name . '</b> <i>' . $item->updated_at . '</i>';
                    $history['old_content'] = $item->old_content;
                    $history['new_content'] = $item->new_content;
                }
                $history['updated_at'] = $item->updated_at;

                array_push($history, $history);

                $history['title'] = '';
                $history['old_content'] = '';
                $history['new_content'] = '';
                $history['updated_at'] = '';
            }
        }
        return $history;
    }

    public function getPredefinedLinks($companyId)
    {
        $result = Statement::where('company_id', $companyId)
            ->where('delete_status', 0)
            ->get();
        if ($result) {
            return $this->responseSuccess($result);
        } else {
            return $this->responseSuccess([]);
        }
    }

    // table Security / Connect to
    // create Security
    public function createSecurityObject($object, $input)
    {
        if ($object['added_by'] > 1) {
            $inputSecurity['company_id'] = $object['company_id'];
        } 
        $inputSecurity['object_type'] = $input['object_type'];
        $inputSecurity['object_id'] = $object['id'];
        $inputSecurity['added_by'] = $object['added_by'];
        $inputSecurity['is_shared'] = $input['is_shared'];
        $inputSecurity['is_public'] = $input['is_public'];
        if (!empty($input['department_array'])) {
            $inputSecurity['department_array'] = json_encode($input['department_array']);
        }
        if (!empty($input['employee_array'])) {
            $inputSecurity['employee_array'] = json_encode($input['employee_array']);
        }
        if((isset($input['department_array']) && !empty($input['department_array'])) && gettype($input['department_array']) == 'string') { 
            $input['department_array'] = json_decode($input['department_array']);
        }
        if((isset($input['employee_array']) && !empty($input['employee_array'])) && gettype($input['employee_array']) == 'string') {
            $input['employee_array'] = json_decode($input['employee_array']);
        }
        Security::create($inputSecurity);
    }

    // get detail Security
    public function getSecurityObject($objectType, $objectData)
    {
        $security = Security::where('object_type', $objectType)
        ->where('object_id', $objectData['id'])
        ->first();

        
        if (!empty($security)) {
            $objectData['object_type'] = $security['object_type'];
            $objectData['object_id'] = $security['object_id'];
            $objectData['updated_by'] = $security['updated_by'];
            $objectData['is_shared'] = $security['is_shared'];
            $objectData['is_public'] = $security['is_public'];
            $objectData['department_array'] = $security['department_array'];
            $department_array = []; 
           
            if (!empty($objectData['department_array'])) {
                $departments = json_decode($objectData['department_array']);
              
                if (!empty($departments) && count($departments) > 0) {
                    foreach ($departments as $arr) {
                        $d_part =  Department::where('id', $arr)->select('name')->first();
                        if (!empty($d_part)) {
                            $department_array[] = $d_part->name ?? '';
                        }
                    }
                }
            }
            $objectData['departments'] = $department_array;
            $objectData['employee_array'] = $security['employee_array'];
            $empps = [];
            $role_id = [];
            if (!empty($objectData['employee_array'])) {
                $array = json_decode($objectData['employee_array']);
                if (!empty($array) && count($array) > 0) {
                    foreach ($array as $arr) {
                        $user =  User::where('id', $arr)->select('first_name', 'last_name','role_id')->first();
                        if (!empty($user)) {
                            $empps[] = $user->first_name . ' ' . $user->last_name;
                            // $role_id[] = $user->role_id;
                        }
                    }
                }
            }
            $objectData['employee_names'] = $empps;
        }
        return $objectData;
    }

    // update Security - only Company admin can update
    public function updateSecurityObject($objectType, $objectData, $userId)
    {
        if (!empty($objectData['object_id'])) {

            $securityObject = Security::where('object_type', $objectType)
                ->where('object_id', $objectData['object_id'])
                ->first();
            $updatedObject['updated_by'] = $userId;
            $updatedObject['is_shared'] = $objectData['is_shared'];
            $updatedObject['is_public'] = $objectData['is_public'];
            if (!empty($objectData['department_array'])) {
                $updatedObject['department_array'] = json_encode($objectData['department_array']);
            } else {
                $updatedObject['department_array'] = null;
            }
            if (!empty($objectData['employee_array'])) {
                $updatedObject['employee_array'] = json_encode($objectData['employee_array']);
            } else {
                $updatedObject['employee_array'] = null;
            }
            if((isset($objectData['department_array']) && !empty($objectData['department_array'])) && gettype($objectData['department_array']) == 'string') { 
                $updatedObject['department_array'] = json_decode($objectData['department_array']);
            }
            if((isset($objectData['employee_array']) && !empty($objectData['employee_array'])) && gettype($objectData['employee_array']) == 'string') {
                $updatedObject['employee_array'] = json_decode($objectData['employee_array']);
            }
            if (!empty($securityObject)) {
                $securityObject->update($updatedObject);
            }
        }
    }

    // table Reminder / start date - due date
    // get detail Reminder
    public function getReminderObject($objectData)
    { 
        if (!empty($objectData['start_time'])) {
            $objectData['start_time'] = date("H:i", $objectData['start_time']);;
        } else {
            $objectData['start_time'] = null;
        }
        if (!empty($objectData['start_date'])) {
            $objectData['start_date'] =  $objectData['start_date'] ;
        } else {
            $objectData['start_date'] = null;
        }
        if (!empty($objectData['deadline'])) {
            $objectData['deadline'] = date("Y-m-d", $objectData['deadline']);
        } else {
            $objectData['deadline'] = null;
        }
        return $objectData;
    }

    // get object attachment (RiskElementSource, Deviation)
    public function getObjectAttachment($type, $id)
    {
        $document = DocumentNew::where('type', 'report')
            ->where('object_type', $type)
            ->where('object_id', $id)
            ->first();
        if (isset($document)) {
            $documentAttachment = DocumentAttachment::where('document_id', $document['id'])->first();

            if ($type == 'risk') {
                $object['id'] = $document['id'];
                $object['original_file_name'] = $documentAttachment['original_file_name'];
                $object['file_size'] = $documentAttachment['file_size'];
            }
            $object['uri'] = $documentAttachment['uri'];
            // url
            $baseUrl = config('app.app_url');
            $object['url'] = $baseUrl . "/api/v1/uploads/" .  $object['uri'];
            //            $object['url'] = "http://localhost:8000/api/v1/uploads/".  $object['uri'];
        } else {
            $object = null;
        }
        return $object;
    }

    public function requestPushNotification($send_from, $company_id, $sendToArray = [], $notificationType = 'notification', $objectInfo = null, $action = null, $deadline = null, $url = null)
    {
        if (!$send_from || !$company_id || empty($sendToArray) || empty($notificationType) || empty($objectInfo)) {
            return null;
        }

        $rules = RequestPushNotification::$rules;

        $input['company_id'] = $company_id;
        $input['send_from'] = $send_from;
        $input['type'] = $notificationType;
        $input['feature'] = $objectInfo['type'];
        $input['feature_id'] = $objectInfo['id'];

        $input['short_description'] = '';
        $input['email_reason'] = '';

        if ($action == 'attendee') {
            $input['short_description'] = 'You have been assigned a ' . $objectInfo['type'] . ': <b>' . $objectInfo['name'] . '</b>';
        } elseif ($action == 'responsible') {
            $input['short_description'] = 'You are responsible person for  ' . $objectInfo['type'] . ': <b>' . $objectInfo['name'] . '</b>';
        } elseif ($action == 'attendee_done') {
            $input['short_description'] = $objectInfo['type'] . ' <b>' . $objectInfo['name'] . '</b> has been done by attendee.';
        } elseif ($action == 'decline') {
            //send to attendee: notification & email
            $input['short_description'] = $objectInfo['type'] . ' <b>' . $objectInfo['name'] . '</b> has been declined.';
            $input['email_reason'] = 'has been declined';
        } elseif ($action == 'deadline') {
            //send to attendee
            $input['short_description'] = $objectInfo['type'] . ' <b>' . $objectInfo['name'] . '</b> Deadline is coming, please complete your task before deadline';
        } elseif ($action == 'overdue') {
            //send to attendee, responsible
            $input['short_description'] = $objectInfo['type'] . ' <b>' . $objectInfo['name'] . '</b> Task is overdue.';
        }

        if ($objectInfo['type'] == 'task') {
            $input['url'] = '/company/tasks?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'goal') {
            $input['url'] = '/company/goals?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'checklist') {
            $input['url'] = '/company/checklists?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'checkpoint') {
            $input['url'] = '/company/checklists?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'risk-analysis') {
            $input['url'] = '/reports/reportedRiskanalysis?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'deviation') {
            $input['url'] = '/company/deviations?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'instruction') {
            $input['url'] = '/company/goals?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'routine') {
            $input['url'] = '/company/routines?id=' . $objectInfo['id'];
        } elseif ($objectInfo['type'] == 'sub-goal') {
            $input['url'] = '/company/goals?id=' . $objectInfo['source_id'];
        } elseif ($objectInfo['type'] == 'instruction-activity') {
            $input['url'] = '/company/instructions?id=' . $objectInfo['source_id'];
        }

        if ($url) {
            $input['url'] =  $url;
        }

        $countSendTo = count($sendToArray);
        if ($countSendTo > 0) {
            $input['send_to'] = json_encode($sendToArray);
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors, 400);
        }
        $newRequestPushNotification = RequestPushNotification::create($input);

        if ($countSendTo < 10) {
            if ($newRequestPushNotification['type'] == 'notification') {
                foreach ($sendToArray as $item) {
                    $this->createNotification($item, $newRequestPushNotification->id);
                }
            } elseif ($newRequestPushNotification['type'] == 'email') {
                foreach ($sendToArray as $item) {
                    $attendee = User::find($item);
                    $data = ([
                        'name' => $attendee['first_name'] . ' ' . $attendee['last_name'],
                        'objectType' => $objectInfo['type'],
                        'objectName' => $objectInfo['name'],
                        'reason' => $input['email_reason'],
                        'url' => config('app.site_url') . $newRequestPushNotification['url'],
                    ]);
                    Mail::to($attendee['email'])->send(new DeclinedTaskMail($data));
                }
            }

            $input['sending_time'] = Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 3;

            $newRequestPushNotification->update($input);
        }

        return $this->responseSuccess($newRequestPushNotification, 201);
    }

    public function setTimeManagement($objectID, $startDate = null, $deadline = null)
    {
        $object = ObjectItem::where('id', $objectID)
            ->with(['time'])
            ->first();
            return $object;

        if (empty($object)) {
            return $this->responseException('Not found object', 404);
        }

        if (!$startDate || !$deadline) {
            $subObject = ObjectItem::where('objects.source', $object['type'])
                ->where('objects.source_id', $object['id'])
                ->where('objects.is_valid', 1)
                ->with(['time'])
                ->get();

            $startDate = $object['start_date'];
            $deadline = $object['deadline'];

            foreach ($subObject as $item) {
                if ($item['start_date'] < $startDate) {
                    $startDate = $item['start_date'];
                }

                if ($item['deadline'] > $deadline) {
                    $deadline = $item['deadline'];
                }
            }
        }

        $objectTime = TimeManagement::where('object_id', $objectID)->first();
        if (empty($objectTime)) {
            return $this->responseException('Not found object time', 404);
        }

        return $objectTime->update(['start_date' => $startDate, 'deadline' => $deadline]);
    }

    public function addConnectToObject($user, $id, $type, $connectToArray)
    {
        if ($connectToArray != "[]" && empty($connectToArray) && count($connectToArray) > 5) return null;

        $rules = ConnectTo::$rules;
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];

        if ($type == 'document') {
            $input['document_id'] = $id;
        } else {
            $input['object_id'] = $id;
        } 
        if($connectToArray != "[]" && !empty($connectToArray) && count($connectToArray) > 0){
            foreach ($connectToArray as $item) {
                $input['connect_to_source'] = $item['connect_to_source'];
                $input['source_id'] = $item['source_id'];

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                ConnectTo::create($input);
            }
        }
    }

    public function updateConnectToObject($user, $id, $type, $connectToArray)
    {

        if ($connectToArray != "[]" && count($connectToArray) > 5) {
            return null;
            //            return $this->responseException('Invalid data!', 404);
        }

        if ($type == 'document') {
            $objectIDField = 'document_id';
        } else {
            $objectIDField = 'object_id';
        }

        $connectToNew = $connectToArray;
        $connectTo = ConnectTo::where('company_id', $user['company_id'])
            ->where($objectIDField, $id)
            ->pluck('id')
            ->toArray();

        $connectToDiff = array_diff($connectTo, array_column($connectToNew, 'id'));

        //delete connectTo
        ConnectTo::whereIn("id", $connectToDiff)->delete();

        $result = [];

        $rules = ConnectTo::$rules;
        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input[$objectIDField] = $id;
        
        if($connectToArray != "[]" && !empty($connectToArray) && count($connectToArray) > 0){
            foreach ($connectToArray as $item) {
                if (isset($item['id'])) {
                    $result[] = $item;
                } else {
                    $input['connect_to_source'] = $item['connect_to_source'];
                    $input['source_id'] = $item['source_id'];
    
                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }
    
                    $newConnectTo = ConnectTo::create($input);
    
                    $result[] = $newConnectTo;
                }
            }
        }
    }
    //    public function getNotificationMessage($action, $objectType, $objectName) {
    //        if ($action == 'assigned') {
    //            return  'You have been assigned a ' . $objectType . ': <b>' . $name . '</b>';
    //        } elseif ($action == 'responsible') {
    //            return  'You are responsible person for task: <b>' . $name . '</b>';
    //        } elseif ($action == 'decline') {
    //            return  'Task <b>' . $name . '</b> has been done by all assignees.';
    //        }
    //
    //        return '';
    //    }
}