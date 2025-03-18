<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Notification;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notification",
 *     description="Notification APIs",
 * )
 **/
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/notification",
     *     tags={"Notification"},
     *     summary="Get Notification",
     *     description="Get Notification list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getNotification",
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
                $getBy = $request->getBy;
                $getLastest = $request->getLastest;
                $getShowAction = $request->getShowAction;
                if (!empty($getBy) && $getBy == 'user') {
                    $result = Notification::where('user_id', $user['id']);
                    if ($getShowAction) {
                        $result = $result->where('show_action', 1)
                            ->with(['notification_content' => function($query) use($user){
                                $query->with(['message']);
                            }]);
                    } else {
                        $result = $result->with(['notification_content' => function($query) use($user){
                            $query->with(['message']);
                        }]);
                    }
                    $result = $result->orderBy('created_at', 'desc');
                    if (!empty($getLastest)) {
                        $result = $result->limit($getLastest);
                    }
                    $result = $result->get();

                } else {
                    $result = Notification::with(['notification_content' => function($query) use($user){
                        $query->where('company_id', $user['company_id'])->with(['message']);
                    }])->orderBy('created_at', 'desc')->get();
                }

                $result->map(function ($notification) {
                    $notification->created_at = optional($notification->created_at)->setTimezone($this->timezone)->toDateTimeString();
                    $notification->updated_at = optional($notification->updated_at)->setTimezone($this->timezone)->toDateTimeString();
                    return $notification;
                });

                if($result) {
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function countUnRead(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $getBy = $request->getBy;
                if (!empty($getBy) && $getBy == 'user') {
                    $result = Notification::where('user_id', $user['id'])->where('read_status', 0)->count();
                } else {
                    $result = Notification::where('read_status', 0)
                        ->with(['notification_content' => function($query) use($user){
                            $query->where('company_id', $user['company_id']);
                        }])
                        ->count();
                }

                if($result) {
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
     *     path="/api/v1/notification",
     *     tags={"Notification"},
     *     summary="Create new Notification",
     *     description="Create new Notification",
     *     security={{"bearerAuth":{}}},
     *     operationId="createNotification",
     *     @OA\RequestBody(
     *         description="Notification schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, Notification $notification)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = Notification::$rules;
                $input = $request->all();

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newNotification = Notification::create($input);

                return $this->responseSuccess($newNotification, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notification/{id}",
     *     tags={"Notification"},
     *     summary="Get Notification by id",
     *     description="Get Notification by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getNotificationByIdAPI",
     *     @OA\Parameter(
     *         description="Notification id",
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
            $notificationData = Notification::find($id)->with(['notification_content']);
            if (empty($notificationData)) {
                return $this->responseException('Not found Notification', 404);
            }

            return $this->responseSuccess($notificationData, 201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notification/{id}",
     *     tags={"Notification"},
     *     summary="Update Notification API",
     *     description="Update Notification API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateNotificationAPI",
     *     @OA\Parameter(
     *         description="Notification id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Notification schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
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
            $rules = Notification::$updateRules;
            $input = $request->all();

            $notificationData = Notification::find($id);
            if (empty($notificationData)) {
                return $this->responseException('Not found Notification', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $notificationData->update($input);

            return $this->responseSuccess($notificationData, 201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notification/{id}",
     *     tags={"Notification"},
     *     summary="Delete Notification API",
     *     description="Delete Notification API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteNotificationAPI",
     *     @OA\Parameter(
     *         description="Notification id",
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
            $notificationData = Notification::find($id);
            if (empty($notificationData)) {
                return $this->responseException('Not found Notification', 404);
            }

            Notification::destroy($id);

            return $this->responseSuccess("Delete Notification success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
