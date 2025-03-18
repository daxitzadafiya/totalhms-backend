<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Employee;
use Validator;
use JWTAuth;
use App\Models\AbsenceReason;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="AbsenceReasons",
 *     description="AbsenceReason APIs",
 * )
 **/
class AbsenceReasonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/absenceReasons",
     *     tags={"AbsenceReasons"},
     *     summary="Get absenceReasons",
     *     description="Get absenceReasons list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAbsenceReasons",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (!$user = $this->getAuthorizedUser('absenceSetting', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user['role_id'] == 1) {
                    $result = AbsenceReason::whereNull('company_id')->get();
                } else {
                    $result = AbsenceReason::where('company_id', $user['company_id'])->get();
                }
                if($result){
//                    $result = $this->filterViewList('absenceSetting', $user, $user->filterBy, $result);
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
     *     path="/api/v1/absenceReasons",
     *     tags={"AbsenceReasons"},
     *     summary="Create new absenceReason",
     *     description="Create new absenceReason",
     *     security={{"bearerAuth":{}}},
     *     operationId="createAbsenceReason",
     *     @OA\RequestBody(
     *         description="AbsenceReason schemas",
     *         @OA\JsonContent(ref="#/components/schemas/AbsenceReason")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, AbsenceReason $absenceReason)
    {
        try {
            if (!$user = $this->getAuthorizedUser('absenceSetting', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = AbsenceReason::$rules;
                $input = $request -> all();

                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['company_id'] = $user['company_id'];
                }

                if (!empty($input['processor'])) {
                    $input['processor'] = json_encode($input['processor']);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newAbsenceReason = AbsenceReason::create($input);

                if (!$newAbsenceReason->company_id) {
                    $companies = Company::select('id')->get();
                    if ($companies) {
                        foreach ($companies as $company) {
                            $input['company_id'] = $company->id;
                            $input['related_id'] = $newAbsenceReason->id;
                            $absenceReason = AbsenceReason::create($input);
                        }
                    }
                }

                return $this->responseSuccess($newAbsenceReason,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/absenceReasons/{id}",
     *     tags={"AbsenceReasons"},
     *     summary="Get absenceReason by id",
     *     description="Get absenceReason by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getAbsenceReasonByIdAPI",
     *     @OA\Parameter(
     *         description="absenceReason id",
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
            $absenceReasonData = AbsenceReason::where("id",$id)->first();
            if (empty($absenceReasonData)) {
                return $this->responseException('Not found absenceReason', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'absenceSetting',
                'objectItem' => $absenceReasonData,
            ];
            if (!$user = $this->getAuthorizedUser('absenceSetting', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $absenceReasonData->children_days_off = json_decode($absenceReasonData->children_days_off);

                return $this->responseSuccess($absenceReasonData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/absenceReasons/{id}",
     *     tags={"AbsenceReasons"},
     *     summary="Update absenceReason API",
     *     description="Update absenceReason API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateAbsenceReasonAPI",
     *     @OA\Parameter(
     *         description="absenceReason id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="AbsenceReason schemas",
     *         @OA\JsonContent(ref="#/components/schemas/AbsenceReason")
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
            $rules = AbsenceReason::$updateRules;
            $input = $request -> all();

            $absenceReasonData = AbsenceReason::where("id",$id)->first();
            if (empty($absenceReasonData)) {
                return $this->responseException('Not found absenceReason', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'absenceSetting',
                'objectItem' => $absenceReasonData,
            ];
            if (!$user = $this->getAuthorizedUser('absenceSetting', 'basic', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {

//            $data = [];
//            $data['reset_time_number'] = $input['reset_time_number'];
//            $data['reset_time_unit'] = $input['reset_time_unit'];
//            $data['apply_time_number'] = $input['apply_time_number'];
//            $data['apply_time_unit'] = $input['apply_time_unit'];
//            $data['deadline_registration_number'] = $input['deadline_registration_number'];
//            $data['deadline_registration_unit'] = $input['deadline_registration_unit'];
//            if ($input['type'] == 1) {
//                $data['days_per_interval'] = $input['days_per_interval'];
//                $data['interval'] = $input['interval'];
//            } else {
//                $data['sick_child_days_off'] = $input['sick_child_days_off'];
//                $data['sick_child_days_off_exception'] = $input['sick_child_days_off_exception'];
//                $data['extra_alone_custody'] = $input['extra_alone_custody'];
//                $data['sick_child_max_age'] = $input['sick_child_max_age'];
//                $data['sick_child_max_age_handicapped'] = $input['sick_child_max_age_handicapped'];
//            }

                if (!empty($input['processor'])) {
                    $input['processor'] = json_encode($input['processor']);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $absenceReasonData->update($input);

                return $this->responseSuccess($absenceReasonData, 201);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/absenceReasons/{id}",
     *     tags={"AbsenceReasons"},
     *     summary="Delete absenceReason API",
     *     description="Delete absenceReason API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteAbsenceReasonAPI",
     *     @OA\Parameter(
     *         description="absenceReason id",
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
            $absenceReasonData = AbsenceReason::where("id",$id)->first();
            if (empty($absenceReasonData)) {
                return $this->responseException('Not found absenceReason', 404);
            }
            AbsenceReason::destroy($id);
            return $this->responseSuccess("Delete absenceReason success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
