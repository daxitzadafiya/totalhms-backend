<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use Validator;
use JWTAuth;
use App\Models\AbsenceReason;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="JobTitles",
 *     description="JobTitles APIs",
 * )
 **/
class JobTitleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/jobTitles",
     *     tags={"JobTitles"},
     *     summary="Get jobTitles",
     *     description="Get jobTitles list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getJobTitles",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('job title', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $getByDepartment = (int)$request->department_id;
                $getExceptUserLogged = $request->getDefineGroup;
                $getResourceByRole = $request->getResourceByRole;
                $result = JobTitle::leftJoin('roles', 'job_titles.role_id','=', 'roles.id');
                if ($user['role_id'] == 1) {
                    $result = $result->whereNull('job_titles.company_id');
                } else {
                    if ($getResourceByRole) {
                        $result = $result->whereNull('job_titles.company_id')
                            ->whereRaw('FIND_IN_SET(?, job_titles.industry_id)', [$user['company']['industry_id']])
                            ->where('job_titles.role_id', $getResourceByRole);
                    } else {
                        $result = $result->where('job_titles.company_id', $user['company_id']);
                        if ($getByDepartment) {
                            $result = $result->whereJsonContains('department', $getByDepartment);
                        }
                        if ($getExceptUserLogged) {
                            $result = $result->where('job_titles.id', '<>', $user->employee->job_title_id);
                        }
                    }
                }
                $result = $result->select('job_titles.*', 'roles.id as role_id', 'roles.name as role_name')
                    ->get();
                if($result){
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
     *     path="/api/v1/jobTitles",
     *     tags={"JobTitles"},
     *     summary="Create new jobTitles",
     *     description="Create new jobTitles",
     *     security={{"bearerAuth":{}}},
     *     operationId="createJobTitles",
     *     @OA\RequestBody(
     *         description="JobTitles schemas",
     *         @OA\JsonContent(ref="#/components/schemas/JobTitles")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, JobTitle $jobTitle)
    {
        try {
            if (!$user = $this->getAuthorizedUser('job title', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $input = $request -> all();

                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['industry_id'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                    $input['department'] = json_encode($input['department']);
                }

                $roleInfo = Role::find($input['role_id']);
                if (empty($roleInfo)) {
                    return $this->responseException('Not found role', 404);
                }
                $input['role_name'] = $roleInfo->name;

                $rules = JobTitle::$rules;
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newJobTitle = JobTitle::create($input);
                if ($newJobTitle && $user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Job title', $newJobTitle['id'], $newJobTitle['name'],'create');
                }

                return $this->responseSuccess($newJobTitle,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/jobTitles/{id}",
     *     tags={"JobTitles"},
     *     summary="Get jobTitles by id",
     *     description="Get jobTitles by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getJobTitlesByIdAPI",
     *     @OA\Parameter(
     *         description="jobTitles id",
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
            $jobTitleData = JobTitle::leftJoin('roles', 'job_titles.role_id','=', 'roles.id')
                ->where('job_titles.id', $id)
                ->select('job_titles.*', 'roles.id as role_id', 'roles.name as role_name')
                ->first();
            if (empty($jobTitleData)) {
                return $this->responseException('Not found job title', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'job title',
                'objectItem' => $jobTitleData,
            ];
            if (!$user = $this->getAuthorizedUser('job title', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $jobTitleData->editPermission = $user->editPermission;
                return $this->responseSuccess($jobTitleData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/jobTitles/{id}",
     *     tags={"JobTitles"},
     *     summary="Update jobTitles API",
     *     description="Update jobTitles API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateJobTitlesAPI",
     *     @OA\Parameter(
     *         description="jobTitles id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="jobTitles schemas",
     *         @OA\JsonContent(ref="#/components/schemas/JobTitles")
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
            if (!$user = $this->getAuthorizedUser('job title', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $jobTitleData = JobTitle::find($id);
                if (empty($jobTitleData)) {
                    return $this->responseException('Not found job title', 404);
                }

                $input = $request->all();
                if ($user['role_id'] > 1) {
                    $input['department'] = json_encode($input['department']);
                }

                $rules = JobTitle::$updateRules;
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $jobTitleData->update($input);
                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Job title', $jobTitleData['id'], $jobTitleData['name'],'update');
                }

                return $this->responseSuccess($jobTitleData, 201);

            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/jobTitles/{id}",
     *     tags={"JobTitles"},
     *     summary="Delete jobTitles API",
     *     description="Delete jobTitles API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteJobTitlesAPI",
     *     @OA\Parameter(
     *         description="jobTitles id",
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
            $jobTitleData = JobTitle::find($id);
            if (empty($jobTitleData)) {
                return $this->responseException('Not found job title', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'job title',
                'objectItem' => $jobTitleData,
            ];
            if (!$user = $this->getAuthorizedUser('job title', 'basic', 'destroy', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($this->moveToRepository($user['id'], $user['company_id'], 0, 'Job title', $jobTitleData->id, $jobTitleData->name)) {
                    $jobTitleData->update(['disable_status' => 1]);

//                    JobTitle::destroy($id);
                    return $this->responseSuccess("Delete jobTitles success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function list(Request $request)
    {
        try {
            if (!$user = $this->getAuthorizedUser('job title', 'basic', 'destroy', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $result = JobTitle::where('job_titles.company_id', $request->company_id)
                ->whereJsonContains('department', $request->department_id)->get();

            if ($result) {
                return $this->responseSuccess($result);
            }
            return $this->responseSuccess([]);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
