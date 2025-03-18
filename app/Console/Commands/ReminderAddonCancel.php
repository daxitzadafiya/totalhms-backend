<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifyAddonCancel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Company;
use App\Helpers\Helper;
use App\Models\Addon;
use Carbon\Carbon;
use Exception;
use Strex;

class ReminderAddonCancel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:addon_cancel';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder addon cancel';

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
        $addons = Addon::with('ActiveAddons')->withTrashed()->whereNotNull('deleted_at')->whereHas('subscriptions')->get();

        $today = Carbon::now();

        foreach ($addons as $addon) {
            foreach ($addon->ActiveAddons as $subscription) {

                $startDate = Carbon::parse($subscription->billed_at);
                $diffDays = $today->diffInDays($startDate);

                if ($diffDays == 30 || $diffDays == 15) {
                    $deadline = date_format(date_create($subscription->billed_at), 'd.m.Y');
                    $company = Company::where('id', $subscription->company_id)->first();
                    $emailContent = EmailContent::where('key', 'reminder_addon_cancel')->first();
                    $emailDescription = str_replace('{company_name}', $subscription->company->name, $emailContent['description']);
                    $emailDescription = str_replace('{addon_name}', $subscription->addon_detail['title'], $emailDescription);

                    if ($company->email) {
                        try {
                            Notification::route('mail', $subscription->company->email)
                                ->notify(new NotifyAddonCancel($emailContent, $emailDescription, $subscription->addon_detail['title'],$deadline));
                            $emailStatus = EmailLog::SENT;
                        } catch (\Exception $e) {
                            info('notify-purchase, Erro:' . $e->getMessage());
                            $emailStatus = EmailLog::FAIL;
                        }

                        EmailLog::create([
                            'company_id' => $subscription->company->id,
                            'type' => $emailContent->title,
                            'description' => 'Addon has been deleted by supper admin,please change your addon before expiry date',
                            'status' => $emailStatus,
                            'for_admin' => 1,
                        ]);
                    }
                    $pushNotification = new Controller();
                    $pushNotification->pushNotification($subscription->user_id, $subscription->company->id, 2, [$subscription->user_id], 'cancel', 'addon_cancel', $subscription->id, $addon->title, 'addon_cancel');

                    if ($company->phone_number) {
                        $emailContent = EmailContent::where('key', 'reminder_addon_cancel')->where('sms', 1)->first();
                        $emailContent = str_replace('{company_name}', $company['name'], $emailContent['sms_description']);
                        $emailDescription = str_replace('{addon_name}', $subscription->addon_detail['title'], $emailDescription);
                        
                        $message = $emailContent;
                        try {
                            $strex = Strex::sendMessage($message, str_replace(' ', '', $company->phone_number));
                            if (@$strex['error']) {
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
}
