<?php

namespace App\Models;

use App\Events\NotificationSentEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            // Trigger the NotificationSent event (without broadcasting)
            event(new NotificationSentEvent($notification));
        });
    }

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
        return ! is_null($this->read_at);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForChat($query, $chatId)
    {
        return $query->where('related_chat_id', $chatId);
    }

    public static function markChatNotificationsAsRead($userId, $chatId): int
    {
        return static::where('user_id', $userId)
            ->where('related_chat_id', $chatId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function createForNewMessage(Message $message): void
    {
        $chat = $message->chat;
        $sender = $message->user;
        logger()->info("Creating notifications for new message ID {$message->id} in chat ID {$chat->id} from user ID {$sender->id}");

        // Get all chat members except the sender
        $recipients = $chat->members()->get();
        $recipients = $recipients->filter(function ($user) use ($sender) {
            return $user->id !== $sender->id;
        });
        logger()->info('Found '.$recipients->count().' recipients for the notification.');

        foreach ($recipients as $recipient) {
            static::create([
                'user_id' => $recipient->id,
                'type' => 'new_message',
                'title' => "Ada pesan dari {$sender->name}",
                'message' => 'Pesan: '.\Str::limit($message->content, 100),
                'data' => [
                    'chat_slug' => $chat->slug,
                    'chat_title' => $chat->title,
                    'sender_name' => $sender->name,
                    'sender_id' => $sender->id,
                    'message_id' => $message->id,
                    'message_content' => $message->content,
                ],
                'related_chat_id' => $chat->id,
                'related_message_id' => $message->id,
            ]);

        }
    }
}
