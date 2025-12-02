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

        $howMany = $this->ask('How many messages to simulate per chat?');
        $chatId = $this->ask('Enter the chat ID to simulate messages for:');

        // Example messages
        $messages = [
            'Hello! How can I help you today?',
            'What are your operating hours?',
            'Can you tell me more about your services?',
            'Thank you for the information!',
            'Goodbye!'
        ];

        // Get active chats
        $chat = Chat::find($chatId);
        $this->info('Found 1 active chat to simulate.');
        logger()->info('Found 1 active chat to simulate.');

       // Simulate sending a random message to each chat
        $randomMessage = $messages[array_rand($messages)];
        
        $this->info("Chat ID {$chat->id}: {$randomMessage}");
        logger()->info("Chat ID {$chat->id}: {$randomMessage}");

        for ($i = 0; $i < $howMany; $i++) {
            Message::create([
                'chat_id' => $chat->id,
                'user_id' => 1, // System message
                'content' => $randomMessage . ' ' . ($i + 1),
            ]);
        }
    }
}
