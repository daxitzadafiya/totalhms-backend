<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\UnwantedEvent;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="UnwantedEvents",
 *     description="UnwantedEvent APIs",
 * )
 **/
class UnwantedEventController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/unwantedEvents",
     *     tags={"UnwantedEvents"},
     *     summary="Get unwantedEvents",
     *     description="Get unwantedEvents list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUnwantedEvents",
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
                $result = UnwantedEvent::leftJoin('projects', 'unwanted_events.project_id', '=', 'projects.id')
                    ->join('users','unwanted_events.added_by','=','users.id')
                    ->select('unwanted_events.*', 'projects.name as project_name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'),)
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
     *     path="/api/v1/unwantedEvents",
     *     tags={"UnwantedEvents"},
     *     summary="Create new unwantedEvent",
     *     description="Create new unwantedEvent",
     *     security={{"bearerAuth":{}}},
     *     operationId="createUnwantedEvent",
     *     @OA\RequestBody(
     *         description="UnwantedEvent schemas",
     *         @OA\JsonContent(ref="#/components/schemas/UnwantedEvent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, UnwantedEvent $unwantedEvent)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = UnwantedEvent::$rules;
                $input = $request -> all();
                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newUnwantedEvent = UnwantedEvent::create($input);
                return $this->responseSuccess($newUnwantedEvent,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/unwantedEvents/{id}",
     *     tags={"UnwantedEvents"},
     *     summary="Get unwantedEvent by id",
     *     description="Get unwantedEvent by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getUnwantedEventByIdAPI",
     *     @OA\Parameter(
     *         description="unwantedEvent id",
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
            $unwantedEventData = UnwantedEvent::where("id",$id)->first();
            if (empty($unwantedEventData)) {
                return $this->responseException('Not found unwanted event', 404);
            }
            return $this->responseSuccess($unwantedEventData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/unwantedEvents/{id}",
     *     tags={"UnwantedEvents"},
     *     summary="Update unwantedEvent API",
     *     description="Update unwantedEvent API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateUnwantedEventAPI",
     *     @OA\Parameter(
     *         description="unwantedEvent id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="UnwantedEvent schemas",
     *         @OA\JsonContent(ref="#/components/schemas/UnwantedEvent")
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
            $rules = UnwantedEvent::$updateRules;
            $input = $request -> all();

            $unwantedEventData = UnwantedEvent::where("id",$id)->first();
            if (empty($unwantedEventData)) {
                return $this->responseException('Not found unwanted event', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $unwantedEventData->update($input);

            return $this->responseSuccess($unwantedEventData,201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/unwantedEvents/{id}",
     *     tags={"UnwantedEvents"},
     *     summary="Delete unwantedEvent API",
     *     description="Delete unwantedEvent API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteUnwantedEventAPI",
     *     @OA\Parameter(
     *         description="unwantedEvent id",
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
            $unwantedEventData = UnwantedEvent::where("id",$id)->first();
            if (empty($unwantedEventData)) {
                return $this->responseException('Not found unwanted event', 404);
            }
            UnwantedEvent::destroy($id);
            return $this->responseSuccess("Delete unwanted event success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
