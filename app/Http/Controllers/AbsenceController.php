<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Employee;
use App\Models\UserTask;
use Validator;
use JWTAuth;
use App\Models\Absence;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Absences",
 *     description="Absence APIs",
 * )
 **/
class AbsenceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/absences",
     *     tags={"Absences"},
     *     summary="Get absences",
     *     description="Get absences list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAbsences",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (!$user = $this->getAuthorizedUser('absence', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
//                $checkPermission = $user->hasAccess('update-absence');
                $result = Absence::where ('absences.company_id', $user->company_id);
//                if (!$checkPermission) {
//                    $result = $result->where('added_by', $user['id']);
//                }
                $result = $result->with(['user_added', 'reason', 'user_processed', 'attachment'])
                    ->get();
                if ($result){
                    $result = $this->filterViewList('absence', $user, $user->filterBy, $result);
                    foreach ($result as $key => $item) {
                        $processorArray = json_decode($item->processor);
                        if (!empty($item['attachment'])) {
                            $item['attachment']['url'] = config('app.app_url') . "/api/v1/uploads/".  $item['attachment']['uri'];
                        }
                        if ($item->added_by == $user['id']) {
                            $item->absenceRole = 'creator';
                        } elseif (in_array($user['id'], $processorArray)) {
                            $item->absenceRole = 'processor';
                        } else {
                            unset($result[$key]);
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
     *     path="/api/v1/absences",
     *     tags={"Absences"},
     *     summary="Create new absence",
     *     description="Create new absence",
     *     security={{"bearerAuth":{}}},
     *     operationId="createAbsence",
     *     @OA\RequestBody(
     *         description="Absence schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Absence")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Absence $absence)
    {
        try {
            if (!$user = $this->getAuthorizedUser('absence', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Absence::$rules;
                $input = $request -> all();
                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];
                $processorArray = $input['processor'];
                if (!empty($input['processor'])) {
                    $input['processor'] = json_encode($input['processor']);
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newAbsence = Absence::create($input);

                if ($input['addNewAbsence'] && !empty($input['new_absence_info'])) {
                    $this->updateAbsenceInfoOfEmployee($newAbsence, $input['new_absence_info']);
                } else {
                    $this->updateAbsenceInfoOfEmployee($newAbsence);
                }

                $this->pushNotification($user['id'], $user['company_id'], 4, $processorArray, 'absence processing');

//                $rulesUserTask = UserTask::$rules;
//                $companyData = Company::where("id",$user['company_id'])->first();
//                $inputUserTask['added_by'] = $user['id'];
//                $inputUserTask['company_id'] = $user['company_id'];
//                $inputUserTask['name'] = 'Processing absence';
//                $inputUserTask['tasks'] = [];
//                if ($processorArray) {
//                    $taskItem['name'] = 'Processing absence abc xyz';
//                    $taskAssignees = [];
//                    $responsiblePerson = [];
//                    foreach ($processorArray as $item) {
//                        array_push($taskAssignees, $item);
//                        array_push($responsiblePerson, $item);
//                    }
//                    $taskItem['taskAssignees'] = $taskAssignees;
//                    $taskItem['responsiblePerson'] = $responsiblePerson;
//
//                    array_push($inputUserTask['tasks'], $taskItem);
//                }
//                if (empty($inputUserTask['industry_id'])) {
//                    $inputUserTask['industry_id'] = $companyData['industry_id'];
//                }
//
//                $validatorUserTask = Validator::make($inputUserTask, $rulesUserTask);
//
//                if ($validatorUserTask->fails()) {
//                    $errors = ValidateResponse::make($validatorUserTask);
//                    return $this->responseError($errors,400);
//                }
//                $newUserTask = UserTask::create($inputUserTask);
//                $this->addTaskByType($inputUserTask, $user['id'], $user['company_id'], 'User', $newUserTask->id, true);
            }

            return $this->responseSuccess($newAbsence,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/absences/{id}",
     *     tags={"Absences"},
     *     summary="Get absence by id",
     *     description="Get absence by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAbsenceByIdAPI",
     *     @OA\Parameter(
     *         description="absence id",
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
            $absenceData = Absence::where("id",$id)->first();
            if (empty($absenceData)) {
                return $this->responseException('Not found absence', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'absence',
                'objectItem' => $absenceData,
            ];
            if (!$user = $this->getAuthorizedUser('absence', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                return $this->responseSuccess($absenceData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/absences/{id}",
     *     tags={"Absences"},
     *     summary="Update absence API",
     *     description="Update absence API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateAbsenceAPI",
     *     @OA\Parameter(
     *         description="absence id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Absence schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Absence")
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
            $rules = Absence::$updateRules;
            $input = $request->all();
            if (!empty($input['processor'])) {
                $input['processor'] = json_encode($input['processor']);
            }

            $absenceData = Absence::where("id",$id)->first();
            if (empty($absenceData)) {
                return $this->responseException('Not found absence', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'absence',
                'objectItem' => $absenceData,
            ];
            if (!$user = $this->getAuthorizedUser('absence', 'basic', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

                if ($input['status'] > 1) {
                    $input['processed_by'] = $user['id'];
                }
                //status: 3 = approved
                if ($absenceData->status == 3 && $input['status'] == 4 && $input['absence_reason_id_added_by_admin']) {
                    $input['status'] = 5; //reject && change reason
                }
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $absenceData->update($input);

                $this->updateAbsenceInfoOfEmployee($absenceData);

                if ($absenceData->absence_reason_id_added_by_admin) {
                    $inputReasonAlt = $input['reason_alt'];
                    $input['absence_reason_id'] = $absenceData->absence_reason_id_added_by_admin;
                    $input['status'] = 6; //status: 6 = added automatically
                    $input['absence_reason_id_added_by_admin'] = null;
                    $input['illegal'] = $inputReasonAlt['illegal'];
                    $input['parent_id'] = $absenceData->id;

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors, 400);
                    }

                    $newAbsence = Absence::create($input);

                    if ($inputReasonAlt['addNewAbsence'] && !empty($inputReasonAlt['new_absence_info'])) {
                        $this->updateAbsenceInfoOfEmployee($newAbsence, $inputReasonAlt['new_absence_info']);
                    } else {
                        $this->updateAbsenceInfoOfEmployee($newAbsence);
                    }

                }

                return $this->responseSuccess($absenceData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateAbsenceInfoOfEmployee($absenceData, $newAbsenceInfo = [])
    {
        $employeeData = Employee::where('user_id', $absenceData->added_by)->first();
        if (empty($employeeData)) {
            return $this->responseException('Not found employee', 404);
        }

        $absence_info = json_decode($employeeData->absence_info);

        if (!empty($newAbsenceInfo)) {
            $newAbsenceInfoItem = $newAbsenceInfo;
            //status: 6 = added automatically
            if ($absenceData->status == 6) {
                if ($newAbsenceInfoItem['class_of_absence'] == 'interval') {
                    if ($absenceData->illegal) {
                        $newAbsenceInfoItem['used_illegal_interval_absence'] += 1;
                    } else {
                        $newAbsenceInfoItem['used_interval_absence'] += 1;
                    }
                } elseif ($newAbsenceInfoItem['class_of_absence'] == 'day') {
                    if ($absenceData->illegal) {
                        $newAbsenceInfoItem['used_illegal_days_off'] += $absenceData->duration_time;
                    } else {
                        $newAbsenceInfoItem['used_days_off'] += $absenceData->duration_time;
                    }
                }
            } else {
                if ($newAbsenceInfoItem['class_of_absence'] == 'interval') {
                    if ($absenceData->illegal) {
                        $newAbsenceInfoItem['pending_illegal_interval_absence'] += 1;
                    } else {
                        $newAbsenceInfoItem['pending_interval_absence'] += 1;
                    }
                } elseif ($newAbsenceInfoItem['class_of_absence'] == 'day') {
                    if ($absenceData->illegal) {
                        $newAbsenceInfoItem['pending_illegal_days_off'] += $absenceData->duration_time;
                    } else {
                        $newAbsenceInfoItem['pending_days_off'] += $absenceData->duration_time;
                    }
                }
            }
            array_push($absence_info, $newAbsenceInfoItem);
        } else {
            foreach ($absence_info as $item) {
                if ($item->absence_reason_id == $absenceData->absence_reason_id) {
                    //if status: 3 = approve, 4 = reject, 5 = reject and change reason (approved before)
                    if ($absenceData->status == 1) {
                        if ($item->class_of_absence == 'interval') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_interval_absence += 1;
                            } else {
                                $item->pending_interval_absence += 1;
                            }
                        } elseif ($item->class_of_absence == 'day') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_days_off += $absenceData->duration_time;
                            } else {
                                $item->pending_days_off += $absenceData->duration_time;
                            }
                        }
                    } elseif ($absenceData->status == 3) {
                        if ($item->class_of_absence == 'interval') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_interval_absence -= 1;
                                $item->used_illegal_interval_absence += 1;
                            } else {
                                $item->pending_interval_absence -= 1;
                                $item->used_interval_absence += 1;
                            }
                        } elseif ($item->class_of_absence == 'day') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_days_off -= $absenceData->duration_time;
                                $item->used_illegal_days_off += $absenceData->duration_time;
                            } else {
                                $item->pending_days_off -= $absenceData->duration_time;
                                $item->used_days_off += $absenceData->duration_time;
                            }
                        }
                    } elseif ($absenceData->status == 4) {
                        if ($item->class_of_absence == 'interval') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_interval_absence -= 1;
                            } else {
                                $item->pending_interval_absence -= 1;
                            }
                        } elseif ($item->class_of_absence == 'day') {
                            if ($absenceData->illegal) {
                                $item->pending_illegal_days_off -= $absenceData->duration_time;
                            } else {
                                $item->pending_days_off -= $absenceData->duration_time;
                            }
                        }
                    } elseif ($absenceData->status == 5) {
                        if ($item->class_of_absence == 'interval') {
                            if ($absenceData->illegal) {
                                $item->used_illegal_interval_absence -= 1;
                            } else {
                                $item->used_interval_absence -= 1;
                            }
                        } elseif ($item->class_of_absence == 'day') {
                            if ($absenceData->illegal) {
                                $item->used_illegal_days_off -= $absenceData->duration_time;
                            } else {
                                $item->used_days_off -= $absenceData->duration_time;
                            }
                        }
                    }
                }
            }
        }
        $data = array();
        $data['absence_info'] = json_encode($absence_info);

        $employeeData->update($data);

        return $this->responseSuccess($employeeData,201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/absences/{id}",
     *     tags={"Absences"},
     *     summary="Delete absence API",
     *     description="Delete absence API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteAbsenceAPI",
     *     @OA\Parameter(
     *         description="absence id",
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
            $absenceData = Absence::where("id",$id)->first();
            if (empty($absenceData)) {
                return $this->responseException('Not found absence', 404);
            }
            Absence::destroy($id);
            return $this->responseSuccess("Delete absence success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
