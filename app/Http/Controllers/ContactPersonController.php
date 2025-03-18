<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Validator;
use JWTAuth;
use App\Models\ContactPerson;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="ContactPersons",
 *     description="ContactPersons APIs",
 * )
 **/
class ContactPersonController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/contactPersons",
     *     tags={"ContactPersons"},
     *     summary="Get contactPersons",
     *     description="Get contactPersons list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getContactPersons",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $contact_id = $request -> contact_id;
                if($contact_id){
                    $result = ContactPerson::where ('contact_id', $contact_id)
                        ->get();
                }else{
                    $result = ContactPerson::all();
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
     *     path="/api/v1/contactPersons",
     *     tags={"ContactPersons"},
     *     summary="Create new contactPerson",
     *     description="Create new contactPerson",
     *     security={{"bearerAuth":{}}},
     *     operationId="createContactPerson",
     *     @OA\RequestBody(
     *         description="ContactPerson schemas",
     *         @OA\JsonContent(ref="#/components/schemas/ContactPerson")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, ContactPerson $contactPerson)
    {
        try {
            $rules = ContactPerson::$rules;
            $input = $request -> all();
            if ($input['is_primary']){
                ContactPerson::where('is_primary', '=', 1)->where('contact_id', '=', $input['contact_id'])->update(array('is_primary' => 0));
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            $newContactPerson = ContactPerson::create($input);
            return $this->responseSuccess($newContactPerson,201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/contactPersons/{id}",
     *     tags={"ContactPersons"},
     *     summary="Get contactPerson by id",
     *     description="Get contactPerson by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getContactPersonByIdAPI",
     *     @OA\Parameter(
     *         description="contactPerson id",
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
            $contactPersonData = ContactPerson::where("id",$id)->first();
            if (empty($contactPersonData)) {
                return $this->responseException('Not found contactPerson', 404);
            }

            return $this->responseSuccess($contactPersonData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/contactPersons/{id}",
     *     tags={"ContactPersons"},
     *     summary="Update contactPerson API",
     *     description="Update contactPerson API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateContactPersonAPI",
     *     @OA\Parameter(
     *         description="contactPerson id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="ContactPerson schemas",
     *         @OA\JsonContent(ref="#/components/schemas/ContactPerson")
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
            $rules = ContactPerson::$updateRules;
            $input = $request -> all();

            $contactPersonData = ContactPerson::where("id",$id)->first();
            if (empty($contactPersonData)) {
                return $this->responseException('Not found contactPerson', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }
            if ($input['is_primary']){
                ContactPerson::where('is_primary', '=', 1)->where('contact_id', '=', $contactPersonData->contact_id)->update(array('is_primary' => 0));
            }

            $contactPersonData->update($input);

            return $this->responseSuccess($contactPersonData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/contactPersons/{id}",
     *     tags={"ContactPersons"},
     *     summary="Delete contactPerson API",
     *     description="Delete contactPerson API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteContactPersonAPI",
     *     @OA\Parameter(
     *         description="contactPerson id",
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
            $contactPersonData = ContactPerson::where("id",$id)->first();
            if (empty($contactPersonData)) {
                return $this->responseException('Not found contactPerson', 404);
            }
            ContactPerson::destroy($id);
            return $this->responseSuccess("Delete contactPerson success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
