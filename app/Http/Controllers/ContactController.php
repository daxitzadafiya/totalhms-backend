<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\Contact;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Contacts",
 *     description="Contact APIs",
 * )
 **/
class ContactController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/contacts",
     *     tags={"Contacts"},
     *     summary="Get contacts",
     *     description="Get contacts list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getContacts",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (!$user = $this->getAuthorizedUser('contact', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $limit = $request->getLimit;
                $orderBy = $request->getOrderBy;
                $result = Contact::join('users', 'contacts.added_by','=', 'users.id')
                    ->join('categories', 'contacts.category_id', '=', 'categories.id')
                    ->leftJoin(DB::raw('(SELECT contact_id, name FROM contact_people WHERE is_primary = 1) AS CP'), 'CP.contact_id','=', 'contacts.id')
                    ->leftJoin(DB::raw('(SELECT count(id) as count_attachment, contact_id FROM documents WHERE contact_id IS NOT NULL GROUP BY contact_id) AS CD'), 'contacts.id', '=', 'CD.contact_id');

                $result = $result->where (function ($q) use ($user) {
                    if ($user->role_id > 1) {
                        $q->where('contacts.company_id', $user['company_id'])
                            ->orWhere('contacts.added_by', 1);
                    } else if ($user->role_id == 1) {
                        $q->where('contacts.added_by', 1);
                    }
                })
                    ->select('contacts.*', 'categories.name as category_name', 'users.email as added_by_email',
                    'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),
                    'CP.name as primary_contact_name', 'CD.count_attachment')
                    ->get();

                if($result){
                    $result = $this->filterViewList('contact', $user, $user->filterBy, $result, $orderBy, $limit);
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
     *     path="/api/v1/contacts",
     *     tags={"Contacts"},
     *     summary="Create new contact",
     *     description="Create new contact",
     *     security={{"bearerAuth":{}}},
     *     operationId="createContact",
     *     @OA\RequestBody(
     *         description="Contact schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Contact")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Contact $contact)
    {
        try {
            if (!$user = $this->getAuthorizedUser('contact', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = Contact::$rules;
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                if ($user['role_id'] > 1) {
                    $input['company_id'] = $user['company_id'];
                }
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newContact = Contact::create($input);
                return $this->responseSuccess($newContact,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Get contact by id",
     *     description="Get contact by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getContactByIdAPI",
     *     @OA\Parameter(
     *         description="contact id",
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
            $contactData = Contact::where("id",$id)->first();
            if (empty($contactData)) {
                return $this->responseException('Not found contact', 404);
            }
            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'contact',
                'objectItem' => $contactData,
            ];
            if (!$user = $this->getAuthorizedUser('contact', 'detail', 'show', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $category = Category::find($contactData->category_id);
                $contactData->category_name = $category->name; // show category name
                $contactData->editPermission = $user->editPermission;
                return $this->responseSuccess($contactData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Update contact API",
     *     description="Update contact API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateContactAPI",
     *     @OA\Parameter(
     *         description="contact id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Contact schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Contact")
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
            $rules = Contact::$updateRules;
            $input = $request -> all();

            $contactData = Contact::where("id",$id)->first();
            if (empty($contactData)) {
                return $this->responseException('Not found contact', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'contact',
                'objectItem' => $contactData,
            ];
            if (!$user = $this->getAuthorizedUser('contact', 'basic', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $contactData->update($input);
                return $this->responseSuccess($contactData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Delete contact API",
     *     description="Delete contact API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteContactAPI",
     *     @OA\Parameter(
     *         description="contact id",
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
            $contactData = Contact::where("id",$id)->first();
            if (empty($contactData)) {
                return $this->responseException('Not found contact', 404);
            }
            Contact::destroy($id);
            return $this->responseSuccess("Delete contact success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
