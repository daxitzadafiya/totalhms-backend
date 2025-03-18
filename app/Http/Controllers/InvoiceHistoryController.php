<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\InvoiceHistory;
use App\Models\Plan;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

class InvoiceHistoryController extends Controller
{
    public function index(){
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
                $invoiceHistories = InvoiceHistory::with('user')->where('user_id',$user->id)->orWhere('company_id',$user->company->id)->get();

                return $this->responseSuccess($invoiceHistories);

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }



    public function show($id)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
                $invoiceHistories = InvoiceHistory::with(['subscription','billing.billingDetail'])->find($id);

                return $this->responseSuccess($invoiceHistories);

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}