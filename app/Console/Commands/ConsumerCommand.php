<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Consumers\QueueConsumer;
use App\Helpers\RedisHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumerCommand extends Command
{
    public $redisHelper;

    public function __construct(RedisHelper $redisHelper)
    {
        parent::__construct();

        $this->redisHelper = $redisHelper;
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consumer {queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'RabbitMQ Consumer';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $queue = $this->argument('queue');

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST')
        );

        $channel = $connection->channel();

        $this->declareExchangeQueue($channel, $queue, 'fanout');

        $this->info(" [*] Waiting for messages in $queue. To exit press CTRL+C");
        $callback = function ($msg) use ($queue) {
            $maxRetry = 5;
            $retryCount = 0;

            $this->info(" [x] Received in queue : $msg->body");

            $message = json_decode($msg->body, true);
            $username = $message['username'];

            while ($retryCount < 5) {
                try {
                    $consumer = $this->getConsumer($queue, $username);
                    $consumer->processQueue($username);
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    break;
                } catch (\Throwable $e) {
                    $this->error("Error processing message: " . $e->getMessage());
                    Log::error("Error processing message: " . $e->getMessage());
                    $this->error("retry count : $retryCount ");
                    $retryCount++;
                }
            }

            if ($retryCount == $maxRetry) {
                $this->info('redis');
                $result = [
                    'message' => $message,
                    'queue' => $queue
                ];
                $timestamp = time();
                $this->redisHelper->cacheResult($username, $result, 5, $timestamp);
                $this->info('redis-cache-stored');
            }
        };

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }

    protected function declareExchangeQueue($channel, $queue, $exchangeType, $routingKey = '')
    {
        $channel->exchange_declare($queue, $exchangeType, false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $queue, $routingKey);
    }

    protected function getConsumer($queueName, $username): QueueConsumer
    {
        $className = 'App\\Consumers\\' . $queueName . 'QueueConsumer';
        if (class_exists($className)) {
            return new $className($username);
        } else {
            throw new \InvalidArgumentException("No consumer found for queue: $queueName"); //
        }
    }
}
