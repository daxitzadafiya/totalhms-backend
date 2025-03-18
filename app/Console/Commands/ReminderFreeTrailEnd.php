<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Subscription;
use App\Notifications\NotifyReminderFreeTrailsEnd;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReminderFreeTrailEnd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:freeTrail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder Free Trails end';

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
        $subscriptions = Subscription::whereNotNull('trial_end_at')->whereNull('deactivated_at')->get();
        $today = Carbon::now();
        foreach ($subscriptions as $subscription) {
            $trailEndAt = Carbon::parse($subscription->trial_end_at);
            $remindingTrailDays = $today->diffInDays($trailEndAt);
            
            if ($remindingTrailDays == 5) {
                $company = Company::where('id', $subscription->company_id)->first();
                $emailContent = EmailContent::where('key', 'reminder_free_trails_end')->first();
                $emailDescription = str_replace('{company_name}', $company->name, $emailContent['description']);
                
                if ($company->email) {
                    try {
                        Notification::route('mail', $company->email)
                            ->notify(new NotifyReminderFreeTrailsEnd($emailContent, $emailDescription));
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
                $pushNotification->pushNotification($subscription->user_id, $subscription->company->id, 2, [$subscription->user_id], 'reminder', 'reminder_free_trails', '', $subscription->plan_detail['title'], 'reminder_free_trails');
            }
        }
    }
}
