<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Events\MessageSent;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id', 
        'content',
        'is_edited',
        'edited_at',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    protected $attributes = [
        'is_edited' => false,
    ];

    protected $casts = [
        'readed_at' => 'datetime',
        'file_size' => 'integer',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
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

    /**
     * Mark this message as edited.
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}
