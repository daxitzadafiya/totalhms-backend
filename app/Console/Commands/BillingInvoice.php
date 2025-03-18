<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Notification;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\InvoiceHistory;
use App\Models\Setting;
use App\Models\Subscription;
use App\Notifications\NotifyPurchaseOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Fiken;

class BillingInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'monthly billing charges invoice';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $subscriptions = Subscription::Active()->get();
            $companyFiken = Fiken::getCompany();

            $today = Carbon::now();
            foreach ($subscriptions as $subscription) {

                // 1. IF Subscription for Addon
                if ($subscription->addon_id) {
                    if (!$subscription->cancelled_at && Carbon::parse($subscription->next_billing_at) <= $today) {
                        $subscription->billed_at = $subscription->next_billing_at;
                        $subscription->next_billing_at = Carbon::parse($subscription->next_billing_at)->addMonths(1);
                        $subscription->save();
                        $this->generateInvoiceForAddon($subscription);

                    } elseif ($subscription->cancelled_at && Carbon::parse($subscription->billed_at) <= $today) {
                        $subscription->deactivated_at = Carbon::now();
                        $subscription->save();
                    }
                    return;
                }
                // 2. IF Subscription for Plan 
                if ($subscription->trial_end_at && Carbon::parse($subscription->trial_end_at) <= $today && !$subscription->cancelled_at && !$subscription->billedDate) {
                    // 3. After finished trial period 
                    $subscription->billed_at = Carbon::now();
                    $subscription->next_billing_at = Carbon::now()->addMonths($subscription->plan_detail['plan_type']);
                    $subscription->trial_end_at = null;
                    $subscription->save();
                    $this->generateInvoiceForPlan($subscription);
                } else {
                    //4. Renew the subscription
                    if (!$subscription->cancelled_at && $subscription->next_billing_at && Carbon::parse($subscription->next_billing_at) <= $today) {

                        $subscription->billed_at = $subscription->next_billing_at;
                        $subscription->next_billing_at = Carbon::parse($subscription->next_billing_at)->addMonths($subscription->plan_detail['plan_type']);
                        $subscription->save();
                        $this->generateInvoiceForPlan($subscription);
                    } elseif ($subscription->cancelled_at) {

                        // 5. Subscription was cancelled with Next billing
                        if ($subscription->next_billing_at) {
                            if (Carbon::parse($subscription->billed_at) <= $today) {
                                $subscription->billed_at = $subscription->next_billing_at;
                                $subscription->next_billing_at = null;
                            }
                        } else {
                            // 6. Subscription was cancelled
                            if (Carbon::parse($subscription->billed_at) <= $today) {
                                $subscription->deactivated_at = Carbon::now();
                                $subscription->save();
                                return;
                            }
                        }
                        $subscription->save();
                        $this->generateInvoiceForPlan($subscription);
                    }
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function generateInvoiceForPlan($subscription)
    {
        try {

            $billing = Helper::billing($subscription);

            $lastBillingDetail = $subscription->billing->billingDetail;

            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();

            $newBilling = Billing::create($billing);

            $emailContent = EmailContent::where('key', 'plan_renewed')->first();
            $emailDescription = str_replace('{company_name}', $subscription->company->name, $emailContent['description']);
            $emailDescription = str_replace('{plan_name}', $subscription->plan_detail['title'], $emailDescription);

            $invoiceDescription = "I have renewed your plan" . $subscription->plan_detail['title'];

            $invoiceHistory = InvoiceHistory::create([
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'billing_id' => $newBilling->id,
                'company_id' => $subscription->company->id,
            ]);

            // additional user price
            $lastAdditinalUsers = $lastBillingDetail->additional_user;
            $lastAdditinalUserPrice = $lastBillingDetail->additional_user_amount;

            $subscribedUsers = $subscription->plan_detail['total_users'] + 1; // 

            // extra user
            $extraAdditinalUsers = 0;
            $extraAdditinalUserPrice = 0;
            if ($billing['employee'] > $subscribedUsers) {
                $extraAdditinalUsers =  $billing['employee'] - $subscribedUsers;
                $extraAdditinalUserPrice = $subscription->plan_detail['additional_price'] * $extraAdditinalUsers;
            }

            // final additional user & price
            $additinalUsers = $lastAdditinalUsers + $extraAdditinalUsers;
            $additinalUserPrice = $lastAdditinalUserPrice + $extraAdditinalUserPrice;

            // plan total price with discount and additional price
            $totalPrice = $subscription->plan_detail['price'] + $additinalUserPrice - $lastBillingDetail->discount;

            //vat calculation
            $vat = 0;
            if ($vatSetting) {
                $vat = ($totalPrice * $vatSetting->value) / 100;
            }

            //total plan amount
            $planPrice = $totalPrice + $vat;

            $status = $this->handleStripe($subscription, $planPrice);

            try {
                $accountFiken = Fiken::getAccounts();

                //get product information
                if ($subscription->plan_detail['fiken_plan_id']) {
                    $FikenPlan = Fiken::getProduct($subscription->plan_detail['fiken_plan_id']);
                }

                //get product additional information
                if ($subscription->plan_detail['fiken_additional_id']) {
                    $FikenPlanAdditional = Fiken::getProduct($subscription->plan_detail['fiken_additional_id']);
                }

                if ($subscription->user->fiken_customer_id) {
                    $customerFiken = Fiken::getContact($subscription->user->fiken_customer_id);
                }

                $subscriptionData = [
                    'discount' => $lastBillingDetail->discount * 100 / $subscription->plan_detail['price'],
                    'quantity' => 1,
                    'additional_users' => $additinalUsers
                ];

                if(!$additinalUsers){
                    $FikenPlanAdditional = '';
                }
                //create invoice
                $createInvoice = Fiken::createInvoice($customerFiken, $FikenPlan, $FikenPlanAdditional, $accountFiken[0], $subscriptionData);
            } catch (\Exception $e) {
                info('fiken issue, Erro:' . $e->getMessage());
            }

            $billingDetail['billing_id'] = $newBilling->id;
            $billingDetail['plan_id'] = $subscription->plan_id;
            $billingDetail['additional_user'] = $additinalUsers;
            $billingDetail['additional_user_amount'] = $additinalUserPrice;
            $billingDetail['discount'] = $lastBillingDetail->discount;
            $billingDetail['vat'] = $vat;
            $billingDetail['amount'] = $subscription->plan_detail['price'] * $subscription->quantity;
            $billingDetail['status'] = $status;
            $billingDetail['fiken_invoice_id'] = @$createInvoice['invoiceId'];

            BillingDetail::create($billingDetail);

            if($subscription->company->email){
                $this->sendEmail($subscription->company->email,$subscription->company->id,$emailContent, $emailDescription);
            }
            $pushNotification = new Controller();
            $pushNotification->pushNotification($subscription->user_id, $subscription->company->id, 2, [$subscription->user_id], 'plan_subscription', 'plan_subscription', $invoiceHistory->id, $subscription->plan_detail['title'], 'renew');

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function generateInvoiceForAddon($subscription)
    {
        try {

            $lastBillingDetail = $subscription->billing->billingDetail;

            $addonPrice = $lastBillingDetail->amount;

            $vatSetting = Setting::where('key', 'vat')->where('is_disabled',1)->first();

            //vat calculation
            $vat = 0;
            if ($vatSetting) {
                $vat = ($addonPrice * $vatSetting->value) / 100;
            }

            //total addon amount
            $addonPrice = $addonPrice + $vat;

            $status = $this->handleStripe($subscription, $addonPrice);

            $billing = Helper::billing($subscription);

            $newBilling = Billing::create($billing);

            $emailContent = EmailContent::where('key', 'addon_renewed')->first();
            $emailDescription = str_replace('{company_name}', $subscription->company->name, $emailContent['description']);
            $emailDescription = str_replace('{addon_name}', $subscription->addon_detail['title'], $emailDescription);

            $invoiceDescription = "I have renewed your addon" . $subscription->addon_detail['title'];

            $invoiceHistory = InvoiceHistory::create([
                'title' =>  $emailContent->title,
                'description' => $invoiceDescription,
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'billing_id' => $newBilling->id,
                'company_id' => $subscription->company->id,
            ]);

            try {
                $accountFiken = Fiken::getAccounts();

                //get product information
                if ($subscription->addon_detail['fiken_plan_id']) {
                    $FikenPlan = Fiken::getProduct($subscription->addon_detail['fiken_plan_id']);
                }

                if ($subscription->user->fiken_customer_id) {
                    $customerFiken = Fiken::getContact($subscription->user->fiken_customer_id);
                }

                $subscriptionData = ['quantity' => $subscription->quantity];

                //create invoice
                $createInvoice = Fiken::createInvoice($customerFiken, $FikenPlan, $FikenPlanAdditional ='', $accountFiken[0], $subscriptionData);
            } catch (\Exception $e) {
                info('fiken issue, Erro:' . $e->getMessage());
            }

            $billingDetail['billing_id'] = $newBilling->id;
            $billingDetail['addon_id'] = $subscription->addon_id;
            $billingDetail['amount'] = $addonPrice;
            $billingDetail['status'] = $status;
            $billingDetail['fiken_invoice_id'] = @$createInvoice['invoiceId'];

            BillingDetail::create($billingDetail);

            if($subscription->company->email){
                $this->sendEmail($subscription->company->email,$subscription->company->id,$emailContent, $emailDescription);
            } 
            $pushNotification = new Controller();
            $pushNotification->pushNotification($subscription->user_id, $subscription->company->id, 2, [$subscription->user_id], 'addon_subscription', 'addon_subscription', $invoiceHistory->id, $subscription->addon_detail['title'], 'renew');   

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function handleStripe($subscription, $price)
    {
        try {
            $user = $subscription->user;

            if ($subscription->pay_by == Subscription::CARD) {

                $setting = Setting::where('key', 'stripe_system')->where('is_disabled',1)->first();
                $stripeSecretKey = @$setting->value_details['is_stripeMode'] ? @$setting->value_details['stripeLiveSecretKey'] : @$setting->value_details['stripeTestSecretKey'];

                $stripe = new \Stripe\StripeClient($stripeSecretKey);
                $customer = $stripe->customers->retrieve($user->customer_stripe_id);

                $stripe->paymentIntents->create([
                    'amount'                    => $price * 100,
                    'currency'                  => 'nok',
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                    'customer'                  => $customer->id,
                    'payment_method'            => $user->payment_method,
                    'off_session'               => true,
                    'confirm'                   => true,
                ]);

                $status = BillingDetail::PAID;
            } else {
                $status = BillingDetail::PENDING;
            }
            return $status;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
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