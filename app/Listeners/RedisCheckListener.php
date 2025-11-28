<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class RedisCheckListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DiagnosingHealth $event): void
    {
        try {
            $conn = Redis::connection();
            $conn->ping();
        } catch (\Exception $e) {
            throw new \RuntimeException('Redis connection failed: ' . $e->getMessage());
        }
    }
}
