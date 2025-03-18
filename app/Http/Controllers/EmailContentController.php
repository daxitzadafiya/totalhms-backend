<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\EmailContent;
use Exception;
use Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailContentController extends Controller
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

            $emailContent = EmailContent::all();

            return $this->responseSuccess($emailContent);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $input = $request->all();
            $emailContent = EmailContent::where('key', $input['key'])->find($id);
            if (empty($emailContent)) {
                return $this->responseException('Not found email content', 404);
            }

            $rules = ['description'   => 'required'];
            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);

                return $this->responseError($errors, 400);
            }

            if ($input['is_sms']) {
                $rules = ['sms_description'   => 'required'];
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);

                    return $this->responseError($errors, 400);
                }
            }

            $updateData = [
                'description' => $input['description'],
                'is_sms' => $input['is_sms'],
                'sms_description' => $input['sms_description']
            ];

            $emailContent->update($updateData);

            return $this->responseSuccess('Email content updated successfully.');
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}