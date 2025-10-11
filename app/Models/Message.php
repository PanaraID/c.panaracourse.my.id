<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\MessageSent;

class Message extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'is_edited' => false,
    ];

    protected $casts = [
        'readed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Message $message) {
            // Load the user relationship for future use
            $message->load('user');
            
            // Create notifications for other members
            \App\Models\Notification::createForNewMessage($message);
            
            // Trigger the MessageSent event (without broadcasting)
            event(new MessageSent($message));
        });
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
