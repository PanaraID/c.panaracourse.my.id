<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMessageNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $chat = $message->chat;
        $sender = $message->user;

        // Create notification for all chat members except the sender
        $recipients = $chat->members()
            ->where('users.id', '!=', $sender->id)
            ->get();

        foreach ($recipients as $recipient) {
            // Create in-app notification
            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => 'new_message',
                'title' => "Pesan baru dari {$sender->name}",
                'message' => \Str::limit($message->content, 100),
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

            // Send push notification to all user's devices
            $recipient->sendPushNotification(
                $notification->title,
                $notification->message,
                [
                    'url' => route('chat.show', ['chat' => $chat->slug]),
                    'chat_slug' => $chat->slug,
                    'message_id' => $message->id,
                ]
            );
        }
    }
}

