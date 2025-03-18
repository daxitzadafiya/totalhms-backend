<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\InstructionActivity;
use App\Models\Security;
use Validator;
use JWTAuth;
use App\Models\Instruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Instructions",
 *     description="Instruction APIs",
 * )
 **/
class InstructionOldController extends Controller
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
        try{
            if (!$user = $this->getAuthorizedUser('instruction', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $getByProjectID = $request->getByProjectID;
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = Instruction::join('users', 'instructions.added_by','=', 'users.id')
                    ->with(['instructionActivities'])
                    ->join('categories','categories.id','=','instructions.category_id')
                    ->leftJoin('departments','departments.id','=','instructions.department_id')
                    ->leftJoin('projects','projects.id','=','instructions.project_id')
                    ->leftJoin('job_titles','job_titles.id','=','instructions.job_title_id')
                    ->leftJoin(DB::raw('(SELECT count(id) as count_activity, instruction_id FROM instruction_activities WHERE instruction_id IS NOT NULL GROUP BY instruction_id) AS IA'), 'instructions.id', '=', 'IA.instruction_id')
                    ->where('instructions.delete_status', 0);
                if ($getByProjectID) {
                    $result = $result->where('project_id', $getByProjectID);
                }
                $result = $result-> where (function ($q) use ($user) {
                        if ($user->role_id > 1) {
                            $q-> whereRaw('FIND_IN_SET(?, instructions.industry_id)', [$user['company']['industry_id']])
                                -> where (function ($query) use ($user) {
                                    $query-> where('instructions.company_id', $user['company_id'])
                                        -> orWhere('instructions.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q-> where('instructions.added_by', 1);
                        }
                    })
                    ->select('instructions.*', 'users.email as added_by_email',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name', 'departments.name as department_name', 'projects.name as project_name', 'job_titles.name as job_title_name', 'IA.count_activity')
                    ->get();
                if($result){
                    $result = $this->filterViewList('instruction', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $item) {
                        $item->count_related_object = 0;
                        if ($item['is_template']) {
                            $countRelatedObject = Instruction::where('parent_id', $item['id'])->count();

                            if ($countRelatedObject > 0) {
                                $item->count_related_object = $countRelatedObject;
                            }
                        }
                    }
                    return $this->responseSuccess($result);
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
            } else{
                $rules = Instruction::$rules;
                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newInstruction = Instruction::create($input);
                if ($newInstruction && $user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Instruction', $newInstruction['id'], $newInstruction['name'],'create');
                }

                //Handle to create activity
                $activities = $input['activities'];
                foreach ($activities as $item) {
                    $activityRules = InstructionActivity::$rules;
                    $item['instruction_id'] = $newInstruction->id;
//                    if (empty($item['assignee'])) {
//                        $item['assignee'] = $user['id'];
//                    }
                    if (!empty($item['employee'])) {
                        $item['assigned_employee'] = json_encode($item['employee']);
                    }
                    if (!empty($item['department'])) {
                        $item['assigned_department'] = json_encode($item['department']);
                    }

                    $activityValidator = Validator::make($item, $activityRules);

                    if ($activityValidator->fails()) {
                        $errors = ValidateResponse::make($activityValidator);
                        return $this->responseError($errors,400);
                    }
                    InstructionActivity::create($item);
                }
                $newInstruction->instruction_activities = $activities;

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newInstruction, $input);

                return $this->responseSuccess($newInstruction);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
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
            $instructionData = Instruction::where("id",$id)
                ->with(['instructionActivities'])
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
                $instructionData->count_related_object = 0;
                $instructionData->related_objects = '';
                if ($instructionData['is_template']) {
                    $relatedObject = Instruction::leftJoin('users', 'instructions.added_by','=', 'users.id')
                        ->leftJoin('companies', 'instructions.company_id','=', 'companies.id')
                        ->where('parent_id', $id);
                    if ($user->filterBy != 'super admin') {
                        $relatedObject = $relatedObject->where('instructions.company_id', $user['company_id']);
                    }
                    $relatedObject = $relatedObject->select('instructions.id', 'instructions.name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'companies.name as company_name')
                        ->get();

                    if (count($relatedObject) > 0) {
                        $instructionData->count_related_object = count($relatedObject);
                        $instructionData->related_objects = $relatedObject;
                    }
                }
                $instructionData->editPermission = $user->editPermission;

                // get Security information
                $this->getSecurityObject('instruction', $instructionData);

                return $this->responseSuccess($instructionData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
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
            $rules = Instruction::$updateRules;
            $input = $request -> all();

            $instructionData = Instruction::where("id",$id)->first();
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
                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Instruction', $instructionData['id'], $instructionData['name'],'update');
                }

                //Handle to update activity
                $activities = $input['activities'];
                $oldActivities = InstructionActivity::where('instruction_id', $id)->get();

                foreach ($oldActivities as $oldActivity) {
                    $key = array_search($oldActivity['id'], array_column($activities, 'id'));
                    if ($key > -1) {
                        //update activity
                        $activityRules = InstructionActivity::$updateRules;

                        $activityData = InstructionActivity::where("id", $oldActivity->id)->first();
                        if (empty($activityData)) {
                            return $this->responseException('Not found activity', 404);
                        }

                        $activities[$key]['assigned_employee'] = json_encode($activities[$key]['employee']);
                        $activities[$key]['assigned_department'] = json_encode($activities[$key]['department']);

                        $activityValidator = Validator::make($activities[$key], $activityRules);

                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($activityValidator);
                            return $this->responseError($errors, 400);
                        }
                        $activityData->update($activities[$key]);
                        $activities[$key]['updated'] = true;
                    } else {
                        //delete activity
                        $activityData = InstructionActivity::where("id", $oldActivity->id)->first();
                        if (empty($activityData)) {
                            return $this->responseException('Not found activity', 404);
                        }
                        InstructionActivity::destroy($oldActivity->id);
                    }
                }
                foreach ($activities as $item) {
                    if (!isset($item['updated'])) {
                        $activityRules = InstructionActivity::$rules;
                        $item['instruction_id'] = $instructionData->id;;
                        $item['assigned_employee'] = json_encode($item['employee']);
                        $item['assigned_department'] = json_encode($item['department']);
                        $activityValidator = Validator::make($item, $activityRules);

                        if ($activityValidator->fails()) {
                            $errors = ValidateResponse::make($activityValidator);
                            return $this->responseError($errors, 400);
                        }
                        InstructionActivity::create($item);
                    }
                }

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
            $instructionData = Instruction::where("id",$id)
                ->with(['instructionActivities'])
                ->first();
            if (empty($instructionData)) {
                return $this->responseException('Not found instruction', 404);
            }
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
                    $instructionData->update(['delete_status' => 1]);

//                    Instruction::destroy($id);
                    return $this->responseSuccess("Delete instruction success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
