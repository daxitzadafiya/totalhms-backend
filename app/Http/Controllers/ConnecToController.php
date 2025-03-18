<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Category;
use App\Models\CategoryV2;
use App\Models\ConnectTo;
use App\Models\DocumentNew;
use App\Models\ObjectItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="ConnectTo",
 *     description="ConnectTo APIs",
 * )
 **/
class ConnecToController extends Controller
{
    public function deleteByID($id)
    {
        try {
            $connectToData = ConnectTo::find($id);

            if (empty($connectToData)) {
                return $this->responseException('Not found connectTo', 404);
            }
            ConnectTo::destroy($id);
            return $this->responseSuccess("Delete connectTo success", 200);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getObjects(Request $request)
    {
        try{
            $typeArray = ['goal', 'task', 'routine', 'instruction', 'risk', 'deviation', 'checklist'];
            $categoryArray = [];
            if ($request->typeArray) {
                $typeArray = $request->typeArray;
            }
            if ($request->categoryArray) {
                $categoryArray = $request->categoryArray;
            }

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $result = ObjectItem::leftJoin('categories_new', 'objects.category_id', 'categories_new.id')
                    ->leftJoin('users', 'objects.added_by', 'users.id')
                    ->where('objects.company_id', $user['company_id']);
                if (!empty($categoryArray)) {
                    $result = $result->whereIn('objects.category_id', $categoryArray);
                }
                if (!empty($typeArray)) {
                    $result = $result->whereIn('objects.type', $typeArray);
                }
                $result = $result->select('objects.name as objectName', 'objects.id as objectID', 'objects.type as objectType',
                        'objects.category_id as categoryID', 'categories_new.name as categoryName',
                        'users.first_name as addedByFirstName', 'users.last_name as addedByLastName')
                    ->get()
                    ->toArray();

//                $documents = DocumentNew::where('company_id', $user['company_id'])
//                    ->where('type', 'document')
//                    ->select('documents_new.name as objectName', 'documents_new.id as objectID', 'documents_new.type as objectType', 'documents_new.category_id as objectCategory')
//                    ->get()
//                    ->toArray();

//                $objectsList = array_merge($objects, $documents);

                if (!empty($result)) {
//                    $result = [];
//                    foreach ($objects as $item) {
//                        if ((empty($typeArray) || in_array($item['objectType'], $typeArray))
//                            && (empty($categoryArray) || in_array($item['categoryID'], $categoryArray))) {
//                            $result[] = $item;
//                        }
//                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function getByObject(Request $request)
    {
        try{
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $type = $request->type;
                $id = $request->objectID;

                $result = ConnectTo::where('company_id', $user['company_id']);
                if ($type == 'document') {
                    $result = $result->where('document_id', $id);
                } else {
                    $result = $result->where('object_id', $id);
                }
                $result = $result->get();

                if ($result) {
                    foreach ($result as $item) {
                        $connectedUser = User::find($item['added_by']);
                        $item['connectedByUser'] = $connectedUser['first_name'] . ' ' . $connectedUser['last_name'];
                        if ($item['connect_to_source'] == 'document') {
                            $objectInfo = DocumentNew::find($item['source_id']);
                            $item['objectName'] = $objectInfo['name'];
                            $addedUser = User::find($objectInfo['added_by']);
                            $item['addedByUser'] = $addedUser['first_name'] . ' ' . $addedUser['last_name'];
                            $category = Category::find($objectInfo['category_id']);
                            $item['categoryName'] = $category ? $category['name'] : '';
                            $item['categoryID'] = $objectInfo['category_id'];
                        } else {
                            $objectInfo = ObjectItem::where('id', $item['source_id'])
                                ->where('type', $item['connect_to_source'])
                                ->first();
                            $item['objectName'] = $objectInfo['name'];
                            $addedUser = User::find($objectInfo['added_by']);
                            $item['addedByUser'] = $addedUser['first_name'] . ' ' . $addedUser['last_name'];
                            $category = CategoryV2::find($objectInfo['category_id']);
                            $item['categoryName'] = $category ? $category['name'] : '';
                            $item['categoryID'] = $objectInfo['category_id'];
                        }
                    }
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function saveByObject(Request $request, $type,  $id)
    {
        try {
            $request = $request->all();
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if (empty($request['connectToArray']) || count($request['connectToArray']) > 5) {
                    return $this->responseException(count($request['connectToArray']), 404);
                }

                $result = [];

                $rules = ConnectTo::$rules;
                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];

                if ($type == 'document') {
                    $input['document_id'] = $id;
                } else {
                    $input['object_id'] = $id;
                }

                foreach($request['connectToArray'] as $item) {
                    $input['connect_to_source'] = $item['object_type'];
                    $input['source_id'] = $item['object_id'];

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $errors = ValidateResponse::make($validator);
                        return $this->responseError($errors,400);
                    }

                    $newConnectTo = ConnectTo::create($input);

                    $result[] = $newConnectTo;
                }

                return $this->responseSuccess($result);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function updateByObject(Request $request, $type, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $request = $request->all();

                if (empty($request['connectToArray']) || count($request['connectToArray']) > 5) {
                    return $this->responseException('Invalid data!', 404);
                }

                if ($type == 'document') {
                    $objectIDField = 'document_id';
                } else {
                    $objectIDField = 'object_id';
                }

                $connectToNew = $request['connectToArray'];
                $connectTo = ConnectTo::where('company_id', $user['company_id'])
                    ->where($objectIDField, $id)
                    ->pluck('id')
                    ->toArray();

                $connectToDiff = array_diff($connectTo, array_column($connectToNew, 'id'));

                //delete connectTo
                ConnectTo::whereIn("id", $connectToDiff)->delete();

                $result = [];

                $rules = ConnectTo::$rules;
                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];
                $input[$objectIDField] = $id;

                foreach($request['connectToArray'] as $item) {
                    if (isset($item['id'])) {
                        $result[] = $item;
                    } else {
                        $input['connect_to_source'] = $item['object_type'];
                        $input['source_id'] = $item['object_id'];

                        $validator = Validator::make($input, $rules);
                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($validator);
                            return $this->responseError($errors,400);
                        }

                        $newConnectTo = ConnectTo::create($input);

                        $result[] = $newConnectTo;
                    }
                }

                return $this->responseSuccess($result);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

}
