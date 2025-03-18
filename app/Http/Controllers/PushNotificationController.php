<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RequestPushNotification;
use App\Models\User;
use Carbon\Carbon;
use Validator;
use JWTAuth;

class PushNotificationController extends Controller
{
    public function index($type = false, $limit = false)
    {
        $requests = RequestPushNotification::where('status', 1);
        if ($type) {
            $requests = $requests->where('type', $type);
        }
        if ($limit) {
            $requests = $requests->limit($limit);
        }
        $requests = $requests->get();
        if (!empty($requests)) {
            foreach ($requests as $request) {
                $sendToArray = json_decode($request->send_to);
                $send_to_option = $request->send_to_option;
                foreach ($sendToArray as $item) {
                    if ($request->type == 'notification') {
                        if ($send_to_option) {
                            $this->sendNotificationToOption($send_to_option, $item, $request->id);
                        } else {
                            $this->createNotification($item, $request->id);
                        }
                    } elseif ($request->type == 'warning') {
                        $this->sendNotificationToOption($send_to_option, $item, $request->id);
                    }
                }

                $input['sending_time'] = Carbon::now()->format('Y-m-d H:i:s');
                $input['status'] = 3;

                $request->update($input);
            }
        }
    }

    protected function sendNotificationToOption($send_to_option, $option_id, $requestPushNotification_id)
    {
        if (!$send_to_option || $option_id || $requestPushNotification_id) return false;

        if ($send_to_option == 'company') {
            $adminOfCompany = User::where('company_id', $option_id)->where('added_by', 1)->get();
            foreach ($adminOfCompany as $userAdmin) {
                $this->createNotification($userAdmin->id, $requestPushNotification_id);
            }
        } elseif ($send_to_option == 'industry') {
            $companyIndustry = Company::where('industry_id', $option_id)->get();
            foreach ($companyIndustry as $company) {
                $adminOfCompany = User::where('company_id', $company->id)->where('added_by', 1)->get();
                foreach ($adminOfCompany as $userAdmin) {
                    $this->createNotification($userAdmin->id, $requestPushNotification_id);
                }
            }
        }
    }
}
