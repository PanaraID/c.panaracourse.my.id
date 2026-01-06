<?php

namespace App\Console\Commands;

use App\Events\HelloWorld;
use Illuminate\Console\Command;

class HelloWorldEchoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hello-world-echo-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        HelloWorld::dispatch();
        $this->info('HelloWorld event dispatched successfully.');
    }
}
