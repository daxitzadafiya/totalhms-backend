<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Projects",
 *     description="Project APIs",
 * )
 **/
class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/projects",
     *     tags={"Projects"},
     *     summary="Get projects",
     *     description="Get projects list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getProjects",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $result = Project::where('company_id', $user['company_id'])
                    ->leftJoin(DB::raw('(SELECT count(id) as count_attachment, project_id FROM documents WHERE project_id IS NOT NULL GROUP BY project_id) AS PD'), 'projects.id', '=', 'PD.project_id')
                    ->select('projects.*', 'PD.count_attachment')
                    ->get();
                if($result){
                    foreach ($result as $item) {
                        $item->time_left = $this->calRemainingTime($item->deadline);

                        // display added by name
                        $userInfo = User::find($item->added_by);
                        $item->added_by_name = $userInfo->first_name . ' ' . $userInfo->last_name;

                        // display responsible name
//                        $responsible = json_decode($item->responsible);
//                        if ($responsible) {
//                            $item->responsible_emps_name = $this->showResponsibleName($responsible);
//                        }
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
     *     path="/api/v1/projects",
     *     tags={"Projects"},
     *     summary="Create new project",
     *     description="Create new project",
     *     security={{"bearerAuth":{}}},
     *     operationId="createProject",
     *     @OA\RequestBody(
     *         description="Project schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Project $project)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Project::$rules;
                $input = $request -> all();
                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];
                $input['responsible'] = json_encode($input['responsibleArray']);
                //generate project number
                $lastest_record = Project::where('company_id', $user['company_id'])
                    ->orderBy('id', 'DESC')
                    ->first();
                $currentYear = date("Y");
                $newNumber = '00001-' . $currentYear . '-PJ-' . $user['company_id'];
                if ($lastest_record){
                    $projectNumber = $lastest_record->project_number;
                    $year = substr($projectNumber, 6, 4);
                    if ($year == $currentYear){
                        $projectNumber = str_replace('-' . $year . '-PJ-' . $user['company_id'], '', $projectNumber);
                        $projectNumber = number_format($projectNumber) + 1;
                        $projectNumber = sprintf("%05d", $projectNumber);
                        $newNumber = $projectNumber . '-' . $year . '-PJ-' . $user['company_id'];
                    }
                }
                $input['project_number'] = $newNumber;
                if (empty($input['project_number_custom'])) {
                    $input['project_number_custom'] = $input['project_number'];
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newProject = Project::create($input);
                return $this->responseSuccess($newProject,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/projects/{id}",
     *     tags={"Projects"},
     *     summary="Get project by id",
     *     description="Get project by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getProjectByIdAPI",
     *     @OA\Parameter(
     *         description="project id",
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
            $projectData = Project::where("id",$id)->first();
            if (empty($projectData)) {
                return $this->responseException('Not found project', 404);
            }
            $logoOriginalName = Document::where("project_id", $id)
                ->where('added_from', '=', 9)
                ->first();
            if ($logoOriginalName) {
                $projectData->header_image_original_file_name = $logoOriginalName->original_file_name;
            }
            return $this->responseSuccess($projectData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/projects/{id}",
     *     tags={"Projects"},
     *     summary="Update project API",
     *     description="Update project API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateProjectAPI",
     *     @OA\Parameter(
     *         description="project id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Project schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
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
            $rules = Project::$updateRules;
            $input = $request -> all();

            $projectData = Project::where("id",$id)->first();
            if (empty($projectData)) {
                return $this->responseException('Not found project', 404);
            }

            $input['responsible'] = json_encode($input['responsibleArray']);

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $projectData->update($input);

            return $this->responseSuccess($projectData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/projects/{id}",
     *     tags={"Projects"},
     *     summary="Delete project API",
     *     description="Delete project API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteProjectAPI",
     *     @OA\Parameter(
     *         description="project id",
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
            $projectData = Project::where("id",$id)->first();
            if (empty($projectData)) {
                return $this->responseException('Not found project', 404);
            }
            Project::destroy($id);
            return $this->responseSuccess("Delete project success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
