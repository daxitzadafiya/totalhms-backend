<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Routine;
use App\Models\ObjectItem;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Routines",
 *     description="Routine APIs",
 * )
 **/
class RoutineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/routines",
     *     tags={"Routines"},
     *     summary="Get routines",
     *     description="Get routines list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRoutines",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('routine', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = Routine::leftJoin('departments','routines.department_id','=','departments.id')
                    -> leftJoin('job_titles','routines.job_title_id','=','job_titles.id')
                    // -> join('categories','routines.category_id','=','categories.id')
                    -> join('categories_new', 'routines.category_id', '=', 'categories_new.id')
                    -> join('users','routines.added_by','=','users.id')
                    -> where('routines.delete_status', 0)
                    -> where('routines.is_template', 1);
              
                if (!empty($request->startDate) && !empty($request->endDate) && $request->startDate == $request->endDate) {
                    $result = $result->whereDate('routines.created_at','<=',  $request->startDate)
                        ->whereDate('routines.deadline','>=',  $request->endDate);
                } else {
                    if (!empty($request->startDate)) {
                        $result = $result->whereDate('routines.created_at','>=',  $request->startDate);
                    }
                    if (!empty($request->endDate)) {
                        $result = $result->whereDate('routines.deadline','<=',  $request->endDate);
                    }
                }

                // start - added filter on routines resources

                if(isset($request->category) && !empty($request->category)) {
                    $result = $result->where('categories_new.id',$request->category);
                }

                if(isset($request->reported_by) && !empty($request->reported_by)) {
                    if($request->reported_by !== 0) {
                        $result = $result->where('routines.added_by',$request->reported_by);
                    }
                }

                if(isset($request->by_name) && !empty($request->by_name)) {
                    if(isset($request->category) && !empty($request->category)) {
                        $result = $result->where('categories_new.id',$request->category)->where(function($q) use($request) {
                            $q->orWhere('routines.name', 'Like', "%{$request->by_name}%");
                            if(str_contains($request->by_name, "Company Admin")) {
                                $q->orWhere('routines.added_by', 2);
                            } else if(str_contains($request->by_name, "System")) {
                                $q->orWhere('routines.added_by', 1);
                            }
                            $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                            $q->orWhere('routines.updated_at', 'Like', "%{$request->by_name}%");
                        });
                    } else if(isset($request->reported_by) && !empty($request->reported_by)) {
                        if($request->reported_by !== 0) {
                            $result = $result->where('routines.added_by',$request->reported_by)->where(function($q) use($request) {
                                $q->orWhere('routines.name', 'Like', "%{$request->by_name}%");
                                if(str_contains($request->by_name, "Company Admin")) {
                                    $q->orWhere('routines.added_by', 2);
                                } else if(str_contains($request->by_name, "System")) {
                                    $q->orWhere('routines.added_by', 1);
                                }
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.updated_at', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else if(isset($request->category) && isset($request->reported_by)) {
                        if($request->reported_by !== 0) {
                            $result = $result->where('categories_new.id',$request->category)->where('routines.added_by',$request->reported_by)->where(function($q) use($request) {
                                $q->orWhere('routines.name', 'Like', "%{$request->by_name}%");
                                if(str_contains($request->by_name, "Company Admin")) {
                                    $q->orWhere('routines.added_by', 2);
                                } else if(str_contains($request->by_name, "System")) {
                                    $q->orWhere('routines.added_by', 1);
                                }
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.updated_at', 'Like', "%{$request->by_name}%");
                            });
                        } else {
                            $result = $result->where('categories_new.id',$request->category)->where(function($q) use($request) {
                                $q->orWhere('routines.name', 'Like', "%{$request->by_name}%");
                                if(str_contains($request->by_name, "Company Admin")) {
                                    $q->orWhere('routines.added_by', 2);
                                } else if(str_contains($request->by_name, "System")) {
                                    $q->orWhere('routines.added_by', 1);
                                }
                                $q->orWhere('categories_new.name', 'Like', "%{$request->by_name}%");
                                $q->orWhere('routines.updated_at', 'Like', "%{$request->by_name}%");
                            });
                        }
                    } else {
                        $result = $result->where('routines.name', 'Like', "%{$request->by_name}%");
                        if(str_contains($request->by_name, "Company Admin")) {
                            $result = $result->orWhere('routines.added_by', 2);
                        } else if(str_contains($request->by_name, "System")) {
                            $result = $result->orWhere('routines.added_by', 1);
                        }
                        $result = $result->orWhere('categories_new.name', 'Like', "%{$request->by_name}%")
                            ->orWhere('routines.updated_at', 'Like', "%{$request->by_name}%");
                    }
                }

                // end - added filter on routines resources
                $result = $result->where (function ($q) use ($user) {
                        if ($user->role_id > 1) {
                            $q-> whereRaw('FIND_IN_SET(?, routines.industry_id)', [$user['company']['industry_id']])
                                -> where (function ($query) use ($user) {
                                    $query-> where('routines.company_id', $user['company_id'])
                                        -> orWhere('routines.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q-> where('routines.added_by', 1);
                        }
                    })
                    ->select('routines.*','departments.name as department_name', 'job_titles.name as job_title_name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'users.email as added_by_email', 'categories_new.name as category_name')
                        ->orderBy('id', 'desc')
                        ->paginate(10);
                if($result){
                    $result = $this->filterViewList('routine', $user, $user->filterBy, $result, $orderBy, $limit);
                    if ($user->role_id > 1) {
                        foreach ($result as $routine){
                            // dd($routine);
                            $routine['routine_used'] = 0;  
                            $routine['routine_used'] = $routine['used_count'] ?? 0;
                            if ($routine['responsible_id']) {
                                $responsible = User::find($routine['responsible_id']);
                                $routine['responsible_id_name'] = $responsible['first_name'] . ' ' . $responsible['last_name'];
                            }

                            $routine->count_related_object = 0;
                            if ($routine['is_template']) {
                                $countRelatedObject = Routine::where('parent_id', $routine['id'])->count();

                                if ($countRelatedObject > 0) {
                                    $routine->count_related_object = $countRelatedObject;
                                }
                            }

                            // start time
                            if (!empty($routine['start_time'])) {
                                $routine['start_time'] = date("H:i A", $routine['start_time']);;
                            } else {
                                $routine['start_time'] = null;
                            }
                            if (!empty($routine['start_date'])) {
                                $routine['start_date'] =  $routine['start_date'] ;
                            } else {
                                $routine['start_date'] = null;
                            }
                            if (!empty($routine['deadline'])) {
                                $routine['deadline'] = date("Y-m-d", $routine['deadline']);
                            } else {
                                $routine['deadline'] = null;
                            }
//                            foreach ($responsible_emps as $responsible_emp){
//                                $userInfo = User::find($responsible_emp);
//                                $username = $userInfo->first_name . ' ' . $userInfo->last_name . ', ';
//                                $list_responsible_users .= $username;
//                            }
//                            $list_responsible_users = trim($list_responsible_users);
//                            $list_responsible_users = substr($list_responsible_users, 0, strlen($list_responsible_users) - 1);
//                            $routine->responsible_emps_name = $list_responsible_users;
                        }
                    }
                    return $result;
                } else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/routines",
     *     tags={"Routines"},
     *     summary="Create new routine",
     *     description="Create new routine",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRoutine",
     *     @OA\RequestBody(
     *         description="Routine schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Routine")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Routine $routine)
    {
        try {
            $input = $request -> all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('routine', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = Routine::$rules;
                $inputRoutine = $this->getRoutineData($input);
                if ($user['role_id'] > 1) {
                    $inputRoutine['industry_id'] = $user['company']['industry_id'];
                    $inputRoutine['company_id'] = $user['company_id'];
                } else {
                    $inputRoutine['industry_id'] = $input['industry_id'];
                    $inputRoutine['company_id'] = null;
                }
                $inputRoutine['added_by'] = $user['id'];

                $validator = Validator::make($inputRoutine, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                } 
                $newRoutine = Routine::create($inputRoutine);
                if ($newRoutine) {
                  
                    if ($user['role_id'] == 1) {
                        $this->pushNotificationToAllCompanies('Routine', $newRoutine['id'], $newRoutine['name'],'create');
                    }
                  
                    if ($newRoutine['responsible_id']) {
                        $this->pushNotification($user['id'], $user['company_id'], 2, [$newRoutine['responsible_id']], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'responsible');
                    }
                    
                    if (!empty($inputRoutine['attendingEmpsArray'])) {
                        $this->pushNotification($user['id'], $user['company_id'], 2, $inputRoutine['attendingEmpsArray'], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'assigned');
                    }
                    
                    if(!empty($input['responsible_employee_array'])  ){
                        $encode = ($input['responsible_employee_array']); 
                        foreach($encode as $responsible_employee_array){ 
                            $n = $this->pushNotification($user['id'], $user['company_id'], 2, [$responsible_employee_array], 'routine', 'Routine', $newRoutine['id'], $newRoutine['name'], 'responsible'); 
                        }
                    }
                 
                }

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newRoutine, $input);

                return $this->responseSuccess($newRoutine);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function getRoutineData ($input) {
        $inputRoutine['name'] = $input['name'];
        $inputRoutine['description'] = $input['description'];
        $inputRoutine['status'] = $input['status'];
        $inputRoutine['category_id'] = $input['category_id'];
        $inputRoutine['deadline'] = @$input['deadline'];
        $inputRoutine['is_template'] = $input['is_template'];
        $inputRoutine['is_public'] = $input['is_public'];
        $inputRoutine['parent_id'] = $input['parent_id'];
        $inputRoutine['is_suggestion'] = $input['is_suggestion'];
        $inputRoutine['attendingEmpsArray'] = [];
        $inputRoutine['recurring_type'] = $input['recurring_type'];
        $inputRoutine['isDefaultResponsible'] = $input['isDefaultResponsible'];
        $inputRoutine['isDefaultAttendee'] = $input['isDefaultAttendee'];
        $inputRoutine['type'] = $input['type'];
        $inputRoutine['object_type'] = $input['object_type'];
        $inputRoutine['is_shared'] = $input['is_shared'];

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
            $inputRoutine['responsible_id'] = $input['responsible_id'];
            $inputRoutine['attendings_count'] = count($input['attending_emps']) + count($input['attending_contact']);
        }

        // Handle to save Reminder/ start date - due date
        $inputRoutine['is_activated'] = $input['is_activated'];
        if (!empty($input['start_time'])) {
            $inputRoutine['start_time'] = strtotime($input['start_time']);
        } else {
            // $inputRoutine['start_time'] = strtotime("today");
            $inputRoutine['start_time'] = null;
        }
        if (!$input['is_activated']) {
            $inputRoutine['deadline'] = null;
            $inputRoutine['recurring'] = 'indefinite';
        } else {
            if (!empty($input['deadline'])) {
                $inputRoutine['deadline'] = strtotime($input['deadline']);
                $inputRoutine['recurring'] = $input['recurring'];
            } else {
                $inputRoutine['deadline'] = null;
                $inputRoutine['recurring'] = 'indefinite';
            }
        }

        $inputRoutine['is_attending_activated'] = $input['is_attending_activated'];

        return $inputRoutine;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/routines/{id}",
     *     tags={"Routines"},
     *     summary="Get routine by id",
     *     description="Get routine by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRoutineByIdAPI",
     *     @OA\Parameter(
     *         description="routine id",
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
            $routineData = Routine::leftJoin('categories_new', 'routines.category_id', '=', 'categories_new.id')
            ->select('routines.*','categories_new.name as categoryName')->where("routines.id",$id)->first();
            if (empty($routineData)) {
                return $this->responseException('Not found routine', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'routine',
                'objectItem' => $routineData,
            ];
            if (!$user = $this->getAuthorizedUser('routine', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if(!empty($routineData->added_by)){
                    $detal = User::where('id',$routineData->added_by)->select('users.first_name', 'users.last_name')->first();
                    $routineData->addedByName = $detal->first_name .' '.$detal->last_name;
                }
                $routineData->routine_used = 0;  
                $routineData->routine_used = $routineData['used_count'] ?? 0;

                $routineData->count_related_object = 0;
                $routineData->related_objects = '';
                if ($routineData['is_template']) {
                    $relatedObject = Routine::leftJoin('users', 'routines.added_by','=', 'users.id')
                    ->leftJoin('companies', 'routines.company_id','=', 'companies.id')
                    ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('routines.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select('routines.id', 'routines.name',
                    'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                    'companies.name as company_name')
                    ->get(); 

                    if (count($relatedObject) > 0) {
                        $routineData->count_related_object = count($relatedObject);
                        $routineData->related_objects = $relatedObject;
                    }
                }
                $routineData->editPermission = $user->editPermission;

                // get Security information
                // $this->getSecurityObject('routine', $routineData);
                // get Reminder/ start date - due date information
                $routineData = $this->getReminderObject($routineData); 
                // if (!empty($routineData['start_time'])) {
                //     $routineData['start_date'] =  $routineData['start_time'];
                // }

                // if (!empty($routineData['deadline'])) {
                //     $routineData['deadline'] = $routineData['deadline'];
                // }
                if (!empty($routineData['start_time'])) {
                        $routineData['start_time'] = null;
                    }

                return $this->responseSuccess($routineData);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/routines/{id}",
     *     tags={"Routines"},
     *     summary="Update routine API",
     *     description="Update routine API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRoutineAPI",
     *     @OA\Parameter(
     *         description="routine id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Routine schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Routine")
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
            $rules = Routine::$updateRules;
            $input = $request -> all();

            $routineData = Routine::where("id",$id)->first();
            if (empty($routineData)) {
                return $this->responseException('Not found routine', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'routine',
                'objectItem' => $routineData,
            ];
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('routine', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $pushNotificationUpdateArray = [];
                $pushNotificationResponsibleArray = [];
                $pushNotificationAssignedArray = [];
                $inputRoutine = $this->getRoutineData($input);
                $inputRoutine['added_by'] = $routineData['added_by'];
                $inputRoutine['company_id'] = $routineData['company_id'];

                // Handle to save Reminder/ start date - due date
                if (!empty($input['start_time'])) {
                    $inputRoutine['start_time'] = strtotime($input['start_time']);
                } else {
                    $inputRoutine['start_time'] = strtotime("today");
                }
                if (!$input['is_activated']) {
                    $inputRoutine['deadline'] = null;
                    $inputRoutine['recurring'] = 'indefinite';
                } else {
                    if (!empty($input['deadline'])) {
                        $inputRoutine['deadline'] = strtotime($input['deadline']);
                        $inputRoutine['recurring'] = $input['recurring'];
                    } else {
                        $inputRoutine['deadline'] = null;
                        $inputRoutine['recurring'] = 'indefinite';
                    }
                }

                $validator = Validator::make($inputRoutine, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                if ($inputRoutine['responsible_id'] != $routineData['responsible_id']) {
                    array_push($pushNotificationResponsibleArray, $inputRoutine['responsible_id']);
                } else {
                    array_push($pushNotificationUpdateArray, $inputRoutine['responsible_id']);
                }

                if (!$routineData['attending_emps']) {
                    $newAttendingEmpsArray = $inputRoutine['attendingEmpsArray'];
                } else {
                    $newAttendingEmpsArray = array_diff($inputRoutine['attendingEmpsArray'], json_decode($routineData['attending_emps']));
                }
                if (!empty($newAttendingEmpsArray)) {
                    $pushNotificationAssignedArray = $newAttendingEmpsArray;

                    $oldAttendingEmpsArray = array_diff($inputRoutine['attendingEmpsArray'], $newAttendingEmpsArray);
                    if (!empty($oldAttendingEmpsArray)) {
                        $pushNotificationUpdateArray = array_merge($pushNotificationUpdateArray, $oldAttendingEmpsArray);
                    }
                }

                $routineData->update($inputRoutine);
                $this->updateConnectToObject($user, $routineData['id'], 'object', $input['connectToArray']);

                // update Security & Reminder
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('routine', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('routine', $input, null);
                }

                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Routine', $routineData['id'], $routineData['name'],'update');
                }

                if (!empty($pushNotificationResponsibleArray)) {
                    $this->pushNotification($user['id'], $user['company_id'], 2, $pushNotificationResponsibleArray, 'routine', 'Routine', $routineData['id'], $routineData['name'], 'responsible');
                }
                if (!empty($pushNotificationUpdateArray)) {
                    $this->pushNotification($user['id'], $user['company_id'], 2, $pushNotificationUpdateArray, 'routine', 'Routine', $routineData['id'], $routineData['name'], 'update');
                }
                if (!empty($pushNotificationAssignedArray)) {
                    $this->pushNotification($user['id'], $user['company_id'], 2, $pushNotificationAssignedArray, 'routine', 'Routine', $routineData['id'], $routineData['name'], 'assigned');
                }

                return $this->responseSuccess($routineData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/routines/{id}",
     *     tags={"Routines"},
     *     summary="Delete routine API",
     *     description="Delete routine API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteRoutineAPI",
     *     @OA\Parameter(
     *         description="routine id",
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
            $routineData = Routine::find($id);
            if (empty($routineData)) {
                return $this->responseException('Not found routine', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'routine',
                'objectItem' => $routineData,
            ];
            if ($routineData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('routine', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($routineData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Routine', $routineData->id, $routineData->name)) {
                    $routineData->update(['delete_status' => 1]);

//                Routine::destroy($id);
                    return $this->responseSuccess("Delete routine success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
