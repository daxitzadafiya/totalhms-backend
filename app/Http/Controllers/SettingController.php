<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Setting;
use Exception;
use Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $settings = Setting::all();

            return $this->responseSuccess($settings);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function update(Request $request)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $settings = $request->all();

            foreach ($settings as $setting) {
                
                if($setting['key'] == 'freezing_system'){
                   $setting['value'] =  $setting['value'] ? 'automatic' : 'manually';
                }
                
                Setting::where('key', $setting['key'])->update([
                    'value' => $setting['value'],
                    'value_details' => json_encode($setting['value_details']),
                    'is_disabled' => $setting['is_disabled'],
                ]);
            }

                return $this->responseSuccess('setting updated successfully.');

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function checkDisabled()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            $setting['stripe_system'] = Setting::where('key', 'stripe_system')->value('is_disabled');
            $setting['fiken_system'] = Setting::where('key', 'fiken_system')->value('is_disabled');

            return $this->responseSuccess($setting);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}