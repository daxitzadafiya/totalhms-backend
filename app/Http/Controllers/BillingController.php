<?php


namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\Company;
use App\Models\DocumentAttachment;
use App\Models\Employee;
use App\Models\Repository;
use Validator;
use JWTAuth;
use app\helpers\ValidateResponse;
use App\Mail\InvoiceMail;
use App\Models\EmailContent;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Fiken;

/**
 * @OA\Tag(
 *     name="Billing",
 *     description="Billing APIs",
 * )
 **/
class BillingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/billings",
     *     tags={"Billing"},
     *     summary="Get billing",
     *     description="Get billing list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getBilling",
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
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 1 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                if($user->role->level === 0){
                    $billings = Billing::with('billingDetail.billing.subscription')->get();
                }else{
                    $billings = Billing::with(['billingDetail.billing.subscription'])->where('company_id',$user->company_id)->get();
                }
                if ($billings) {
                    return $this->responseSuccess($billings);
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
     *     path="/api/v1/billings",
     *     tags={"Billing"},
     *     summary="Create new billing",
     *     description="Create new billing",
     *     security={{"bearerAuth":{}}},
     *     operationId="createBilling",
     *     @OA\RequestBody(
     *         description="Billing schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Billing")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $input = $request->all();

                $companyArray = $input['companyArray'];
                if (!empty($companyArray)) {
                    foreach ($companyArray as $id) {
                        $storageUpload = DocumentAttachment::leftJoin('documents_new', 'documents_attachments.document_id', 'documents_new.id')
                            ->where('documents_new.company_id', $id)
                            ->where('documents_new.delete_status', 0)
                            ->sum('documents_attachments.file_size');

                        $storageRepo = Repository::where('company_id', $id)
                            ->whereNotNull('attachment_uri')
                            ->whereNull('restore_date')
                            ->sum('attachment_size');

                        $numberOfEmployee = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                            ->where('users.company_id', $id)
                            ->where('employees.disable_status', 0)
                            ->get()
                            ->count();

                        $input['name'] = Helper::getInvoiceID();
                        $input['company_id'] = $id;
                        $input['company_name'] = Company::find($id)->name;
                        $input['added_by'] = $user['id'];
                        $input['storage_upload'] = $storageUpload;
                        $input['storage_repo'] = $storageRepo;
                        $input['employee'] = $numberOfEmployee;

                        $rules = Billing::$rules;
                        $validator = Validator::make($input, $rules);

                        if ($validator->fails()) {
                            $errors = ValidateResponse::make($validator);
                            return $this->responseError($errors, 400);
                        }
                        Billing::create($input);
                    }
                    return $this->responseSuccess('Save success!');
                }
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/billings/{id}",
     *     tags={"Billing"},
     *     summary="Get billing by id",
     *     description="Get billing by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getBillingByIdAPI",
     *     @OA\Parameter(
     *         description="Billing id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $billingData = Billing::where('id', $id)->first();
                if (empty($billingData)) {
                    return $this->responseException('Not found help', 404);
                }
                return $this->responseSuccess($billingData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/billings/{id}",
     *     tags={"Billing"},
     *     summary="Update billing API",
     *     description="Update billing API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateBillingAPI",
     *     @OA\Parameter(
     *         description="billing id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Billing schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Billing")
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $billingData = Billing::find($id);
                if (empty($billingData)) {
                    return $this->responseException('Not found help', 404);
                }

                $rules = Billing::$updateRules;
                $input = $request->all();
                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }

                $billingData->update($input);

                return $this->responseSuccess($billingData);
            }
        } catch(Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/billings/{id}",
     *     tags={"Billing"},
     *     summary="Delete billing API",
     *     description="Delete billing API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteBillingAPI",
     *     @OA\Parameter(
     *         description="billing id",
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
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                $billingData = Billing::find($id);
                if (empty($billingData)) {
                    return $this->responseException('Not found billing', 404);
                }
                return $this->responseException('Delete failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function status(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->role->level > 0 && $user->role->level < 4) {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $billingDetails = BillingDetail::findOrFail($id);
                $billingDetails->update(['status' => !$billingDetails->status]);

                return $this->responseSuccess('Status change success!');
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function sendEmail(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $billing = Billing::findOrFail($id);
            $company = Company::where('id', $billing->company_id)->first();
            $emailContent = EmailContent::where('key', 'reminder_invoice')->first();
            $emailDescription = str_replace('{company_name}', $company['name'], $emailContent['description']);
            $subscription = $billing->subscription;

            $startDate = Carbon::parse($subscription->billed_at);
            $diff_in_days = Carbon::now()->diffInDays($startDate);
            $remainingDays = 15 - $diff_in_days;
            $subscriptionType = $subscription->plan_id ? 'Plan' : 'Addon';
            try {
                Mail::to($company->email)->send(new InvoiceMail($emailContent->subject,$emailDescription, $remainingDays, $subscriptionType));
            } catch (\Exception $e) {
                Log::debug('Invoice mail issue : ', ['error' => $e]);
            }

            return $this->responseSuccess('Sending email success!');
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function pdf(Request $request)
    {

        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $invoice = Fiken::getinvoice($request->fiken_invoice_id);
            $invoiceUrl = config('app.app_url').'/invoice/pdf/?url='.$invoice['invoicePdf']['downloadUrl'];
            
            return $this->responseSuccess($invoiceUrl);

        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function pdfDownload(Request $request)
    {
        try {
            $invoiceUrl = $invoiceUrl = Fiken::getInvoicePdf($request->url);

            return response()->streamDownload(function () use ($invoiceUrl) {
                echo $invoiceUrl;
            }, 'invoice.pdf');
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}