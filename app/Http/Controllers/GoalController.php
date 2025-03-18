<?php
namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Attendee;
use App\Models\AttendeeProcessing;
use App\Models\Employee;
use App\Models\ObjectItem;
use App\Models\Responsible;
use App\Models\TimeManagement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

/**
 * @OA\Tag(
 *     name="Goals",
 *     description="Goal APIs",
 * )
 **/
class GoalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/goals",
     *     tags={"Goals"},
     *     summary="Get goals",
     *     description="Get goals list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getGoals",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('goal', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = ObjectItem::join('users', 'objects.added_by', '=', 'users.id')
                    ->leftJoin('categories', 'objects.category_id', '=', 'categories.id')
                    ->where('objects.type', 'goal')
                    ->where('objects.is_valid', 1);

                $result = $result-> where (function ($q) use ($user) {
                    if ($user->role_id > 1) {
                        $q->whereJsonContains('objects.industry', $user['company']['industry_id'])
                            ->where(function ($query) use ($user) {
                                $query->where('objects.company_id', $user['company_id'])
                                    ->orWhere('objects.added_by', 1);
                            });
                    } else if ($user->role_id == 1) {
                        $q->where('objects.added_by', 1);
                    }
                })
                    -> select('objects.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name')
                    -> get();
                if($result){
                    $result = $this->filterViewList('goal', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $item) {
                        // responsible
                        $responsible_temp = Responsible::where('object_id', $item['id'])->first();
                        if (!empty($responsible_temp)) {
                            $responsible = json_decode($responsible_temp['employee_array']);
                            $list = null;
                            foreach ($responsible as $responsible_item) {
                                $userInfo = User::find($responsible_item);
                                $name = $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ', ';
                                $list .= $name;
                            }
                            $list = trim($list);
                            $list = substr($list, 0, strlen($list) - 1);
                            $item->responsible_id_name = $list;
                        }

                        // count sub-goal
                        $item->count_sub_goal = ObjectItem::where('source', 'goal')
                            ->where('source_id', $item['id'])
                            ->count();

                        // start time - deadline
                        if ($item->count_sub_goal == 0) {
                            $time = TimeManagement::where('object_id', $item['id'])->first();
                            if (!empty($time)) {
                                $item['start_date'] = date("Y-m-d", $time['start_date']);
                                $item['deadline'] = date("Y-m-d", $time['deadline']);
                            }
                        } else {
                            $item['start_date'] = null;
                            $item['deadline'] = null;
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
     *     path="/api/v1/goals",
     *     tags={"Goals"},
     *     summary="Create new goal",
     *     description="Create new goal",
     *     security={{"bearerAuth":{}}},
     *     operationId="createGoal",
     *     @OA\RequestBody(
     *         description="Goal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Goal")
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
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (!empty($input['subGoal'])) {
                    $subGoals = $input['subGoal'];
                }
                // create Goal object
                $newGoal = $this->createObject($input, $user);
//                if ($newGoal && $user['role_id'] == 1) {
//                    $this->pushNotificationToAllCompanies('Goal', $newGoal['id'], $newGoal['name'],'create');
//                }
                // create Sub goal object
                if (!empty($subGoals)) {
                    foreach ($subGoals as $item) {
                        if ($user['role_id'] == 1) {
                            $item['isSubGoal'] = true;
                            $item['industry'] = $newGoal['industry'];
                        }
                        $item['category_id'] = $newGoal['category_id'];
                        $item['source_id'] = $newGoal['id'];
                        $item['is_template'] = $newGoal['is_template'];
                        $this->createObject($item, $user);
                    }
                }
                return $this->responseSuccess($newGoal);
            }
        } catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function createObject ($input, $user) {
        $inputTemp = $input;

        $rules = ObjectItem::$rules;

        $input['added_by'] = $user['id'];

        if ($user['role_id'] > 1) {
            $input['industry'] = json_encode($user['company']['industry_id']);
            $input['company_id'] = $user['company_id'];
        } else {
            if (empty($input['isSubGoal'])) {
                $input['industry'] = json_encode($input['industry']);
            }
        }

//        $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', 'goal', $input['name']));

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        $newObject = ObjectItem::create($input);

        if ($user['role_id'] > 1) {
            // Responsible
            $this->createObjectResponsible($inputTemp, $newObject, $user);

            if (($input['type'] == 'goal' && empty($input['subGoal'])) || $input['type'] == 'sub-goal') {
                // Attendee
                $attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

                // Attendee processing
                $this->createObjectAttendeeProcessing($attendee);

                // Time management
                $this->createObjectTimeManagement($inputTemp, $newObject, $user);
            }
        }
        return $newObject;
    }

    private function createObjectResponsible($inputObject, $object, $user) {
        $input['company_id'] = $object['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];
        if (empty($inputObject['responsible_department_array']) && empty($inputObject['responsible_employee_array'])) {
            // not choose department & employee
            $input['employee_array'] = json_encode(array($user['id']));
//            $this->pushNotification($user['id'], $user['company_id'], 2, [$user['id']], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
        } else if (!empty($inputObject['responsible_department_array']) && empty($inputObject['responsible_employee_array'])) {
            // choose department - not choose employee
            $responsible = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['responsible_department_array'])->pluck('user_id')->toArray();
//            foreach ($responsible as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
//            }
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = json_encode($responsible);
        } else if (!empty($inputObject['responsible_employee_array'])) {
            // not choose department - choose employee
//            foreach ($inputObject['responsible_employee_array'] as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
//            }
            if (!is_array($inputObject['responsible_employee_array'])) {
                $inputObject['responsible_employee_array'] = array($inputObject['responsible_employee_array']);
            }
            $input['employee_array'] = json_encode($inputObject['responsible_employee_array']);
        }

        $rules = Responsible::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        return Responsible::create($input);
    }

    private function createObjectAttendee($inputObject, $object, $user) {
        $input['company_id'] = $object['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];

        if ($inputObject['attendee_all']) { // attendee = All
            $attendee = User::where('company_id', $object['company_id'])
                ->where('role_id', '>=', $user['role_id'])
                ->whereIn('role_id', [3, 4])->pluck('id')->toArray();
            if (!is_array($attendee)) {
                $attendee = array($attendee);
            }
            $input['employee_array'] = json_encode($attendee);
        } else {
            if (empty($inputObject['attendee_department_array']) && empty($inputObject['attendee_employee_array'])) {
                // not choose department & employee
                $input['employee_array'] = json_encode(array($user['id']));
            } else if (!empty($inputObject['attendee_department_array']) && empty($inputObject['attendee_employee_array'])) {
                // choose department - not choose employee
                $attendee = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                    ->where('users.company_id', $object['company_id'])
                    ->where('users.role_id', '>=', $user['role_id'])
                    ->whereIn('employees.department_id', $inputObject['attendee_department_array'])->pluck('user_id')->toArray();
                if (!is_array($attendee)) {
                    $attendee = array($attendee);
                }
                $input['employee_array'] = json_encode($attendee);
            } else if (!empty($inputObject['attendee_employee_array'])) {
                // not choose department - choose employee
                if (!is_array($inputObject['attendee_employee_array'])) {
                    $inputObject['attendee_employee_array'] = array($inputObject['attendee_employee_array']);
                }
                $input['employee_array'] = json_encode($inputObject['attendee_employee_array']);
            }
        }

        $rules = Attendee::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        return Attendee::create($input);
    }

    private function createObjectAttendeeProcessing($attendee) {
        $input['company_id'] = $attendee['company_id'];
        $input['attendee_id'] = $attendee['id'];
        $input['status'] = 'new';

        $list = json_decode($attendee['employee_array']);
        foreach ($list as $item) {
            $input['added_by'] = $item;
            $rules = AttendeeProcessing::$rules;
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            AttendeeProcessing::create($input);
        }
    }

    private function createObjectTimeManagement($inputObject, $object, $user) {
        $input['company_id'] = $object['company_id'];
        $input['added_by'] = $user['id'];
        $input['object_id'] = $object['id'];

        if (!empty($inputObject['start_date'])) {
            $input['start_date'] = strtotime($inputObject['start_date']);
        } else {
            $input['start_date'] = strtotime("today");
        }
        if (!empty($inputObject['deadline'])) {
            $input['deadline'] = strtotime($inputObject['deadline']);
        } else {
            $input['deadline'] = $input['start_date'];
        }

        $rules = TimeManagement::$rules;
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        return TimeManagement::create($input);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Get goal by id",
     *     description="Get goal by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getGoalByIdAPI",
     *     @OA\Parameter(
     *         description="goal id",
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
            $goalData = ObjectItem::where('objects.id', $id)
                ->leftJoin('users', 'objects.added_by', '=', 'users.id')
                ->leftJoin('categories', 'objects.category_id', '=', 'categories.id')
                ->select('objects.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                    'categories.name as category_name')
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
                // update history
//                $updateHistory = json_decode($goalData['update_history']);
//                $goalHistory = $this->getUpdateHistory($updateHistory);

                // responsible
                $responsible_temp = Responsible::where('object_id', $id)->first();
                if (!empty($responsible_temp)) {
                    $goalData->responsible_employee_array = json_decode($responsible_temp['employee_array']);
                    $list = [];
                    foreach ($goalData->responsible_employee_array as $responsible_item) {
                        $userInfo = User::find($responsible_item);
                        array_push($list, $userInfo['first_name'] . ' ' . $userInfo['last_name']);
                    }
                    $goalData->responsible_name = $list;
                }

                // attendee
                $attendee_temp = Attendee::where('object_id', $id)->first();
                if (!empty($attendee_temp)) {
                    $goalData->attendee_employee_array = json_decode($attendee_temp['employee_array']);
                }

                // start time - deadline
                $time = TimeManagement::where('object_id', $id)->first();
                if (!empty($time)) {
                    $goalData->start_date = date("Y-m-d", $time['start_date']);
                    $goalData->start_date_pre = $goalData->start_date;
                    $goalData->deadline = date("Y-m-d", $time['deadline']);
                    $goalData->deadline_pre = $goalData->deadline;
                } else {
                    $goalData->start_date = $goalData->start_date_pre = null;
                    $goalData->deadline = $goalData->deadline_pre = null;
                }

                $goalData->editPermission = $user->editPermission;
//                $goalData->history = $goalHistory;

                // count sub-goal
                $goalData->count_sub_goal = ObjectItem::where('source', 'goal')
                    ->where('source_id', $id)
                    ->count();

                // sub goal
                $goalData->subGoal = ObjectItem::where('source', 'goal')
                    ->where('source_id', $id)
                    ->get();
                foreach ($goalData->subGoal as $item) {
                    // attendee
                    $temp = Attendee::where('object_id', $item['id'])->first();
                    if (!empty($temp)) {
                        $item['attendee_employee_array'] = json_decode($temp['employee_array']);
                        $item['total_attendee'] = count($item['attendee_employee_array']);
                        $list = [];
                        foreach ($item['attendee_employee_array'] as $attendee_item) {
                            $process = AttendeeProcessing::where('company_id', $goalData['company_id'])
                                ->where('added_by', $attendee_item)
                                ->where('attendee_id', $temp['id'])->first();
                            if (!empty($process)) {
                                $attendee = [
                                    'name' => User::find($attendee_item)['first_name'] . ' ' . User::find($attendee_item)['last_name'],
                                    'comment' => $process['comment'],
                                    'image' => $process['image'],
                                    'status' => $process['status']
                                ];
                                array_push($list, $attendee);
                            }
                        }
                        $item['attendeeArray'] = $list;
                    }

                    // start time - deadline
                    $timeSubGoal = TimeManagement::where('object_id', $item['id'])->first();
                    if (!empty($timeSubGoal)) {
                        $item['start_date'] = date("Y-m-d", $timeSubGoal['start_date']);
                        $item['start_date_pre'] = $item['start_date'];
                        $item['deadline'] = date("Y-m-d", $timeSubGoal['deadline']);
                        $item['deadline_pre'] = $item['deadline'];
                    }
                }

                return $this->responseSuccess($goalData);
            }
        } catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Update goal API",
     *     description="Update goal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateGoalAPI",
     *     @OA\Parameter(
     *         description="goal id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Goal schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Goal")
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
            $rules = ObjectItem::$updateRules;
            $input = $request->all();
            $goalData = ObjectItem::find($id);
            if (empty($goalData)) {
                return $this->responseException('Not found goal', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'goal',
                'objectItem' => $goalData,
            ];

            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $inputTemp = $input;

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $goalData->update($input);
                // responsible
                $this->updateObjectResponsible($inputTemp, $goalData, $user);
                // attendee
                $this->updateObjectAttendee($inputTemp, $goalData, $user);
                // start time - deadline
                $this->updateObjectTimeManagement($inputTemp, $id);

                return $this->responseSuccess($goalData);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function updateObjectResponsible($input, $object, $user) {
        if (empty($input['responsible_department_array']) && empty($input['responsible_employee_array'])) {
            // not choose department & employee
            $input['employee_array'] = array($user['id']);
//            $this->pushNotification($user['id'], $user['company_id'], 2, [$user['id']], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
        } else if (!empty($input['responsible_department_array']) && empty($input['responsible_employee_array'])) {
            // choose department - not choose employee
            $responsible = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $input['responsible_department_array'])->pluck('user_id')->toArray();
//            foreach ($responsible as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
//            }
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = $responsible;
        } else if (!empty($input['responsible_employee_array'])) {
            // not choose department - choose employee
//            foreach ($input['responsible_employee_array'] as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'goal', 'Goal', $object['id'], $object['name'], 'responsible');
//            }
            if (!is_array($input['responsible_employee_array'])) {
                $input['responsible_employee_array'] = array($input['responsible_employee_array']);
            }
            $input['employee_array'] = $input['responsible_employee_array'];
        }
        $responsible = Responsible::where('object_id', $object['id'])->first();
        if (!empty($responsible)) {
            if (!empty(array_diff(json_decode($responsible['employee_array']), $input['employee_array']))) {
                $responsible->update(['employee_array' => json_encode($input['employee_array'])]);
            }
        }
    }

    private function updateObjectAttendee($input, $object, $user) {
        if (!empty($input['attendee_all']) && $input['attendee_all']) { // attendee = All
            $attendee = User::where('company_id', $object['company_id'])
                ->where('role_id', '>=', $user['role_id'])
                ->whereIn('role_id', [3, 4])->pluck('id')->toArray();
            if (!is_array($attendee)) {
                $attendee = array($attendee);
            }
            $input['employee_array'] = $attendee;
        } else {
            if (empty($input['attendee_department_array']) && empty($input['attendee_employee_array'])) {
                // not choose department & employee
                $input['employee_array'] = array($user['id']);
            } else if (!empty($input['attendee_department_array']) && empty($input['attendee_employee_array'])) {
                // choose department - not choose employee
                $attendee = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                    ->where('users.company_id', $object['company_id'])
                    ->where('users.role_id', '>=', $user['role_id'])
                    ->whereIn('employees.department_id', $input['attendee_department_array'])->pluck('user_id')->toArray();
                if (!is_array($attendee)) {
                    $attendee = array($attendee);
                }
                $input['employee_array'] = $attendee;
            } else if (!empty($input['attendee_employee_array'])) {
                // not choose department - choose employee
                if (!is_array($input['attendee_employee_array'])) {
                    $input['attendee_employee_array'] = array($input['attendee_employee_array']);
                }
                $input['employee_array'] = $input['attendee_employee_array'];
            }
        }
        $attendee = Attendee::where('object_id', $object['id'])->first();
        if (!empty($attendee)) {
            if (!empty(array_diff(json_decode($attendee['employee_array']), $input['employee_array']))) {
                $attendee->update(['employee_array' => json_encode($input['employee_array'])]);
            }
        }
    }

    private function updateObjectTimeManagement($inputObject, $id) {
        // start date
        if (!empty($inputObject['start_date'])) {
            $inputObject['start_date'] = strtotime($inputObject['start_date']);
        } else {
            $inputObject['start_date'] = strtotime("today");
        }
        // deadline
        if (!empty($inputObject['deadline'])) {
            $inputObject['deadline'] = strtotime($inputObject['deadline']);
        } else {
            $inputObject['deadline'] = $inputObject['start_date'];
        }
        $time = TimeManagement::where('object_id', $id)->first();
        if (!empty($time)) {
            if ($inputObject['start_date'] != $time['start_date']) {
                $time->update(['start_date' => $inputObject['start_date']]);
            }
            if ($inputObject['deadline'] != $time['deadline']) {
                $time->update(['deadline' => $inputObject['deadline']]);
            }
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/goals/{id}",
     *     tags={"Goals"},
     *     summary="Delete goal API",
     *     description="Delete goal API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteGoalAPI",
     *     @OA\Parameter(
     *         description="goal id",
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
            $goalData = ObjectItem::find($id);
            if (empty($goalData)) {
                return $this->responseException('Not found goal', 404);
            }
            // sub goal
            $subGoal = ObjectItem::where('source', 'goal')
                ->where('source_id', $id)
                ->get();
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'goal',
                'objectItem' => $goalData,
            ];
            if ($goalData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('goal', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($goalData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Goal', $goalData->id, $goalData->name)) {
                    $goalData->update(['is_valid' => 0]);
                    if (!empty($subGoal)) {
                        foreach ($subGoal as $item) {
                            if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Goal', $item->id, $item->name)) {
                                $item->update(['is_valid' => 0]);
                            }
                        }
                    }
                    return $this->responseSuccess("Delete goal success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
