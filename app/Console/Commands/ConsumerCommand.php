<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Consumers\QueueConsumer;
use App\Helpers\Logger;
use App\Helpers\RedisHelper;
use App\Services\SlackService;
use Carbon\Carbon;

class ConsumerCommand extends Command
{
    public $redisHelper;
    public $logger;

    public function __construct(RedisHelper $redisHelper, Logger $logger)
    {
        parent::__construct();

        $this->redisHelper = $redisHelper;
        $this->logger = $logger;
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
            env('RABBITMQ_VHOST'),
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            3.0,
            null,
            false,
            60
        );
        while (true) {
            try {
                $channel = $connection->channel();

                $this->declareExchangeQueue($channel, $exchange, $queue, 'fanout');

                $this->logger->notice(" [*] Waiting for messages in $queue. To exit press CTRL+C");

                $callback = function ($msg) use ($queue) {
                    $maxRetry = 5;
                    $retryCount = 0;

                    $this->logger->notice(" [x] Received in queue : $msg->body");

                    $username = $this->extractUsername($msg->body);
                    if ($username === null) {
                        $this->logger->error("Username not received", ["queue" => $queue]);
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                        return;
                    }
                    $consumerCommandName = config('rabbitmq.consumerCommandName');

                    $this->logger->logs('start', $consumerCommandName[$queue] . "Consumer", $queue, $username);

                    while ($retryCount < 5) {
                        try {
                            $consumer = $this->getConsumer($consumerCommandName[$queue], $username);
                            $consumer->processQueue($username);
                            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                            break;
                        } catch (\Throwable $e) {
                            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                            $this->logger->errorLogs('error', 'retryOnError', $queue, $username, "Error with retryCount : " . $retryCount + 1);
                            $retryCount++;
                        }
                    }

                    if ($retryCount == $maxRetry) {
                        $this->logger->logs('start', 'redisStoreOnMaxRetry', $queue, $username);
                        $result = [
                            'message' => ['username' => $username],
                            'queue' => $consumerCommandName[$queue]
                        ];
                        $now = Carbon::now();
                        $timestamp = $now->format('Y:m:d::H:i:s');
                        $this->redisHelper->cacheResult($username, $result, 5, $timestamp); //cache tag concept to be added instead of timestamp
                        $this->logger->logs('finish', 'redisStoreOnMaxRetry', $queue, $username);


                        //multiple is set to false so the broker will nack the message specified by the delivery tag 
                        //requeue is set to true so when a message is nacked the broker will requeue it again if false the broker will remove the nacked messages
                        // $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
                    }

                    $this->logger->logs('finish', "$consumerCommandName[$queue]Consumer", $queue, $username);
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
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                sleep(5);
            }
        }
        return Command::SUCCESS;
    }

    protected function declareExchangeQueue($channel, $exchange, $queue, $exchangeType, $routingKey = '')
    {
        $this->logger->logs('start', 'queueBind', $queue, '');

        $channel->exchange_declare($exchange, $exchangeType, false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);

        $this->logger->logs('finish', 'queueBind', $queue, '');
    }

    protected function getConsumer($queueName, $username): QueueConsumer
    {
        $className = 'App\\Consumers\\' . $queueName . 'QueueConsumer';
        if (class_exists($className)) {
            return new $className($username, $this->logger);
        } else {
            $this->logger->error("No consumer found for queue", ['queuename' => $queueName, 'username' => $username]);
            throw new \Exception("No consumer found for queue : $queueName");
        }
    }

    private function extractUsername($message)
    {
        $message = json_decode($message, true);
        if (isset($message['data']['username']))
            return $message['data']['username'];

        if (isset($message['data']['customer']['user_name']))
            return $message['data']['customer']['user_name'];

        if (isset($message['data']['user_name']))
            return $message['data']['user_name'];

        if (isset($message['data']['machine_name']))
            return $message['data']['machine_name'];

        if (isset($message['machine_name']))
            return $message['machine_name'];

        if (isset($message['user_name']))
            return $message['user_name'];
    }
}
