<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\MessageSent;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
        'is_edited',
        'edited_at'
    ];

    protected $attributes = [
        'is_edited' => false,
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime'
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

    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now()
        ]);
    }
}
