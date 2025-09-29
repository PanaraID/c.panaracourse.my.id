<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'related_chat_id',
        'related_message_id',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedChat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'related_chat_id');
    }

    public function relatedMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'related_message_id');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function createForNewMessage(Message $message): void
    {
        $chat = $message->chat;
        $sender = $message->user;
        
        // Get all chat members except the sender
        $recipients = $chat->members()->where('users.id', '!=', $sender->id)->get();
        
        foreach ($recipients as $recipient) {
            static::create([
                'user_id' => $recipient->id,
                'type' => 'new_message',
                'title' => "Pesan baru di {$chat->title}",
                'message' => "{$sender->name}: " . \Str::limit($message->content, 50),
                'data' => [
                    'chat_slug' => $chat->slug,
                    'sender_name' => $sender->name,
                    'message_id' => $message->id,
                ],
                'related_chat_id' => $chat->id,
                'related_message_id' => $message->id,
            ]);
        }
    }
}
