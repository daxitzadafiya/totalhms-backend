<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Industry;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Categories APIs",
 * )
 **/
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     tags={"Categories"},
     *     summary="Get categories",
     *     description="Get categories list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getCategories",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('category', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $result = Category::join('users','categories.added_by','=','users.id')
                    -> where (function ($q) use ($user) {
                        if ($user->role_id > 1) {
                            $q-> whereRaw('FIND_IN_SET(?, categories.industry_id)', [$user['company']['industry_id']])
                                -> where (function ($query) use ($user) {
                                    $query-> where('categories.company_id', $user['company_id'])
                                        -> orWhere('categories.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q->where('categories.added_by', 1)
                                ->where('categories.company_id', null);
                        }
                    })
                    -> select('categories.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'));
                $type = $request->type;
                if($type){
                    $result = $result->where ('type', $type);
                    $name = $request->name;
                    if ($name){
                        $result = $result->where("name", $name);
                    }
                    $added_from = $request->added_from;
                    if ($added_from){
                        $result = $result->where("added_from", $added_from);
                    }
                    $result = $result->get();
                } else {
                    $result = $result->get();
                }

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
     *     path="/api/v1/categories",
     *     tags={"Categories"},
     *     summary="Create new category",
     *     description="Create new category",
     *     security={{"bearerAuth":{}}},
     *     operationId="createCategory",
     *     @OA\RequestBody(
     *         description="Category schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
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
            $input = $request -> all();
            if (!$user = $this->getAuthorizedUser('category', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Category::$rules;
                if (isset($input['is_primary']) && $input['is_primary']){
                    Category::where('is_primary', '=', 1)
                        ->where('added_by', '=', 1)
                        ->where('type', '=', $input['type'])
                        ->update(array('is_primary' => 0));
                }
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

                $newCategory = Category::create($input);
                if ($newCategory && $user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Category', $newCategory['id'], $newCategory['name'],'create', $newCategory['type']);
                }

                return $this->responseSuccess($newCategory);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get category by id",
     *     description="Get category by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getCategoryByIdAPI",
     *     @OA\Parameter(
     *         description="category id",
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
            if (!$user = $this->getAuthorizedUser('category', 'detail', 'show', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $categoryData = Category::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }
                $categoryData->editPermission = $user->editPermission;
                return $this->responseSuccess($categoryData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update category API",
     *     description="Update category API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateCategoryAPI",
     *     @OA\Parameter(
     *         description="category id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Category schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
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
            if (!$user = $this->getAuthorizedUser('category', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Category::$updateRules;
                $input = $request->all();

                $categoryData = Category::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                if (isset($input['is_primary']) && $input['is_primary']){
                    Category::where('is_primary', '=', 1)->where('added_by', '=', 1)->where('type', '=', $input['type'])->update(array('is_primary' => 0));
                }

                $categoryData->update($input);
                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Category', $categoryData['id'], $categoryData['name'],'update', $categoryData['type']);
                }

                return $this->responseSuccess($categoryData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete category API",
     *     description="Delete category API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteCategoryAPI",
     *     @OA\Parameter(
     *         description="category id",
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
            if (!$user = $this->getAuthorizedUser('category', 'basic', 'destroy', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $categoryData = Category::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 0, 'Category', $categoryData->id, $categoryData->name)) {
                    $categoryData->update(['disable_status' => 1]);

//                Category::destroy($id);
                    return $this->responseSuccess("Delete category success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
