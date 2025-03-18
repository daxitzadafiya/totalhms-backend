<?php

namespace App\Console;

use App\Console\Commands\BillingInvoice;
use App\Console\Commands\CreateTask;
use App\Console\Commands\ReminderAddonCancel;
use App\Console\Commands\ReminderFreeTrailEnd;
use App\Console\Commands\ReminderInvoice;
use App\Console\Commands\ReminderPlanCancel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(BillingInvoice::class)->hourly();
        $schedule->command(ReminderInvoice::class)->hourly();
        $schedule->command(ReminderFreeTrailEnd::class)->hourly();
        $schedule->command(ReminderPlanCancel::class)->hourly();
        $schedule->command(ReminderAddonCancel::class)->hourly();
        $schedule->command(CreateTask::class)->everyMinute();
        
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
