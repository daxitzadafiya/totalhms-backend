<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ObjectItem;
use App\Models\Routine;
use App\Models\Schedule;
use App\Models\TimeManagement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Exception;
use Validator;
use app\helpers\ValidateResponse;
use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\AttendeeHistory;
use App\Models\AttendeeProcessing;
use App\Models\Employee;
use App\Models\ObjectOption;
use App\Models\Responsible;
use App\Models\ResponsibleProcessing;
use Illuminate\Support\Facades\Log;

class CreateTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command used to store tasks';
    public $timezone = 'Europe/Oslo';
    public $controller = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->controller  = new Controller();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $objectData = ObjectItem::where('type','routine')->with(['attendee', 'responsible', 'time'])->orderBy('id','desc')->get();
            foreach ($objectData as $key => $object) {
                $company = Company::where('id',$object['company_id'])->first('time_zone');
                $this->timezone  = $company['time_zone'] ?? 'Europe/Oslo';
                $user = User::find($object['added_by']);
                $dateTimeObj = $this->getDateTimeBasedOnTimezone($object);
                $obj_start_date = $dateTimeObj['start_date']?? '';
                $obj_start_time = isset($dateTimeObj['start_time']) && !empty($dateTimeObj['start_time']) ? $dateTimeObj['start_time'] : '00:00:00';
                $obj_end_date = $dateTimeObj['deadline'] ?? '';
                $obj_end_time = isset($dateTimeObj['end_time']) && !empty($dateTimeObj['end_time']) ? $dateTimeObj['end_time'] : '00:00:00'; 
                $todayDate = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
                $tomorrowDate = Carbon::tomorrow($this->timezone)->format('Y-m-d H:i:s');
                $startDay = Carbon::make($obj_start_date . ' ' . $obj_start_time);
                $endDay = Carbon::make($obj_end_date . ' ' . $obj_end_time);
                // check routine start and end dates
                $routine = Routine::where('id',$object['source_id'])->with(['schedule'])->first();
                if(empty($routine->deadline)) {
                    $obj_end_date = "9999-01-01";
                    $obj_end_time = "00:00:00";
                    $endDay = Carbon::make($obj_end_date . ' ' . $obj_end_time);
                }
                if(($startDay->format('Y-m-d H:i:s') <= $todayDate) && $todayDate <= $endDay->format('Y-m-d H:i:s')) {
                    if((isset($routine) && !empty($routine)) && $routine->recurring_type == 'task'){
                        $taskData = ObjectItem::where('type','task')->where('source','routine')->where('source_id',$object['source_id'])->with(['time'])->latest()->first();
                        // if task already created 
                        if(isset($taskData) && !empty($taskData)){
                            $latestTaskTimeObj = $this->getDateTimeBasedOnTimezone($taskData);
                            $latest_obj_start_date = $latestTaskTimeObj['start_date']?? '';
                            $latest_obj_start_time = isset($latestTaskTimeObj['start_time']) && !empty($latestTaskTimeObj['start_time']) ? $latestTaskTimeObj['start_time'] : '00:00:00';
                            $latest_obj_end_date = $latestTaskTimeObj['recurring_date'] ?? '';
                            $latest_obj_end_time = isset($latestTaskTimeObj['end_time']) && !empty($latestTaskTimeObj['end_time']) ? $latestTaskTimeObj['end_time'] : '00:00:00'; 
                            $startDay = Carbon::make($latest_obj_start_date . ' ' . $latest_obj_start_time);
                            $endDay = Carbon::make($latest_obj_end_date . ' ' . $latest_obj_end_time);
                            $todayOnlyDate = Carbon::now($this->timezone)->format('Y-m-d');
                            
                            //check task end and today date
                            if($todayOnlyDate == $latest_obj_end_date) {
                                $todayTime = Carbon::now($this->timezone)->format('H:i');
                                $taskTime = Carbon::make($latest_obj_start_time)->format('H:i');    
                                // check exact time for create task
                                if($taskTime == $todayTime){
                                    $this->createTask($routine,$object,$user);
                                }
                            }
                        }else{
                            // create the first task for specified routine
                            $this->createTask($routine,$object,$user);
                        }
                    }
                    if((isset($routine) && !empty($routine)) && $routine->recurring_type == 'reminder'){
                        $obj_recurring_date = $dateTimeObj['recurring_date'] ?? '';
                        if(isset($obj_recurring_date) && !empty($obj_recurring_date)){
                            $todayReminderDate = Carbon::now($this->timezone)->format('Y-m-d');
                            //check reminder-end and today date
                            if($todayReminderDate == $obj_recurring_date) {
                                $todayReminderTime = Carbon::now($this->timezone)->format('H:i');
                                $reminderTime = Carbon::make($obj_start_time)->format('H:i');    
                                    // check exact time for send reminder
                                    if($todayReminderTime == $reminderTime){
                                        $this->createReminder($routine,$object,$user);
                                    }
                                }
                        }else{
                            $this->createReminder($routine,$object,$user);
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function createTask($routine,$object,$user)
    {
        
        if(isset($routine->schedule['schedule_data']) && !empty($routine->schedule['schedule_data'])){
            $input = $routine->schedule['schedule_data'];
            if(isset($input) && !empty($input)){
                $input = json_decode($input,true);
                if(isset($object->attendee->employee_array) && !empty($object->attendee->employee_array)){
                    $input['attendee_employee_array'] = json_decode($object->attendee->employee_array,true);
                }
                if(isset($object->responsible->employee_array) && !empty($object->responsible->employee_array)){
                    $input['responsible_employee_array'] = json_decode($object->responsible->employee_array,true);
                }

                $todayTaskDate = Carbon::now($this->timezone)->format('Y-m-d');
                $deadlineDate = Carbon::tomorrow($this->timezone)->format('Y-m-d');
                $recurringDate = Carbon::tomorrow($this->timezone)->format('Y-m-d');
               
                if($input['recurring'] == 'Daily'){
                    $deadlineDate = Carbon::tomorrow($this->timezone)->format('Y-m-d');
                    $recurringDate = Carbon::tomorrow($this->timezone)->format('Y-m-d');
                } else if ($input['recurring'] == 'Weekly'){
                    $deadlineDate = Carbon::now($this->timezone)->addWeek()->format('Y-m-d');
                    $recurringDate = Carbon::now($this->timezone)->addWeek()->format('Y-m-d');
                } else if ($input['recurring'] == 'Monthly'){
                    $deadlineDate = Carbon::now($this->timezone)->addMonth()->format('Y-m-d');
                    if($input['is_duration']){
                        $deadlineDate = Carbon::now($this->timezone)->addWeeks($input['duration'])->format('Y-m-d');
                    }else{
                        $deadlineDate = Carbon::now($this->timezone)->addDays($input['duration'])->format('Y-m-d');
                    }
                    $recurringDate = Carbon::now($this->timezone)->addMonth()->format('Y-m-d');
                } else if ($input['recurring'] == 'Yearly'){
                    $deadlineDate = Carbon::now($this->timezone)->addYear()->format('Y-m-d');
                    if($input['is_duration']){
                        $deadlineDate = Carbon::now($this->timezone)->addWeeks($input['duration'])->format('Y-m-d');
                    }else{
                        $deadlineDate = Carbon::now($this->timezone)->addDays($input['duration'])->format('Y-m-d');
                    }
                    $recurringDate = Carbon::now($this->timezone)->addYear()->format('Y-m-d');
                } else if ($input['recurring'] == 'Quarter'){
                    $deadlineDate = Carbon::now($this->timezone)->addMonths(3)->format('Y-m-d');
                    if($input['is_duration']){
                        $deadlineDate = Carbon::now($this->timezone)->addWeeks($input['duration'])->format('Y-m-d');
                    }else{
                        $deadlineDate = Carbon::now($this->timezone)->addDays($input['duration'])->format('Y-m-d');
                    }
                    $recurringDate = Carbon::now($this->timezone)->addMonths(3)->format('Y-m-d');
                } else if($input['recurring'] == 'Bi-Weekly') {
                    $deadlineDate = Carbon::now($this->timezone)->addDays(15)->format('Y-m-d');
                    $recurringDate = Carbon::now($this->timezone)->addDays(15)->format('Y-m-d');
                } else if($input['recurring'] == 'Half-Yearly') {
                    $deadlineDate = Carbon::now($this->timezone)->addMonths(6)->format('Y-m-d');
                    if($input['is_duration']){
                        $deadlineDate = Carbon::now($this->timezone)->addWeeks($input['duration'])->format('Y-m-d');
                    }else{
                        $deadlineDate = Carbon::now($this->timezone)->addDays($input['duration'])->format('Y-m-d');
                    }
                    $recurringDate = Carbon::now($this->timezone)->addMonths(6)->format('Y-m-d');
                }

                $input['start_date'] = $todayTaskDate;
                $input['start_date_pre'] = $todayTaskDate;
                $input['deadline'] = $deadlineDate;
                $input['deadline_pre'] = $deadlineDate;
                $input['recurring_date'] = $recurringDate;
                $input['type'] = $routine->recurring_type;

                $this->createObject($input, $user);
                Log::channel('custom')->info('task created:--'.Carbon::now($this->timezone)->format('Y-m-d H:i:s'));

            }
        }
    }

    public function createReminder($routine,$object,$user){
        $input = $routine->schedule['schedule_data'] ?? [];
        if(isset($input) && !empty($input)){
            $input = json_decode($input,true);
            if(isset($object->attendee->employee_array) && !empty($object->attendee->employee_array)){
                $input['attendee_employee_array'] = json_decode($object->attendee->employee_array,true);
            }
            if(isset($object->responsible->employee_array) && !empty($object->responsible->employee_array)){
                $input['responsible_employee_array'] = json_decode($object->responsible->employee_array,true);
            }

            $this->controller->requestPushNotification($user['id'], $user['company_id'], $input['responsible_employee_array'], 'notification', $object, 'responsible');
            $this->controller->requestPushNotification($user['id'], $user['company_id'], $input['attendee_employee_array'], 'notification', $object, 'attendee');
            if($input['recurring'] == 'Daily'){
                $recurringDate = Carbon::tomorrow($this->timezone)->format('Y-m-d');
            } else if ($input['recurring'] == 'Weekly'){
                $recurringDate = Carbon::now($this->timezone)->addWeek()->format('Y-m-d');
            } else if ($input['recurring'] == 'Monthly'){
                $recurringDate = Carbon::now($this->timezone)->addMonth()->format('Y-m-d');
            } else if ($input['recurring'] == 'Yearly'){
                $recurringDate = Carbon::now($this->timezone)->addYear()->format('Y-m-d');
            } else if ($input['recurring'] == 'Quarter'){
                $recurringDate = Carbon::now($this->timezone)->addMonths(3)->format('Y-m-d');
            } else if($input['recurring'] == 'Bi-Weekly') {
                $recurringDate = Carbon::now($this->timezone)->addDays(15)->format('Y-m-d');
            } else if($input['recurring'] == 'Half-Yearly') {
                $recurringDate = Carbon::now($this->timezone)->addMonths(6)->format('Y-m-d');
            }
            $timeInput = [];
            if (!empty($input['end_time']) && !empty($recurringDate)) {
                $timeInput['recurring_date'] = strtotime($recurringDate . ' ' . $input['end_time']);
                $timeInput['end_time'] = $input['end_time'];
            } elseif (!empty($recurringDate)) {
                $timeInput['recurring_date'] = strtotime($recurringDate);
                $input['end_time'] = $input['start_time'];
            } else {
                $timeInput['recurring_date'] = strtotime("+1 day", $input['start_date']);
                $timeInput['end_time'] = $input['start_time'];
            }
            $time = TimeManagement::where('object_id', $object['id'])->first();
            
            $time->update(['recurring_date' => $timeInput['recurring_date']]);
            Log::channel('custom')->info('send reminder:--'.Carbon::now($this->timezone)->format('Y-m-d H:i:s'));
        }
    }

    public function getDateTimeBasedOnTimezone($object)
    {
        $dateTimeObject = $this->getObjectTimeInfo($object);
        $start_date = $dateTimeObject['start_date'] ?? '';
        $start_time = $dateTimeObject['start_time'] ?? '';
        $deadline = $dateTimeObject['deadline'] ?? '';
        $end_time = $dateTimeObject['end_time'] ?? '';
        $recurring_date = $dateTimeObject['recurring_date'] ?? '';

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
        if((isset($recurring_date) && !empty($recurring_date)) && (isset($end_time) && !empty($end_time))) {
            // $object['recurring_date'] = Carbon::make($recurring_date . ' ' . $end_time)->setTimezone($this->timezone)->format('Y-m-d');
            $object['recurring_date'] = Carbon::make($recurring_date . ' ' . $end_time, $this->timezone)->format('Y-m-d');

            if($end_time == "00:00:00") {
                $object['end_time'] = "";
            } else {
                $object['end_time'] = Carbon::make($recurring_date . ' ' . $end_time,$this->timezone)->format('H:i:s');
            }
        } else if(isset($recurring_date) && !empty($recurring_date)) {
            $end_time = "00:00";
            $object['recurring_date'] = Carbon::make($recurring_date . ' ' . $end_time,$this->timezone)->format('Y-m-d');
            $object['end_time'] = "";
        } else {
            $object['recurring_date'] = "";
            $object['end_time'] = "";
        }

        return $object;
    }

    public function createObject($input, $user)
    {
         
        $inputTemp = $input;

        if ($user->role_id > 1) {
            $input['company_id'] = $user['company_id'];
            $input['industry'] = json_encode($user['company']['industry_id']);
        } else {
            $input['industry'] = json_encode($input['industry']);
        }
        $input['added_by'] = $user['id'];

        $rules = ObjectItem::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->controller->responseError($errors, 400);
        }

        $newObject = ObjectItem::create($input);


        if (!empty($input['connectToArray'])) {
            $this->controller->addConnectToObject($user, $newObject['id'], $newObject['type'], $input['connectToArray']);
        }

        // if object is NOT a Resource
        if (!$newObject['is_template']) {
            // Responsible
            if(empty($inputTemp['responsible_employee_array'])) {
                if(isset($user['role_id']) && $user['role_id'] == 4) {
                    $employee = Employee::where('user_id', $user['id'])->first();
                    if(isset($employee->nearest_manager) && !empty($employee->nearest_manager)) {
                        $inputTemp['responsible_employee_array'] = [$employee->nearest_manager];
                    }
                } else {
                    $inputTemp['responsible_employee_array'] = [$user['id']];
                }

            }

            if(empty($inputTemp['attendee_employee_array'])) {
                $inputTemp['attendee_employee_array'] = [$user['id']];
            }

            if (!empty($inputTemp['responsible_employee_array']) || !empty($inputTemp['responsible_department_array'])) { 
                $newObject->responsible = $this->createObjectResponsible($inputTemp, $newObject, $user);
                $newObject->processing = $this->createObjectResponsibleProcessing($newObject->responsible, $user, false, $input);
            }

            // Attendee
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

        return $newObject;
    }

    public function createObjectTimeManagement($inputObject, $object, $user, $requestEdit = false)
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
            $input['start_time'] = Carbon::now()->format('H:i');
        }
      
        if (!empty($inputObject['end_time']) && !empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline'] . ' ' . $inputObject['end_time']);
            $input['end_time'] = $inputObject['end_time'];
        } elseif (!empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline']);
            $input['end_time'] = $input['start_time'];
        } else {
            $input['deadline'] = strtotime("+1 day", $input['start_date']);
            $input['end_time'] = $input['start_time'];
        }
        if (!empty($inputObject['end_time']) && !empty($inputObject['recurring_date'])) {
            $input['recurring_date'] = strtotime($inputObject['recurring_date'] . ' ' . $inputObject['end_time']);
            $input['end_time'] = $inputObject['end_time'];
        } elseif (!empty($inputObject['recurring_date'])) {
            $input['recurring_date'] = strtotime($inputObject['recurring_date']);
            $input['end_time'] = $input['start_time'];
        } else {
            $input['recurring_date'] = strtotime("+1 day", $input['start_date']);
            $input['end_time'] = $input['start_time'];
        }
        

        if ($requestEdit) {
            $time = TimeManagement::where('object_id', $object['id'])->first();

            $rules = TimeManagement::$updateRules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->controller->responseError($errors, 400);
            }
            $time->update($input);
            if ($object['source_id']) {
                $this->controller->setTimeManagement($object['source_id']);
            }

            return $time;
        } else {
            $rules = TimeManagement::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->controller->responseError($errors, 400);
            }
            return TimeManagement::create($input);
        }
    }

    public function getObjectTimeInfo($objectData)
    {
        $newdata = [];
        if (isset($objectData['time']['id'])) {
            $newdata['start_date'] = date("Y-m-d", $objectData['time']['start_date']);
            if(isset($objectData['time']['start_time']) && !empty($objectData['time']['start_time'])) {
                $newdata['start_time'] = $objectData['time']['start_time'].":00"; 
            } else {
                $newdata['start_time'] = "00:00:00";
            }
            $newdata['deadline'] = date("Y-m-d", $objectData['time']['deadline']);
            if(isset($objectData['time']['recurring_date']) && !empty($objectData['time']['recurring_date'])) {
                $newdata['recurring_date'] = date("Y-m-d", $objectData['time']['recurring_date']);
            }
            if(isset($objectData['time']['end_time']) && !empty($objectData['time']['end_time'])) {
                $newdata['end_time'] = $objectData['time']['end_time'].":00"; 
            } else {
                $newdata['end_time'] = "00:00:00";
            }
        }

        return $newdata;
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
            $input['employee_array'] = json_encode(array($user['id']));
        } elseif (!empty($inputObject['responsible_employee_array'])) {   // choose employee
            if (!is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] = array($inputObject['responsible_employee_array']);
            }
            if (isset($inputObject['responsible_department_array']) && count($inputObject['responsible_department_array']) > 0 && !is_array($inputObject['responsible_department_array'])) {
                $inputObject['responsible_department_array'] = array($inputObject['responsible_department_array']);
            }
            $input['employee_array'] = json_encode($inputObject['responsible_employee_array']);
            $input['department_array'] = !empty($inputObject['responsible_department_array']) ? json_encode($inputObject['responsible_department_array']) : '';
        } elseif (!empty($inputObject['responsible_department_array'])) {   // choose department
            $responsible = Employee::leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['responsible_department_array'])
                ->pluck('user_id')
                ->toArray();
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
            $responsible = Responsible::where('object_id', $object['id'])->first();
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
            return $this->controller->responseError($errors, 400);
        }
        if (!$requestEdit) {
             $responsible = Responsible::create($input);
        }
        $this->controller->requestPushNotification($user['id'], $user['company_id'], json_decode($responsible['employee_array']), 'notification', $object, 'responsible');

        return $responsible;
    }

    private function createObjectAttendee($inputObject, $object, $user, $requestEdit = false)
    {

        $input['company_id'] = $user['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
        $input['required_comment'] = $inputObject['attendee_required_comment'] ?? 0;
        $input['required_attachment'] = $inputObject['attendee_required_attachment'] ?? 0;

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
            } else if (!empty($inputObject['attendee_department_array'])) { // choose department
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
        }

        $rules = Attendee::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->controller->responseError($errors, 400);
        }
        $attendee = Attendee::create($input);
        $this->controller->requestPushNotification($user['id'], $user['company_id'], json_decode($attendee['employee_array']), 'notification', $object, 'attendee');

        return $attendee;
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
                return $this->controller->responseError($errors, 400);
            }
            $result[] = AttendeeProcessing::create($input);
        }
        return $result;
    }

    private function createObjectResponsibleProcessing($responsible, $user, $requestEdit = false, $inputData = false)
    {
        if ($requestEdit) {
            ResponsibleProcessing::where('responsible_id', $responsible['id'])->delete();
        }
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
        }
        return $result;
    }

    private function countObjectResourceUsedTime($objectId)
    {
        $obj = ObjectOption::where('object_id', $objectId)->first();
        if (empty($obj)) {
            return $this->controller->responseException('Not found resource', 404);
        }
        $obj->update([
            'number_used_time' => $obj['number_used_time'] + 1,
        ]);
    }
}
