<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'subscription_data',
    ];

    protected $casts = [
        'subscription_data' => 'array',
    ];

    /**
     * Relation to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create or update a subscription for a user
     */
    public static function updateOrCreateSubscription(int $userId, array $subscriptionData): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'endpoint' => $subscriptionData['endpoint'],
            ],
            [
                'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
                'auth_token' => $subscriptionData['keys']['auth'] ?? null,
                'subscription_data' => $subscriptionData,
            ]
        );
    }

    /**
     * Convert to WebPush Subscription object
     */
    public function toWebPushSubscription(): Subscription
    {
        return Subscription::create($this->subscription_data);
    }

    /**
     * Send a push notification to this subscription
     */
    public function sendPushNotification(string $title, string $body, array $data = []): bool
    {
        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/icons/icon-192x192.png',
            'badge' => '/icons/icon-72x72.png',
            'data' => $data,
        ]);

        try {
            $report = $webPush->sendOneNotification(
                $this->toWebPushSubscription(),
                $payload
            );

            // Delete subscription if it's expired or invalid
            if (!$report->isSuccess() && $report->getResponse() !== null) {
                $statusCode = $report->getResponse()->getStatusCode();
                if (in_array($statusCode, [404, 410])) {
                    $this->delete();
                    return false;
                }
            }

            return $report->isSuccess();
        } catch (\Exception $e) {
            \Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }
}

