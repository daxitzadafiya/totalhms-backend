<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Exception;
use Validator;
use JWTAuth;

use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            if ($user->role->level > 1 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if($user->role->level === 0 || $user->role->level === 4 ){
                $emailLogs = EmailLog::with('company')->where('for_admin',1)->get();
            }else{
                $company = $user->company;
                $emailLogs = EmailLog::with('company')->where('for_company',1)->where('company_id',$company->id)->get();
            }
            
            return $this->responseSuccess($emailLogs);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 1 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $emailLog = EmailLog::with('company')->findOrFail($id);
            if (empty($emailLog)) {
                return $this->responseException('Not found emailLog', 404);
            }
            return $this->responseSuccess($emailLog);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}