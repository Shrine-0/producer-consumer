<?php

namespace App\Console\Commands;

use App\Helpers\Encrypter;
use App\Helpers\Logger;
use Illuminate\Console\Command;

class EncryptNumberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encryption:command {value} {process}';

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
        $value = $this->argument('value');
        $process  = $this->argument('process');

        if ($process == 'encrypt') {
            echo (Encrypter::handle($value) . "\n");
            // return Command::SUCCESS;
        }

        if ($process == 'decrypt') {
            echo (Encrypter::handle($value, 'decrypt') . "\n");
            // return Command::SUCCESS;
        }
    }
}
