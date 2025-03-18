<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentNew;
use App\Models\IntervalSetting;
use App\Models\JobTitle;
use App\Models\Report;
use App\Models\Repository;
use App\Models\RiskElement;
use App\Models\RiskElementSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RiskElementSource",
 *     description="RiskElementSource APIs",
 * )
 **/
class RiskElementSourceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/riskElementSource",
     *     tags={"RiskElementSource"},
     *     summary="Get riskElementSource",
     *     description="Get riskElementSource list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRiskElementSource",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('risk area', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }else{
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = RiskElementSource::leftJoin('departments', 'risk_element_sources.department_id','=', 'departments.id')
                    ->leftJoin('job_titles', 'risk_element_sources.job_title_id','=', 'job_titles.id')
                    ->join('users', 'risk_element_sources.added_by', '=', 'users.id')
                    ->leftJoin('categories', 'risk_element_sources.category_id', '=', 'categories.id')
                    //                    ->where('risk_element_sources.company_id', $user->company_id)
                    ->where('risk_element_sources.delete_status', 0);

                $result = $result-> where (function ($q) use ($user) {
                    if ($user->role_id > 1) {
                        $q-> whereRaw('FIND_IN_SET(?, risk_element_sources.industry_id)', [$user['company']['industry_id']])
                            -> where (function ($query) use ($user) {
                                $query-> where('risk_element_sources.company_id', $user['company_id'])
                                    -> orWhere('risk_element_sources.added_by', 1);
                            });
                    } else if ($user->role_id == 1) {
                        $q-> where('risk_element_sources.added_by', 1);
                    }
                })
                    ->with(['user'])
                    ->select('risk_element_sources.*',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                        'categories.name as category_name')
                    ->get();
                if($result) {
                    $result = $this->filterViewList('risk area', $user, $user->filterBy, $result, $orderBy, $limit);
                    foreach ($result as $riskElementSource) {
                        if (!empty($riskElementSource['attachment'])) {
                            $riskElementSource['attachment']['url'] = config('app.app_url') . "/api/v1/uploads/".  $riskElementSource['attachment']['uri'];
                        }
                        $reportedUser = User::find($riskElementSource['added_by']);
                        $riskElementSource['added_by_name'] = $reportedUser['first_name'] . " " . $reportedUser['last_name'];
                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/riskElementSource",
     *     tags={"RiskElementSource"},
     *     summary="Create new RiskElementSource",
     *     description="Create new RiskElementSource",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRiskElementSource",
     *     @OA\RequestBody(
     *         description="RiskElementSource schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RiskElementSource")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, RiskElementSource $riskElementSource)
    { 
        try {
            $input = $request -> all();
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('risk area', $permissionName, 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = RiskElementSource::$rules;
//                $input = $request -> all();
                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }
//                $input['company_id'] = $user['company_id'];

                $input['update_history'] = json_encode($this->setUpdateHistory('created', $user['id'], [], 'object', 'risk element', $input['name']));

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newRiskElementSource = RiskElementSource::create($input);

                // Handle to save Security/ Connect to
                $this->createSecurityObject($newRiskElementSource, $input);

                return $this->responseSuccess($newRiskElementSource);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/riskElementSource/{id}",
     *     tags={"RiskElementSource"},
     *     summary="Get riskElementSource by id",
     *     description="Get riskElementSource by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRiskElementSourceByIdAPI",
     *     @OA\Parameter(
     *         description="riskElementSource id",
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
            $riskElementSourceData = RiskElementSource::where("id", $id)
                ->first();
            if (empty($riskElementSourceData)) {
                return $this->responseException('Not found RiskElementSource', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk area',
                'objectItem' => $riskElementSourceData,
            ];

            if (!$user = $this->getAuthorizedUser('risk area', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $updateHistory = json_decode($riskElementSourceData['update_history']);

                $riskElementSourceData->history = $this->getUpdateHistory($updateHistory);

                $relatedRiskAnalysis = RiskElement::where('type_id', $id)
                    ->leftJoin('risk_analysis', 'risk_elements.risk_analysis_id', 'risk_analysis.id')
                    ->select('risk_analysis.name', 'risk_elements.probability', 'risk_elements.consequence', 'risk_elements.updated_at')
                    ->orderBy('risk_elements.id', 'DESC')
                    ->limit(5)
                    ->get();

                if (!empty($relatedRiskAnalysis)) {
                    $riskElementSourceData->relatedRiskAnalysis = $relatedRiskAnalysis;
                } else {
                    $riskElementSourceData->relatedRiskAnalysis = '';
                }
                $riskElementSourceData->editPermission = $user->editPermission;

                $riskElementSourceData->attachment = $this->getObjectAttachment('risk', $riskElementSourceData->id);

                // get Security information
                $this->getSecurityObject('risk', $riskElementSourceData);

                return $this->responseSuccess($riskElementSourceData);
            }

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/riskElementSource/{id}",
     *     tags={"RiskElementSource"},
     *     summary="Update riskElementSource API",
     *     description="Update riskElementSource API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRiskElementSourceAPI",
     *     @OA\Parameter(
     *         description="riskElementSource id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="RiskElementSource schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RiskElementSource")
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
            $rules = RiskElementSource::$updateRules;
            $input = $request -> all();

            $riskElementSourceData = RiskElementSource::where("id",$id)->first();
            if (empty($riskElementSourceData)) {
                return $this->responseException('Not found RiskElementSource', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk area',
                'objectItem' => $riskElementSourceData,
            ];
            if (!empty($input['is_template'])) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }
            if (!$user = $this->getAuthorizedUser('risk area', $permissionName, 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $historyArray = json_decode($riskElementSourceData->update_history);

                if ($riskElementSourceData['name'] != $input['name']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'name', 'name', $riskElementSourceData['name'], $input['name']);
                }
                if ($riskElementSourceData['is_public'] != $input['is_public']) {
                    $old_content = 'Yes';
                    $new_content = 'No';
                    if ($input['is_public'] == 1) {
                        $old_content = 'No';
                        $new_content = 'Yes';
                    }
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'is_public', 'share with company', $old_content, $new_content);
                }
                if ($riskElementSourceData['job_title_id'] != $input['job_title_id']) {
                    if ($riskElementSourceData['job_title_id']) {
                        $old_content = JobTitle::find($riskElementSourceData['job_title_id'])->name;
                    } else {
                        $old_content = 'None';
                    }
                    if ($input['job_title_id']) {
                        $new_content = JobTitle::find($input['job_title_id'])->name;
                    } else {
                        $new_content = 'None';
                    }
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'job_title_id', 'connect to job title', $old_content, $new_content);
                }
                if ($riskElementSourceData['department_id'] != $input['department_id']) {
                    if ($riskElementSourceData['department_id']) {
                        $old_content = Department::find($riskElementSourceData['department_id'])->name;
                    } else {
                        $old_content = 'None';
                    }
                    if ($input['department_id']) {
                        $new_content = Department::find($input['department_id'])->name;
                    } else {
                        $new_content = 'None';
                    }
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'department_id', 'connect to department', $old_content, $new_content);
                }
                if ($riskElementSourceData['description'] != $input['description']) {
                    $old_content = $riskElementSourceData['description'];
                    $new_content = $input['description'];
                    if (!$riskElementSourceData['description']) {
                        $old_content = 'None';
                    }
                    if (!$input['description']) {
                        $new_content = 'None';
                    }

                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'description', 'description', $old_content, $new_content);
                }
                if ($riskElementSourceData['type'] != $input['type'] || $riskElementSourceData['type_id'] != $input['type_id']) {
                    $historyArray = $this->setUpdateHistory('updated', $user['id'], $historyArray, 'attachment', 'attachment');
                }

                $input['update_history'] = json_encode($historyArray);

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $riskElementSourceData->update($input);

                if (!$input['type_of_attachment'] && !empty($riskElementSourceData->attachment)) {
                    $oldAttachment = DocumentNew::where('company_id', $user['company_id'])
                        ->where('type', 'report')
                        ->where('object_type', 'risk')
                        ->where('object_id', $id)
                        ->first();
                    if (empty($oldAttachment)) {
                        return $this->responseException('Not found attachment', 404);
                    }
                    $oldAttachment->destroy($oldAttachment->id);
                }

                // update Security
                if ($user['id'] != $input['added_by']) {
                    $this->updateSecurityObject('risk', $input, $user['id']);
                } else { // update by creator
                    $this->updateSecurityObject('risk', $input, null);
                }

                return $this->responseSuccess($riskElementSourceData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/riskElementSource/{id}",
     *     tags={"RiskElementSource"},
     *     summary="Delete riskElementSource API",
     *     description="Delete riskElementSource API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteRiskElementSourceAPI",
     *     @OA\Parameter(
     *         description="riskElementSource id",
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
            $riskElementSourceData = RiskElementSource::find($id);
            if (empty($riskElementSourceData)) {
                return $this->responseException('Not found RiskElementSource', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'risk area',
                'objectItem' => $riskElementSourceData,
            ];
            if ($riskElementSourceData->is_template) {
                $permissionName = 'resource';
            } else {
                $permissionName = 'basic';
            }

            if (!$user = $this->getAuthorizedUser('risk area', $permissionName, 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($riskElementSourceData->company_id) && $user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $riskAttachment = '';
                if ($riskElementSourceData->type == 'attachment') {
//                    $riskAttachment = Document::where('risk_element_source_id', $id)->first();
                    $riskAttachment = DocumentNew::where('company_id', $user['company_id'])
                        ->where('object_type', 'risk')
                        ->where('object_id', $id)
                        ->first();
                    if (empty($riskAttachment)) {
                        return $this->responseException('Not found risk Attachment', 404);
                    }
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 1, 'Risk element', $riskElementSourceData->id, $riskElementSourceData->name, $riskAttachment)) {
                    $riskElementSourceData->update(['delete_status' => 1]);

//                RiskElementSource::destroy($id);
                    return $this->responseSuccess("Delete risk element success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
