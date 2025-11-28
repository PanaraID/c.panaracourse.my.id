<?php

namespace App\Observers;

use App\Models\Notification;

class MessageObserver
{
    public function created($message)
    {
        logger()->info("MessageObserver: Message ID {$message->id} has been created.");

        // Load the user relationship for future use
        $message->load('user');

        // Create notifications for other members
        Notification::createForNewMessage($message);

        // // Trigger the MessageSent event (without broadcasting)
        event(new \App\Events\MessageSent($message));
    }
}
