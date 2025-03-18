<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Company;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Subscription;
use App\Notifications\NotifyRemainderInvoice;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Strex;

class ReminderInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reminder invoice payment';

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
        $subscriptions = Subscription::whereHas('billing', function ($query) {
            $query->WhereHas('billingDetail', function ($q) {
                $q->where('status', 0);
            });
        })->whereNull('deactivated_at')->get();

        $today = Carbon::now();

        foreach ($subscriptions as $subscription) {

            $startDate = Carbon::parse($subscription->billed_at);

            $billingDays = $today->diffInDays($startDate);

            $company = Company::where('id', $subscription->company_id)->first();
            $emailContent = EmailContent::where('key', 'reminder_invoice')->first();
            $emailDescription = str_replace('{company_name}', $company['name'], $emailContent['description']);
            $subscriptionType = $subscription->plan_id ? 'Plan' : 'Addon';

            if ($billingDays == 3 || $billingDays == 10) {

                if ($company->email) {
                    try {
                        Notification::route('mail', $company->email)
                            ->notify(new NotifyRemainderInvoice($emailContent, $subscriptionType, $emailDescription));
                        $emailStatus = EmailLog::SENT;
                    } catch (\Exception $e) {
                        Log::debug('Failed to send email: ', ['error' => $e]);
                        $emailStatus = EmailLog::FAIL;
                    }
                    EmailLog::create([
                        'company_id' => $company->id,
                        'type' => $emailContent->title,
                        'description' => $emailDescription,
                        'status' => $emailStatus,
                        'for_admin' => 1,
                        'for_company' => 1,
                    ]);
                }
                $pushNotification = new Controller();
                $pushNotification->pushNotification($subscription->user_id, $subscription->company->id, 2, [$subscription->user_id], 'reminder', 'reminder_invoice', '', $subscriptionType, 'reminder_invoice');

                if ($company->phone_number) {
                    $emailContent = EmailContent::where('key', 'reminder_invoice')->where('sms',1)->first();
                    $emailContent = str_replace('{company_name}', $company['name'], $emailContent['sms_description']);
                    $message = $emailContent;
                    try {
                        $strex = Strex::sendMessage($message, str_replace(' ', '', $company->phone_number));
                        if(@$strex['error']){
                            Helper::SendEmailIssue($strex['error']);
                        }
                    } catch (Exception $e) {
                        Log::debug('Failed to send text SMS: ', ['error' => $e]);
                    }
                }
            }
        }
    }
}