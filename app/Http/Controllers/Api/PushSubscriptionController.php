<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    /**
     * Store a new push subscription
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        try {
            $subscription = PushSubscription::updateOrCreateSubscription(
                auth()->id(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Push subscription saved successfully',
                'subscription_id' => $subscription->id,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to save push subscription: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save push subscription',
            ], 500);
        }
    }

    /**
     * Delete a push subscription
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        try {
            PushSubscription::where('user_id', auth()->id())
                ->where('endpoint', $validated['endpoint'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Push subscription removed successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to remove push subscription: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove push subscription',
            ], 500);
        }
    }

    /**
     * Get VAPID public key
     */
    public function publicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('services.vapid.public_key'),
        ]);
    }
}

