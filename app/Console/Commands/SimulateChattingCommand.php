<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class SimulateChattingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate chatting command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('SimulateChattingCommand executed successfully.');
        logger()->info('SimulateChattingCommand executed.');

        // Simulate chatting logic goes here
        $this->simulateChatting();

        return 0;
    }

    private function simulateChatting()
    {
        // Simulate chatting logic
        $this->info('Simulating chatting...');
        logger()->info('Simulating chatting...');

        // Example messages
        $messages = [
            'Hello! How can I help you today?',
            'What are your operating hours?',
            'Can you tell me more about your services?',
            'Thank you for the information!',
            'Goodbye!'
        ];

        // Get active chats
        $chats = Chat::where(['is_active' => true])->get();
        $this->info('Found ' . $chats->count() . ' active chats to simulate.');
        logger()->info('Found ' . $chats->count() . ' active chats to simulate.');

        $chats->each(function ($chat) use ($messages) {
            // Simulate sending a random message to each chat
            $randomMessage = $messages[array_rand($messages)];
           
            $this->info("Chat ID {$chat->id}: {$randomMessage}");
            logger()->info("Chat ID {$chat->id}: {$randomMessage}");

            Message::create([
                'chat_id' => $chat->id,
                'user_id' => 1, // System message
                'content' => $randomMessage,
            ]);
        });
    }
}
