<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {userId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test push notification to a specific user or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found!");
                return 1;
            }
            $users = collect([$user]);
        } else {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->error('No users found!');
            return 1;
        }

        $this->info("Sending test push notifications to {$users->count()} user(s)...\n");

        foreach ($users as $user) {
            $subscriptions = $user->pushSubscriptions;
            
            if ($subscriptions->isEmpty()) {
                $this->warn("  ⚠ User #{$user->id} ({$user->name}) has no push subscriptions");
                continue;
            }

            $this->info("  📱 User #{$user->id} ({$user->name}) - {$subscriptions->count()} subscription(s)");

            $user->sendPushNotification(
                'Test Push Notification',
                'Ini adalah test notifikasi dari sistem. Jika Anda melihat ini, berarti push notification berhasil! 🎉',
                [
                    'url' => route('home'),
                    'test' => true,
                ]
            );

            $this->line("     ✅ Push notification sent!");
        }

        $this->newLine();
        $this->info('✨ Test push notifications completed!');
        
        return 0;
    }
}

