<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Notification;
use App\Models\RequestPushNotification;
use App\Models\User;
use Carbon\Carbon;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RequestPushNotification",
 *     description="RequestPushNotification APIs",
 * )
 **/
class RequestPushNotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/requestPushNotification",
     *     tags={"RequestPushNotification"},
     *     summary="Get RequestPushNotification",
     *     description="Get RequestPushNotification list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRequestPushNotification",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $filterBy = $request->filterBy;
                if ($filterBy && $filterBy == 'superadmin') {
                    $result = RequestPushNotification::where('send_from', $user['id'])->with(['message'])->get();
                } else {
                    $result = RequestPushNotification::where('company_id', $user['company_id'])->with(['message'])->get();
                }

                if ($result) {
                    if ($filterBy && $filterBy == 'superadmin') {
                        foreach ($result as $request) {
                            $sendToArray = json_decode($request->send_to);
                            $send_to_option = $request->send_to_option;
                            $send_to_name = [];
                            foreach ($sendToArray as $item) {
                                if ($send_to_option == 'company') {
                                    $company = Company::find($item);
                                    if ($company) {
                                        array_push($send_to_name, $company->name);
                                    }
                                } elseif ($send_to_option == 'industry') {
                                    $industry = Industry::find($item);
                                    if ($industry) {
                                        array_push($send_to_name, $industry->name);
                                    }
                                }
                            }
                            $request->send_to_name = $send_to_name;
                        }
                    }
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
     *     path="/api/v1/requestPushNotification",
     *     tags={"RequestPushNotification"},
     *     summary="Create new RequestPushNotification",
     *     description="Create new RequestPushNotification",
     *     security={{"bearerAuth":{}}},
     *     operationId="createRequestPushNotification",
     *     @OA\RequestBody(
     *         description="RequestPushNotification schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RequestPushNotification")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, RequestPushNotification $requestPushNotification)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            } else {
                $rules = RequestPushNotification::$rules;
                $input = $request->all();
                if ($user['id'] > 1) {
                    $input['company_id'] = $user['company_id'];
                }
                $input['send_from'] = $user['id'];
                $show_action = null;
                if (!empty($input['show_action'])) {
                    $show_action = $input['show_action'];
                }
                $sendToArray = $input['send_to'];
                $countSendTo = count($input['send_to']);
                if ($countSendTo > 0) {
                    $input['send_to'] = json_encode($input['send_to']);
                }

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                $newRequestPushNotification = RequestPushNotification::create($input);

                //test
                if ($countSendTo < 10) {
                    foreach ($sendToArray as $item) {
                        $send_to_option = $input['send_to_option'];
                        if ($send_to_option) {
                            if ($input['type'] == 'notification') {
                                $this->sendNotificationToOption($send_to_option, $item, $newRequestPushNotification->id, $show_action);
                            } elseif ($input['type'] == 'warning') {
                                $this->sendNotificationToOption($send_to_option, $item, $newRequestPushNotification->id, $show_action);
                            }
                        } else {
                            $this->createNotification($item, $newRequestPushNotification->id);
                        }
                    }
                    $input['sending_time'] = Carbon::now()->format('Y-m-d H:i:s');
                    $input['status'] = 3;

                    $newRequestPushNotification->update($input);
                }

                return $this->responseSuccess($newRequestPushNotification, 201);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function sendNotificationToOption($send_to_option, $option_id, $requestPushNotification_id, $show_action = false)
    {
        if (!$send_to_option || !$option_id || !$requestPushNotification_id) return false;

        if ($send_to_option == 'company') {
            $adminOfCompany = User::where('company_id', $option_id)->where('added_by', 1)->get();
            foreach ($adminOfCompany as $userAdmin) {
                $this->createNotification($userAdmin->id, $requestPushNotification_id, $show_action);
            }
        } elseif ($send_to_option == 'industry') {
            $companyIndustry = Company::where('industry_id', $option_id)->get();
            foreach ($companyIndustry as $company) {
                $adminOfCompany = User::where('company_id', $company->id)->where('added_by', 1)->get();
                foreach ($adminOfCompany as $userAdmin) {
                    $this->createNotification($userAdmin->id, $requestPushNotification_id, $show_action);
                }
            }
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/requestPushNotification/{id}",
     *     tags={"RequestPushNotification"},
     *     summary="Get RequestPushNotification by id",
     *     description="Get RequestPushNotification by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getRequestPushNotificationByIdAPI",
     *     @OA\Parameter(
     *         description="RequestPushNotification id",
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
            $requestPushNotificationData = RequestPushNotification::find($id)->with(['message']);
            if (empty($requestPushNotificationData)) {
                return $this->responseException('Not found RequestPushNotification', 404);
            }

            return $this->responseSuccess($requestPushNotificationData, 201);

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/requestPushNotification/{id}",
     *     tags={"RequestPushNotification"},
     *     summary="Update RequestPushNotification API",
     *     description="Update RequestPushNotification API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateRequestPushNotificationAPI",
     *     @OA\Parameter(
     *         description="RequestPushNotification id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="RequestPushNotification schemas",
     *         @OA\JsonContent(ref="#/components/schemas/RequestPushNotification")
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
            $rules = RequestPushNotification::$updateRules;
            $input = $request->all();

            $requestPushNotificationData = RequestPushNotification::find($id);
            if (empty($requestPushNotificationData)) {
                return $this->responseException('Not found RequestPushNotification', 404);
            }

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }

            $requestPushNotificationData->update($input);

            return $this->responseSuccess($requestPushNotificationData, 201);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function declineNewUpdate($request_push_notification_id)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $request = RequestPushNotification::find($request_push_notification_id);

                if (empty($request)) {
                    return $this->responseException('Not found request', 404);
                }

                $input['processed_by'] = $user['id'];
                $input['process_status'] = 'declined';

                $request->update($input);

                $notificationsOfRequest = Notification::where('request_push_notification_id', $request->id)->get();
                if (!empty($notificationsOfRequest)) {
                    foreach ($notificationsOfRequest as $notification) {
                        $inputNotification['show_action'] = 0;
                        if ($notification->user_id == $user['id']) {
                            $inputNotification['read_status'] = 1;
                        }
                        $notification->update($inputNotification);
                    }
                }

                return $this->responseSuccess($request, 201);


                return $this->responseSuccess($request, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/requestPushNotification/{id}",
     *     tags={"RequestPushNotification"},
     *     summary="Delete RequestPushNotification API",
     *     description="Delete RequestPushNotification API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteRequestPushNotificationAPI",
     *     @OA\Parameter(
     *         description="RequestPushNotification id",
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
            $requestPushNotificationData = RequestPushNotification::find($id);
            if (empty($requestPushNotificationData)) {
                return $this->responseException('Not found RequestPushNotification', 404);
            }

            RequestPushNotification::destroy($id);

            return $this->responseSuccess("Delete RequestPushNotification success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
