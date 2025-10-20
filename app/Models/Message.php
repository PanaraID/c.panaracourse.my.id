<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Events\MessageSent;

class Message extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'is_edited' => false,
    ];

    protected $casts = [
        'readed_at' => 'datetime',
        'file_size' => 'integer',
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

    /**
     * Get all tags for this message.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(MessageTag::class);
    }

    /**
     * Get tagged users for this message.
     */
    public function taggedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_tags', 'message_id', 'tagged_user_id')
                    ->withPivot(['tagged_by_user_id', 'is_read', 'created_at'])
                    ->withTimestamps();
    }
}
