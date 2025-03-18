<?php


namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\CategoryV2;
use Illuminate\Http\Request;

    /**
     * @OA\Tag(
     *     name="Categories",
     *     description="Categories APIs",
     * )
     **/
class CategoryControllerV2 extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categoriesNew",
     *     tags={"Categories"},
     *     summary="Get categoriesNew",
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
                $result = CategoryV2::join('users', 'categories_new.added_by', '=', 'users.id')
                    ->where (function ($q) use ($user) {
                        if ($user->role_id > 1) {
//                            $q->whereRaw('FIND_IN_SET(?, categories_new.industry)', [$user['company']['industry']])
                                $q->whereJsonContains('categories_new.industry', $user['company']['industry_id'])
                                    ->where (function ($query) use ($user) {
                                    $query->where('categories_new.company_id', $user['company_id'])
                                        ->orWhere('categories_new.added_by', 1);
                                });
                        } else if ($user->role_id == 1) {
                            $q->where('categories_new.added_by', 1)
                                ->where('categories_new.company_id', null);
                        }
                    })
                    -> select('categories_new.*', 'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'));
                $type = $request->type;
                $typeArray = ['goal', 'task', 'routine', 'instruction', 'risk', 'deviation', 'checklist', 'risk-analysis'];
                if (!empty($request->typeArray)) {
                    $typeArray = $request->typeArray;
                }
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
                } else if (!empty($typeArray)) {
                    $result = $result->whereIn('type', $typeArray)
                        ->get();
                } else {
                    $result = $result->get();
                }

                if ($result) {
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/categoriesNew",
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
                $rules = CategoryV2::$rules;
                if (isset($input['is_priority']) && $input['is_priority']){
                    CategoryV2::where('is_priority', '=', 1)
                        ->where('added_by', '=', 1)
                        ->where('type', '=', $input['type'])
                        ->update(array('is_priority' => 0));
                }
                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['industry'] = $user['company']['industry_id'];
                    $input['company_id'] = $user['company_id'];
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newCategory = CategoryV2::create($input);
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
                $categoryData = CategoryV2::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }
                $categoryData->editPermission = $user->editPermission;
                return $this->responseSuccess($categoryData);
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
                $rules = CategoryV2::$updateRules;
                $input = $request->all();

                $categoryData = CategoryV2::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                if (isset($input['is_priority']) && $input['is_priority']){
                    CategoryV2::where('is_priority', '=', 1)->where('added_by', '=', 1)->where('type', '=', $input['type'])->update(array('is_priority' => 0));
                }

                $categoryData->update($input);
                if ($user['role_id'] == 1) {
                    $this->pushNotificationToAllCompanies('Category', $categoryData['id'], $categoryData['name'],'update', $categoryData['type']);
                }

                return $this->responseSuccess($categoryData);
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
                $categoryData = CategoryV2::find($id);
                if (empty($categoryData)) {
                    return $this->responseException('Not found category', 404);
                }
                if ($this->moveToRepository($user['id'], $user['company_id'], 0, 'Category', $categoryData->id, $categoryData->name)) {
                    $categoryData->update(['is_valid' => 1]);

//                CategoryV2::destroy($id);
                    return $this->responseSuccess("Delete category success");
                }

                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
