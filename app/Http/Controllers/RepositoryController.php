<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Repository;
use App\Models\RiskElementSource;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Repositories",
 *     description="Repositories APIs",
 * )
 **/
class RepositoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/repositories",
     *     tags={"JobTitles"},
     *     summary="Get Repositories",
     *     description="Get Repositories list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRepositories",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $result = Repository::select('repositories.*',
                    'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'))
                    ->leftJoin('users','repositories.added_by','=','users.id')
                    ->where('repositories.company_id', $user['company_id'])
                    ->whereNull('repositories.restore_date')
                    ->whereNull('repositories.deleted_date')
                    ->get();
                if($result){
                    return $this->responseSuccess($this->filterRepositoryList($result));
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
     *     path="/api/v1/repositories",
     *     tags={"Repositories"},
     *     summary="Create new Repositories",
     *     description="Create new Repositories",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRepositories",
     *     @OA\RequestBody(
     *         description="Repositories schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Repositories")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Repository $repository)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $rules = Repository::$rules;
                $input = $request -> all();

                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newRepository = Repository::create($input);

                return $this->responseSuccess($newRepository,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/repositories/{id}",
     *     tags={"JobTitles"},
     *     summary="Get Repositories by id",
     *     description="Get Repositories by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRepositoriesByIdAPI",
     *     @OA\Parameter(
     *         description="Repositories id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $repositoryData = Repository::find($id);
                if (empty($repositoryData)) {
                    return $this->responseException('Not found repository', 404);
                }
                return $this->responseSuccess($repositoryData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/repositories/{id}",
     *     tags={"JobTitles"},
     *     summary="Update Repositories API",
     *     description="Update Repositories API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRepositoriesAPI",
     *     @OA\Parameter(
     *         description="Repositories id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Repositories schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Repositories")
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                if ($user->role->level > 1) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $rules = Repository::$updateRules;
                $input = $request->all();

                $repositoryData = Repository::find($id);
                if (empty($repositoryData)) {
                    return $this->responseException('Not found repository', 404);
                }
                $input['restore_date'] = new \DateTime('now');
                $input['restore_by'] = $user['id'];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                if ($this->moveOutRepository($repositoryData)) {
                    $repositoryData->update($input);

                    return $this->responseSuccess($repositoryData, 201);
                } else {
                    return $this->responseException('Restore failed!', 404);
                }

            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/repositories/{id}",
     *     tags={"JobTitles"},
     *     summary="Delete Repositories API",
     *     description="Delete Repositories API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteRepositoriesAPI",
     *     @OA\Parameter(
     *         description="Repositories id",
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
            $repositoryData = Repository::find($id);
            if (empty($repositoryData)) {
                return $this->responseException('Not found repository', 404);
            }
            Repository::destroy($id);
            return $this->responseSuccess("Delete repository success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
