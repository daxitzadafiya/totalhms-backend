<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifyPurchaseOrder;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceHistory;
use App\Models\BillingDetail;
use App\Models\EmailContent;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\CardDetails;
use App\Models\Company;
use App\Helpers\Helper;
use App\Models\Addon;
use App\Models\Billing;
use App\Models\EmailLog;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\Plan;
use Carbon\Carbon;
use Exception;
use JWTAuth;
use Validator;
use Stripe;
use Credit;
use Fiken;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function stripeCard(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $setting = Setting::where('key', 'stripe_system')->where('is_disabled',1)->first();
            if(!$setting){
                throw new Exception('please stripe setting update', 400);
            }

            $stripeSecretKey = @$setting->value_details['is_stripeMode'] ? @$setting->value_details['stripeLiveSecretKey'] : @$setting->value_details['stripeTestSecretKey'];
            $stripePrivateKey = @$setting->value_details['is_stripeMode'] ? @$setting->value_details['stripeLivePrivateKey'] : @$setting->value_details['stripeTestPrivateKey'];

            $stripe = new \Stripe\StripeClient($stripeSecretKey);

            $stripe = $stripe->setupIntents->create([
                'payment_method_types' => ['card'],
            ]);

            $resource = [
                'key'    => $stripePrivateKey,
                'intent' => $stripe->client_secret,
            ];

            return $this->responseSuccess($resource);
        } catch (Exception $e) {
            Log::debug('Failed to stripe: ', ['error' => $e->getMessage()]);
            Helper::SendEmailIssue($e->getMessage());
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function creditCheck(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $creditLimit = Setting::where('key', 'credit_limit')->where('is_disabled',1)->first();
            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();
            $vat = 12;
            if ($vatSetting) {
                $vat = $vatSetting->value;
            }
            $company = $user->company;
            $lowCredit = false;

            if ($company->vat_number) {
                $creditToken = Credit::authenticate();

                if($creditToken){
                    $companyDetail = [
                        'countries' => 'NO',
                        'regNo'     => str_replace(' ', '', $company->vat_number), //'920312845'
                    ];
                    $credit = Credit::getCompanyCredit($creditToken, $companyDetail);

                    $lowCredit = true;

                    if (($creditLimit && $credit && $credit > @$creditLimit->value) ) {
                        $lowCredit = false;
                    }
                }
            }
            $companyDetail = [
                'lowCredit' => $lowCredit,
                'customerDefaultCard' => !!$user->payment_method,
                'cardDetail' => $user->cardActive,
                'vat' => $vat
            ];

            return $this->responseSuccess($companyDetail);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function planPurchase(Request $request)
    {
        try {
            DB::beginTransaction();
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $plan = Plan::find($request->plan_id);

            if (empty($plan)) {
                return $this->responseException('Not found plan', 404);
            }

            //get contact information with create
            $fikenConfiguration = $this->fikenCheckConfiguration($user);
            if(@$fikenConfiguration['error']){
                Helper::SendEmailIssue($fikenConfiguration['error']);
                throw new Exception($fikenConfiguration['error'], 400);
            }

            $lastSubscription = Subscription::Active()->where('addon_id')->where('user_id', $user->id)->first();
            if ($lastSubscription) {
                $checkstatus = $lastSubscription->billing->billingDetail->status;
                if ($checkstatus == BillingDetail::PENDING) {
                    return $this->responseException(`The current plan is not paid, so the plan can't be purchased`, 422);
                }
            }

            // additional user price
            $additionalUsers = $request->additional_users;
            $additionalPrice = $additionalUsers * $plan->additional_price;

            // plan total price with discount and additional price
            $totalPrice = $plan->price + $additionalPrice - $request->discount;

            //vat calculation
            $vat = 0;
            if ($request->vat) {
                $vat = ($totalPrice * $request->vat) / 100;
            }

            //total plan amount
            $totalAmount = $totalPrice + $vat;

            // payByMethod = 1  card , payByMethod = 2 invoice

            if ($request->payByMethod == 1) {

                $this->stripeManage($user, $totalAmount, $request->payment_method, $plan, $addon = '');

                $status = BillingDetail::PAID;
                $pay_by = Subscription::CARD;
            } elseif ($request->payByMethod == 2) {
                $status = BillingDetail::PENDING;
                $pay_by = Subscription::INVOICE;
            }

            $freeable = $user->company->is_freeable;
            $trial_end = null;
            $billedDate = Carbon::now();
            $nextbillingDate = Carbon::now()->addMonths($plan->plan_type);

            if ($plan->free_trial_months && !$user->company->is_freeable) {
                $trial_end = Carbon::now()->addMonths($plan->free_trial_months);
                $totalAmount = 0;
                $status = BillingDetail::PAID;
                $billedDate = null;
                $nextbillingDate = null;
                $vat = 0;
                $freeable = true;
            }

            $coupon = Coupon::where('code', $request->couponCode)->where('company_id',$user->company_id)->first();
            if($coupon){
                $coupon->update(['used_at' => Carbon::now()]);
            }

            $subscription = Subscription::create([
                'plan_id'    => $plan->id,
                'user_id'    => $user->id,
                'company_id' => $user->company_id,
                'trial_end_at'  => $trial_end,
                'start_date' => Carbon::now(),
                'billed_at'  => $billedDate,
                'next_billing_at'  => $nextbillingDate,
                'pay_by'     => $pay_by,
                'plan_detail' => $plan,
                'quantity' => 1,
            ]);

            if ($lastSubscription) {
                $lastSubscription->deactivated_at = Carbon::now();
                $lastSubscription->save();

                $emailContent = EmailContent::where('key', 'plan_changed')->first();
                $emailDescription = str_replace('{company_name}', $user->company->name, $emailContent['description']);
                $emailDescription = str_replace('{new_plan}', $plan->title, $emailDescription);
                $emailDescription = str_replace('{old_plan}', $lastSubscription->plan_detail['title'], $emailDescription);
                $invoiceDescription = "The company has changed a new plan " . $lastSubscription->plan_detail['title'] . " from " . $plan->title;
            } else {
                $emailContent = EmailContent::where('key', 'plan_purchased')->first();
                $emailDescription = str_replace('{company_name}', $user->company->name, $emailContent['description']);
                $emailDescription = str_replace('{plan_name}', $plan->title, $emailDescription);
                $invoiceDescription = "The company has purchased a new plan" . $plan->title;
            }

            $billing = Helper::billing($subscription);
            $newBilling = Billing::create($billing);

            $invoiceHistory = InvoiceHistory::create([
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'billing_id' => $newBilling->id,
                'company_id' => $user->company->id,
            ]);

            //fiken invoice 
            try {
                if ($user->company->is_freeable) {

                    //get product information
                    $FikenPlan = Fiken::getProduct($plan->fiken_plan_id);
                    if(@$FikenPlan['error']){
                        Helper::SendEmailIssue($FikenPlan['error']);
                        throw new Exception($FikenPlan['error'], 400);
                    }

                    //get product additional information
                    $FikenPlanAdditional = Fiken::getProduct($plan->fiken_additional_id);
                    if(@$FikenPlanAdditional['error']){
                        Helper::SendEmailIssue($FikenPlanAdditional['error']);
                        throw new Exception($FikenPlanAdditional['error']);
                    }

                    // get contact information
                    $customerFiken = Fiken::getContact($user->fiken_customer_id);
                    if(@$customerFiken['error']){
                        Helper::SendEmailIssue($customerFiken['error']);
                        throw new Exception($customerFiken['error'], 400);
                    }

                    // get account information
                    $accountFiken = Fiken::getAccounts();
                    if(@$accountFiken['error']){
                        Helper::SendEmailIssue($accountFiken['error']);
                        throw new Exception($accountFiken['error'], 400);
                    }

                    $subscriptionData = [
                        'discount' => $request->discount_percentage,
                        'quantity' => 1,
                        'additional_users' => $additionalUsers
                    ];

                    if (!$additionalUsers) {
                        $FikenPlanAdditional = '';
                    }

                    //create invoice
                    $createInvoice = Fiken::createInvoice($customerFiken, $FikenPlan, $FikenPlanAdditional, $accountFiken[0], $subscriptionData);
                    if(@$createInvoice['error']){
                        Helper::SendEmailIssue($createInvoice['error']);
                        throw new Exception($createInvoice['error'], 400);
                    }
                }
            } catch (\Exception $e) {
                info('fiken issue, Erro:' . $e->getMessage());
                return $this->responseException($e->getMessage(), 400);
            }

            $billingDetail['additional_user'] = $additionalUsers;
            $billingDetail['additional_user_amount'] = $additionalPrice;
            $billingDetail['billing_id'] = $newBilling->id;
            $billingDetail['plan_id'] = $plan->id;
            $billingDetail['discount'] = $request->discount;
            $billingDetail['vat'] = $vat;
            $billingDetail['amount'] = $totalAmount;
            $billingDetail['status'] = $status;
            $billingDetail['fiken_invoice_id'] = @$createInvoice['invoiceId'];

            BillingDetail::create($billingDetail);

            if ($lastSubscription) {
                $this->pushNotification($user->id, $user->company_id, 2, [$subscription->user_id], 'plan_subscription', 'plan_subscription', $invoiceHistory->id, $plan->title, 'update');
            }else{
                $this->pushNotification($user->id, $user->company_id, 2, [$subscription->user_id], 'plan_subscription', 'plan_subscription', $invoiceHistory->id, $plan->title, 'create');
            }

            $company = $user->company;
            $company->subscription_deactivated_at = null;
            $company->is_freeable = $freeable;
            $company->save();

            if($user->company->email){
                $this->sendEmail($user->company->email,$user->company->id,$emailContent, $emailDescription);
            }

            DB::commit();
            return $this->responseSuccess('Plan purchase successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function addonPurchase(Request $request)
    {
        try {
            DB::beginTransaction();
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $addon = Addon::find($request->addon_id);
            if (empty($addon)) {
                return $this->responseException('Not found addon', 404);
            }

            //get contact information with create
            $fikenConfiguration = $this->fikenCheckConfiguration($user);
            if(@$fikenConfiguration['error']){
                Helper::SendEmailIssue($fikenConfiguration['error']);
                throw new Exception($fikenConfiguration['error'], 400);
            }

            // addon total price with quantity
            $totalPrice = $addon->price * $request->quantity;
            //vat calculation
            $vat = 0;
            if ($request->vat) {
                $vat = ($totalPrice * $request->vat) / 100;
            }
            //total addon amount
            $totalAmount = $totalPrice + $vat;

            // payByMethod = 1  card , payByMethod = 2 invoice
            if ($request->payByMethod == 1) {

                $this->stripeManage($user, $totalAmount, $request->payment_method, $plan = '', $addon);

                $status = BillingDetail::PAID;
                $pay_by = Subscription::CARD;
            } elseif ($request->payByMethod == 2) {
                $status = BillingDetail::PENDING;
                $pay_by = Subscription::INVOICE;
            }

            $subscription = Subscription::create([
                'addon_id' => $addon->id,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'start_date' => Carbon::now(),
                'billed_at' => Carbon::now(),
                'next_billing_at'  => Carbon::now()->addMonths($addon->frequency),
                'pay_by'     => $pay_by,
                'addon_detail'     => $addon,
                'quantity' => $request->quantity
            ]);

            if ($subscription) {

                $billing = Helper::billing($subscription);
                $newBilling = Billing::create($billing);

                $emailContent = EmailContent::where('key', 'addon_purchased')->first();
                $emailDescription = str_replace('{company_name}', $user->company->name, $emailContent['description']);
                $emailDescription = str_replace('{addon_name}', $addon->title, $emailDescription);
                $invoiceDescription = "The company has purchased a new addon" . $addon->title;

                $invoiceHistory = InvoiceHistory::create([
                    'title' =>  $emailContent->title,
                    'description' => $invoiceDescription,
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'billing_id' => $newBilling->id,
                    'company_id' => $user->company->id,
                ]);

                //fiken invoice 
                try {
                    //get product information
                    $FikenAddon = Fiken::getProduct($addon->fiken_addon_id);
                    if(@$FikenAddon['error']){
                        Helper::SendEmailIssue($FikenAddon['error']);
                        throw new Exception($FikenAddon['error'], 400);
                    }

                    // get contact information
                    $customerFiken = Fiken::getContact($user->fiken_customer_id);
                    if(@$customerFiken['error']){
                        Helper::SendEmailIssue($customerFiken['error']);
                        throw new Exception($customerFiken['error'], 400);
                    }

                    // get account information
                    $accountFiken = Fiken::getAccounts();
                    if(@$accountFiken['error']){
                        Helper::SendEmailIssue($accountFiken['error']);
                        throw new Exception($accountFiken['error'], 400);
                    }

                    $subscriptionData = [
                        'quantity' => $request->quantity
                    ];

                    //create invoice
                    $createInvoice = Fiken::createInvoice($customerFiken, $FikenAddon, $FikenPlanAdditional = '', $accountFiken[0], $subscriptionData);
                    if(@$createInvoice['error']){
                        Helper::SendEmailIssue($createInvoice['error']);
                        throw new Exception($createInvoice['error'], 400);
                    }
                } catch (\Exception $e) {
                    info('fiken issue, Erro:' . $e->getMessage());
                    return $this->responseException($e->getMessage(), 400);
                }

                $billingDetail['billing_id'] = $newBilling->id;
                $billingDetail['addon_id'] = $addon->id;
                $billingDetail['amount'] = $totalAmount;
                $billingDetail['status'] = $status;
                $billingDetail['vat'] = $vat;
                $billingDetail['fiken_invoice_id'] = @$createInvoice['invoiceId'];

                BillingDetail::create($billingDetail);

                $this->pushNotification($user->id, $user->company_id, 2, [$subscription->user_id], 'addon_subscription', 'addon_subscription', $invoiceHistory->id, $addon->title, 'create');
                
                if($user->company->email){
                    $this->sendEmail($user->company->email,$user->company->id,$emailContent, $emailDescription);
                }    
            }
            DB::commit();
            return $this->responseSuccess('Addon purchase successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function planPurchaseCompleted(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }

            $plan = Plan::with('ActiveSubscription')->find($request->plan_id);

            if (empty($plan)) {
                return $this->responseException('Not found subscription', 404);
            }

            $billing = Billing::with('billingDetail')->where('subscription_id', $plan->ActiveSubscription->id)->latest()->first();

            $purchase['plan'] = $plan;
            $purchase['billing'] = $billing;
            $purchase['total_price'] = $plan->price + $billing->billingDetail['additional_user_amount'] - $billing->billingDetail['discount'];

            return $this->responseSuccess($purchase);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function activePlan()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }
            $access = [];
            if ($user->role->level == 0 || $user->role->level == 4) {
                return $this->responseSuccess($access);
            }
            $subscription = Subscription::where('company_id', $user->company_id)->select('plan_detail')->whereNull('deactivated_at')->whereNull('addon_id')->first();
            $newPlanAccess = $subscription->plan_detail['plan_detail'];

            $lastSubscriptions = Subscription::where('company_id', $user->company_id)->select('plan_detail')->whereNotNull('deactivated_at')->whereNull('addon_id')->get();
            
            if(count($lastSubscriptions) > 0){
                $lastPlanAccess = [];
                foreach ($lastSubscriptions as $lastSubscription)
                {
                    $lastPlanAccess = array_merge(array_filter($lastSubscription->plan_detail['plan_detail']),$lastPlanAccess);
                } 
                $diffAaccess = array_diff_assoc($lastPlanAccess,$newPlanAccess);
                $access['lastPlanAccess'] = $diffAaccess;
            }

            $access['planAccess'] = $newPlanAccess;
            
            return $this->responseSuccess($access);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function cancelPlan(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $subscription = Subscription::find($request->subscription_id);

            if (empty($subscription)) {
                return $this->responseException('Not found subscription', 404);
            }

            $today = Carbon::now();

            if ($subscription->trial_end_at && Carbon::parse($subscription->trial_end_at) > $today && !$subscription->billedDate) {
                $subscription->cancelled_at = Carbon::now();
                $subscription->deactivated_at = Carbon::now();
                $subscription->save();
            } else {

                $endSubscription = Carbon::parse($subscription->next_billing_at);
                $remainingDay = $today->diffInDays($endSubscription);

                if ($remainingDay < 30) {
                    $billedDate = $subscription->next_billing_at;
                    $nextbillingDate = Carbon::parse($subscription->next_billing_at)->addMonths($subscription->plan_detail['plan_type']);
                } else {
                    $billedDate = $subscription->next_billing_at;
                    $nextbillingDate = null;
                }

                $subscription->billed_at = $billedDate;
                $subscription->next_billing_at = $nextbillingDate;
                $subscription->cancelled_at = Carbon::now();
                $subscription->save();
            }

            $emailContent = EmailContent::where('key', 'plan_cancelled')->first();
            $emailDescription = str_replace('{company_name}', $user->company->name, $emailContent['description']);
            $emailDescription = str_replace('{plan_name}', $subscription->plan_detail['title'], $emailDescription);
            $invoiceDescription = "The company has canceled its " . $subscription->plan_detail['title'] . " plan";

            $invoiceHistory = InvoiceHistory::create([
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'billing_id' => $subscription->billing->id,
                'company_id' => $user->company->id,
            ]);

            if($user->company->email){
                $this->sendEmail($user->company->email,$user->company->id,$emailContent, $emailDescription);
            } 
            $this->pushNotification($user->id, $user->company_id, 2, [$subscription->user_id], 'plan_subscription', 'plan_subscription', $invoiceHistory->id, $subscription->plan_detail['title'], 'cancel');   

            return $this->responseSuccess('Plan cancel successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function cancelAddon(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $subscription = Subscription::find($request->subscription_id);

            if (empty($subscription)) {
                return $this->responseException('Not found subscription', 404);
            }

            $subscription->cancelled_at = Carbon::now();
            $subscription->billed_at = $subscription->next_billing_at;
            $subscription->next_billing_at = null;
            $subscription->save();

            $emailContent = EmailContent::where('key', 'addon_cancelled')->first();
            $emailDescription = str_replace('{company_name}', $user->company->name, $emailContent['description']);
            $emailDescription = str_replace('{addon_name}', $subscription->addon_detail['title'], $emailDescription);
            $invoiceDescription = "The company has canceled its " . $subscription->addon_detail['title'] . " addon";

            $invoiceHistory = InvoiceHistory::create([
                'user_id' => $user->id,
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'subscription_id' => $subscription->id,
                'billing_id' => $subscription->billing->id,
                'company_id' => $user->company->id,
            ]);

            if($user->company->email){
                $this->sendEmail($user->company->email,$user->company->id,$emailContent, $emailDescription);
            }
            $this->pushNotification($user->id, $user->company_id, 2, [$subscription->user_id], 'addon_subscription', 'addon_subscription', $invoiceHistory->id, $subscription->addon_detail['title'], 'cancel');   

            return $this->responseSuccess('Plan cancel successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function immediatelyDeactive(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $company = Company::find($request->company_id);

            if (empty($company)) {
                return $this->responseException('Not found company', 404);
            }
            $subscriptions = $company->subscriptions;

            foreach ($subscriptions as $subscription) {
                $subscription->deactivated_at = Carbon::now();
                $subscription->save();
            }
            $company->subscription_deactivated_at = Carbon::now();
            $company->save();

            $emailContent = EmailContent::where('key', 'immediately_cancel_subscription')->first();
            $emailDescription = str_replace('{company_name}', $company->name, $emailContent['description']);
            $invoiceDescription = "You have been notified that I have cancelled your subscription immediately";

            InvoiceHistory::create([
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

            if($user->company->email){
                $this->sendEmail($company->email,$company->id,$emailContent, $emailDescription);
            }    

            return $this->responseSuccess('Immediately all plan deactive.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function fikenCheckConfiguration($user)
    {
        $companyFiken = Fiken::getCompany();
        if (@$companyFiken['error']) {
            return $companyFiken;
        }

        if (!$user->fiken_customer_id) {
            $customerFiken = Fiken::createContact($user->company, $companyFiken['organizationNumber']);
            if (@$customerFiken['error']) {
                return $customerFiken;
            }
            $user->fiken_customer_id = $customerFiken['contactId'];
            $user->save();
        }
        return true;
    }

    public function stripeManage($user, $totalAmount, $paymentMethod, $plan, $addon)
    {
        $setting = Setting::where('key', 'stripe_system')->where('is_disabled', 1)->first();
        if (!$setting) {
            throw new Exception('please stripe setting update', 400);
        }
        $stripeSecretKey = @$setting->value_details['is_stripeMode'] ? @$setting->value_details['stripeLiveSecretKey'] : @$setting->value_details['stripeTestSecretKey'];

        $stripe = new \Stripe\StripeClient($stripeSecretKey);
        if (empty($user->customer_stripe_id)) {

            $customer = $stripe->customers->create([
                'email'          => $user->email,
                'name'           => @$user->first_name . ' ' . @$user->last_name,
                'payment_method' => $paymentMethod,
            ]);

            $user->update(['customer_stripe_id' => $customer->id]);
        }

        $customer = $stripe->customers->retrieve($user->customer_stripe_id);

        $activeCard = $user->cardActive;

        if ($activeCard) {
            $user->update(['payment_method'  => $activeCard->stipe_payment_id]);
        } else {
            $user->update(['payment_method'  => $paymentMethod]);

            $paymentMethodDefult = $stripe->customers->retrievePaymentMethod(
                $customer->id,
                $user->payment_method,
                []
            );

            if ($paymentMethodDefult && $user->payment_method) {

                $stripe->paymentMethods->attach(
                    $user->payment_method,
                    ['customer' => $customer->id]
                );

                CardDetails::create([
                    'user_id'   => $user->id,
                    'brand'     => $paymentMethodDefult->card->brand,
                    'last4'     => $paymentMethodDefult->card->last4,
                    'exp_month' => $paymentMethodDefult->card->exp_month,
                    'exp_year'  => $paymentMethodDefult->card->exp_year,
                    'status'    => 2,
                    'stipe_payment_id' => $paymentMethod
                ]);
            }
        }
        $stripe->paymentMethods->attach(
            $user->payment_method,
            ['customer' => $customer->id]
        );

        if ($plan && (!$plan->free_trial_months || $user->company->is_freeable)) {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount'                    => $totalAmount * 100,
                'currency'                  => 'nok',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'customer'                  => $customer->id,
                'payment_method'            => $user->payment_method,
                'off_session'               => true,
                'confirm'                   => true,
            ]);
        } elseif ($addon) {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount'                    => $totalAmount * 100,
                'currency'                  => 'nok',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'customer'                  => $customer->id,
                'payment_method'            => $user->payment_method,
                'off_session'               => true,
                'confirm'                   => true,
            ]);
        }
    }

    public function sendEmail($email,$company,$emailContent, $emailDescription)
    {
        try {
            Notification::route('mail', $email)
                ->notify(new NotifyPurchaseOrder($emailContent, $emailDescription));
            $emailStatus = EmailLog::SENT;
        } catch (\Exception $e) {
            info('notify-purchase, Erro:' . $e->getMessage());
            $emailStatus = EmailLog::FAIL;
        }

        EmailLog::create([
            'company_id' => $company,
            'type' => $emailContent->title,
            'description' => $emailDescription,
            'status' => $emailStatus,
            'for_admin' => 1,
            'for_company' => 1,
        ]);
        
    }
}