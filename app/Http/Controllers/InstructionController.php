<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ObjectItem;
use App\Models\Responsible;
use App\Models\Attendee;
use App\Models\AttendeeProcessing;
use Validator;

/**
 * @OA\Tag(
 *     name="Instructions",
 *     description="Instruction APIs",
 * )
 **/
class InstructionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/instructions",
     *     tags={"Instructions"},
     *     summary="Get instructions",
     *     description="Get instructions list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getInstructions",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            if (!$user = $this->getAuthorizedUser('instruction', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = ObjectItem::join('users', 'objects.added_by', '=', 'users.id')
                    ->leftJoin('categories', 'objects.category_id', '=', 'categories.id')
                    ->where('objects.type', 'instruction')
                    ->where('objects.is_valid', 1);

                $result = $result->where(function ($q) use ($user) {
                    if ($user->role_id > 1) {
                        $q->whereJsonContains('objects.industry', $user['company']['industry_id'])
                            ->where(function ($query) use ($user) {
                                $query->where('objects.company_id', $user['company_id'])
                                    ->orWhere('objects.added_by', 1);
                            });
                    } else if ($user->role_id == 1) {
                        $q-> where('objects.added_by', 1);
                    }
                })
                    ->select('objects.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name')
                    ->get();
                if($result) {
                    $result = $this->filterViewList('instruction', $user, $user->filterBy, $result, $orderBy, $limit);
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

                            // count instruction-activity
                            $item->count_activity = ObjectItem::where('source', 'instruction')
                                ->where('source_id', $item['id'])
                                ->count();
                        }
                    }
                    return $this->responseSuccess($result);
                } else{
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/instructions",
     *     tags={"Instructions"},
     *     summary="Create new instruction",
     *     description="Create new instruction",
     *     security={{"bearerAuth":{}}},
     *     operationId="createInstruction",
     *     @OA\RequestBody(
     *         description="Instruction schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Instruction")
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
            if (!$user = $this->getAuthorizedUser('instruction', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (!empty($input['activities'])) {
                    $activities = $input['activities'];
                }
                // create Instruction object
                $newInstruction = $this->createObject($input, $user);
//                if ($newInstruction && $user['role_id'] == 1) {
//                    $this->pushNotificationToAllCompanies('Instruction', $newInstruction['id'], $newInstruction['name'],'create');
//                }
                // create Instruction activity object
                if (!empty($activities)) {
                    foreach ($activities as $item) {
                        if ($user['role_id'] == 1) {
                            $item['isActivity'] = true;
                            $item['industry'] = $newInstruction['industry'];
                        }
                        $item['category_id'] = $newInstruction['category_id'];
                        $item['source_id'] = $newInstruction['id'];
                        $item['is_template'] = $newInstruction['is_template'];
                        $this->createObject($item, $user);
                    }
                }

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newInstruction, $input);

                return $this->responseSuccess($newInstruction);
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
            if (empty($input['isActivity'])) {
                $input['industry'] = json_encode($input['industry']);
            }
        }

//        $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', 'instruction', $input['name']));

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }
        $newObject = ObjectItem::create($input);

        if ($user['role_id'] > 1) {
            // Responsible
            $this->createObjectResponsible($inputTemp, $newObject, $user);

            // Attendee
            $attendee = $this->createObjectAttendee($inputTemp, $newObject, $user);

            // Attendee processing
            $this->createObjectAttendeeProcessing($attendee);
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
//            $this->pushNotification($user['id'], $user['company_id'], 2, [$user['id']], 'instruction', 'Instruction', $object['id'], $object['name'], 'responsible');
        } else if (!empty($inputObject['responsible_department_array']) && empty($inputObject['responsible_employee_array'])) {
            // choose department - not choose employee
            $responsible = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                ->where('users.company_id', $object['company_id'])
                ->whereIn('users.role_id', [2, 3])
                ->whereIn('employees.department_id', $inputObject['responsible_department_array'])->pluck('user_id')->toArray();
//            foreach ($responsible as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'instruction', 'Instruction', $object['id'], $object['name'], 'responsible');
//            }
            if (!is_array($responsible)) {
                $responsible = array($responsible);
            }
            $input['employee_array'] = json_encode($responsible);
        } else if (!empty($inputObject['responsible_employee_array'])) {
            // not choose department - choose employee
//            foreach ($inputObject['responsible_employee_array'] as $item) {
//                $this->pushNotification($user['id'], $user['company_id'], 2, [$item], 'instruction', 'Instruction', $object['id'], $object['name'], 'responsible');
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

    /**
     * @OA\Get(
     *     path="/api/v1/instructions/{id}",
     *     tags={"Instructions"},
     *     summary="Get instruction by id",
     *     description="Get instruction by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getInstructionByIdAPI",
     *     @OA\Parameter(
     *         description="instruction id",
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
            $instructionData = ObjectItem::where('objects.id', $id)
                ->leftJoin('users', 'objects.added_by', '=', 'users.id')
                ->leftJoin('categories', 'objects.category_id', '=', 'categories.id')
                ->select('objects.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                    'categories.name as category_name')
                ->first();
            if (empty($instructionData)) {
                return $this->responseException('Not found instruction', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'instruction',
                'objectItem' => $instructionData,
            ];
            if (!$user = $this->getAuthorizedUser('instruction', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                // responsible
                $instructionData = $this->showResponsible($id, $instructionData);

                // instruction-activity
                $instructionData->activities = ObjectItem::where('source', 'instruction')
                    ->where('source_id', $id)
                    ->get();
                foreach ($instructionData->activities as $item) {
                    // responsible
                    $item = $this->showResponsible($item['id'], $item);

                    // attendee
                    $temp = Attendee::where('object_id', $item['id'])->first();
                    if (!empty($temp)) {
                        $item['attendee_employee_array'] = json_decode($temp['employee_array']);
                        $item['total_attendee'] = count($item['attendee_employee_array']);
                        $list = [];
                        foreach ($item['attendee_employee_array'] as $attendee_item) {
                            $process = AttendeeProcessing::where('company_id', $instructionData['company_id'])
                                ->where('added_by', $attendee_item)
                                ->where('attendee_id', $temp['id'])->first();
                            if (!empty($process)) {
                                $attendee = [
                                    'name' => User::find($attendee_item)['first_name'] . ' ' . User::find($attendee_item)['last_name'],
                                ];
                                array_push($list, $attendee);
                            }
                        }
                        $item['attendeeArray'] = $list;
                    }
                }

                // count instruction-activity
                $instructionData->count_activity = ObjectItem::where('source', 'instruction')
                    ->where('source_id', $id)
                    ->count();

                $instructionData->editPermission = $user->editPermission;

                // get Security information
                $this->getSecurityObject('instruction', $instructionData);

                return $this->responseSuccess($instructionData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function showResponsible($id, $array) {
        $responsible_temp = Responsible::where('object_id', $id)->first();
        if (!empty($responsible_temp)) {
            $array->responsible_employee_array = json_decode($responsible_temp['employee_array']);
            $list = [];
            foreach ($array->responsible_employee_array as $responsible_item) {
                $userInfo = User::find($responsible_item);
                array_push($list, $userInfo['first_name'] . ' ' . $userInfo['last_name']);
            }
            $array->responsible_name = $list;
        }
        return $array;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/instructions/{id}",
     *     tags={"Instructions"},
     *     summary="Update instruction API",
     *     description="Update instruction API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateInstructionAPI",
     *     @OA\Parameter(
     *         description="instruction id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Instruction schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Instruction")
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
            $input = $request -> all();
            $instructionData = ObjectItem::find($id);
            if (empty($instructionData)) {
                return $this->responseException('Not found instruction', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'instruction',
                'objectItem' => $instructionData,
            ];
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('instruction', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $instructionData->update($input);
//                if ($user['role_id'] == 1) {
//                    $this->pushNotificationToAllCompanies('Instruction', $instructionData['id'], $instructionData['name'],'update');
//                }

                // update Security
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('instruction', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('instruction', $input, null);
                }

                return $this->responseSuccess($instructionData);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/instructions/{id}",
     *     tags={"Instructions"},
     *     summary="Delete instruction API",
     *     description="Delete instruction API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteInstructionAPI",
     *     @OA\Parameter(
     *         description="instruction id",
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
            $instructionData = ObjectItem::find($id);
            if (empty($instructionData)) {
                return $this->responseException('Not found instruction', 404);
            }
            // instruction-activity
            $activities = ObjectItem::where('source', 'instruction')
                ->where('source_id', $id)
                ->get();
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'instruction',
                'objectItem' => $instructionData,
            ];
            if ($instructionData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('instruction', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($instructionData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Instruction', $instructionData->id, $instructionData->name)) {
                    $instructionData->update(['is_valid' => 0]);
                    if (!empty($activities)) {
                        foreach ($activities as $item) {
                            if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Instruction', $item->id, $item->name)) {
                                $item->update(['is_valid' => 0]);
                            }
                        }
                    }
                    return $this->responseSuccess("Delete instruction success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
