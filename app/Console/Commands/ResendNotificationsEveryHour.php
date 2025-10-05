<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class ResendNotificationsEveryHour extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:resend-notifications-every-hour';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Logic to resend notifications goes here
        $this->info('ResendNotificationsEveryHour command executed successfully.');
        logger()->info('ResendNotificationsEveryHour command executed.');

        $notifications = Notification::whereNull('read_at')->whereNotNull('pushed_at')->get();
        $this->info('Found ' . $notifications->count() . ' notifications to resend.');
        logger()->info('Found ' . $notifications->count() . ' notifications to resend.');

        foreach ($notifications as $notification) {
            $notification->pushed_at = null; // Reset pushed_at to null to resend
            $notification->save();
            logger()->info("Notification ID {$notification->id} reset for resending.");
        }
        
        return 0;
    }
}
