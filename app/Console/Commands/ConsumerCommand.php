<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Consumers\QueueConsumer;
use App\Helpers\RedisHelper;
use Illuminate\Support\Facades\Log;

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
    protected $signature = 'rabbitmq:consumer {exchange}';

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
        $queueExchangeArray = config('rabbitmq.queueExchange');

        $exchange = $this->argument('exchange');
        $queue = $queueExchangeArray[$exchange];

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST')
        );
        $channel = $connection->channel();

        $this->declareExchangeQueue($channel, $exchange, $queue, 'fanout');

        $this->info(" [*] Waiting for messages in $queue. To exit press CTRL+C");
        $callback = function ($msg) use ($queue) {
            $maxRetry = 5;
            $retryCount = 0;

            $this->info(" [x] Received in queue : $msg->body");

            $username = $this->extractUsername($msg->body);
            $consumerCommandName = config('rabbitmq.consumerCommandName');

            while ($retryCount < 5) {
                try {
                    $consumer = $this->getConsumer($consumerCommandName[$queue], $username);
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
                    'message' => ['user_name' => $username],
                    'queue' => $queue
                ];
                $timestamp = time();
                $this->redisHelper->cacheResult($username, $result, 5, $timestamp);
                $this->info('redis-cache-stored');

                //multiple is set to false so the broker will nack the message specified by the delivery tag 
                //requeue is set to true so when a message is nacked the broker will requeue it again if false the broker will remove the nacked messages
                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
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

    protected function declareExchangeQueue($channel, $exchange, $queue, $exchangeType, $routingKey = '')
    {
        $channel->exchange_declare($exchange, $exchangeType, false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);
    }

    protected function getConsumer($queueName, $username): QueueConsumer
    {
        $className = 'App\\Consumers\\' . $queueName . 'QueueConsumer';
        if (class_exists($className)) {
            return new $className($username);
        } else {
            throw new \InvalidArgumentException("No consumer found for queue: $queueName");
        }
    }

    private function extractUsername($message)
    {
        $message = json_decode($message, true);
        if (isset($message['data']['customer']['user_name']))
            return $message['data']['customer']['user_name'];

        if (isset($message['data']['user_name']))
            return $message['data']['user_name'];

        if (isset($message['machine_name']))
            return $message['machine_name'];

        if (isset($message['user_name']))
            return  $message['user_name'];
    }
}
