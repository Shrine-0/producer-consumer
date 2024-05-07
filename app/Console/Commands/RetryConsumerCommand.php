<?php

namespace App\Console\Commands;

use App\Consumers\QueueConsumer;
use App\Helpers\Logger;
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

    private $logger;

    public function __construct(RedisHelper $redisHelper, Logger $logger)
    {
        parent::__construct();

        $this->redisHelper = $redisHelper;
        $this->logger = $logger;
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

                $this->logger->logs('start', 'retryCommand', $queue, $username);
                if (isset($username) && isset($queue)) {
                    $newArray = explode('-', $value);
                    $consumer  = $this->getConsumer($queue, $username);
                    $consumer->processQueue($username);
                    $this->redisHelper->deleteKey($username, $module, $newArray[4]);
                } else {
                    $this->logger->errorLogs('error', 'retryCommand', $queue, $username);
                }
                $this->logger->logs('finish', 'retryCommand', $queue, $username);
            } catch (\Throwable $th) {
                $this->logger->errorLogs('error', 'retryCommandError', $queue, $username);
            }
        }
    }

    protected function getConsumer($queueName, $username): QueueConsumer
    {
        $className = 'App\\Consumers\\' . $queueName . 'QueueConsumer';
        if (class_exists($className)) {
            return new $className($username, $this->logger);
        } else {
            $this->logger->error("No consumer found for queue: $queueName");
        }
    }
}
