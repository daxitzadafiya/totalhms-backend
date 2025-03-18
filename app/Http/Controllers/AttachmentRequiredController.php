<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\Attachment;
use App\Models\AttendeeProcessing;
use App\Models\ObjectOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use JWTAuth;

class AttachmentRequiredController extends Controller
{
    public function store(Request $request)
    {
        try {
            $input = $request->all();
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $rules = Attachment::$rules;
                $fileRules = Attachment::$fileRules;

                $input['company_id'] = $user['company_id'];
                $input['added_by'] = $user['id'];

                // url image
                $path = Storage::disk('public')->putFile('/' . $input['company_id'], $request->file('file'));
                $baseUrl = config('app.app_url');
                $input['url'] = $baseUrl. "/api/v1/image/". $path;

                $fileValidator = Validator::make($input, $fileRules);
                if ($fileValidator->fails()) {
                    $errors = ValidateResponse::make($fileValidator);
                    return $this->responseError($errors);
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }

                $newObject = Attachment::create($input);

                if (!empty($newObject['processing_id'])) {
                    // update attachment_id / responsible_attachment_id for table AttendeeProcessing
                    $process = AttendeeProcessing::where('id', $newObject['processing_id'])->first();
                    if (empty($process)) {
                        return $this->responseException('Not found processing', 404);
                    }
                    if ($newObject['added_by_role'] == 'attendee') {
                        $process->update(['attachment_id' => $newObject['id']]);
                    } else {
                        $process->update(['responsible_attachment_id' => $newObject['id']]);
                    }
                } else {
                    // update image_id for table ObjectOption
                    $option = ObjectOption::where('object_id', $newObject['object_id'])->first();
                    if (empty($option)) {
                        return $this->responseException('Not found object', 404);
                    }
                    $option->update(['image_id' => $newObject['id']]);
                }
                return $this->responseSuccess(200);
            }
        } catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
