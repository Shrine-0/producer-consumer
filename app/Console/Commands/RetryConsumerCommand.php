<?php

namespace App\Console\Commands;

use App\Consumers\QueueConsumer;
use App\Helpers\RedisHelper;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;

class RetryConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry consuming messages from the redis retry queue';

    /**
     * 
     * redis instance to be used
     */
    private $redisHelper;

    public function __construct(RedisHelper $redisHelper)
    {
        parent::__construct();

        $this->redisHelper = $redisHelper;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keys = $this->redisHelper->getKeys();
        $module = 'messagingError';
        foreach ($keys as $key => $value) {
            try {
                $message = $this->redisHelper->getMessage($value);
                $message = json_decode($message, true);

                $username = $message['message']['username'];
                $queue = $message['queue'];

                if (isset($username) && isset($queue)) {
                    $newArray = explode('-', $value);
                    $consumer  = $this->getConsumer($queue, $username);
                    $consumer->processQueue($username);
                    $this->redisHelper->deleteKey($username, $module, $newArray[4]);
                } else {
                    $this->error('Message is empty');
                }
            } catch (\Throwable $th) {
                $this->error('Error processing the message', $th->getMessage());
                // Log::error('Error processing the message', $th->getMessage());
            }
        }
    }

    protected function getConsumer($queueName, $username): QueueConsumer //make this method reusable later or make it global
    {
        $className = 'App\\Consumers\\' . $queueName . 'QueueConsumer';
        if (class_exists($className)) {
            return new $className($username);
        } else {
            throw new \InvalidArgumentException("No consumer found for queue: $queueName"); //
        }
    }
}
