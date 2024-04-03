<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Consumers\QueueConsumer;
use Illuminate\Support\Facades\Log;

class ConsumerCommand extends Command
{
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

        $this->declareExchangeQueue($channel, $queue);

        $this->info(" [*] Waiting for messages in $queue. To exit press CTRL+C");

        $callback = function ($msg) use ($queue) {

            $this->info(" [x] Received in queue : $msg->body");

            $message = json_decode($msg->body, true);
            $username = $message['username'];

            try {
                $consumer = $this->getConsumer($queue, $username);
                $consumer->processQueue($username);
            } catch (\Exception $e) {
                $this->error("Error processing message: " . $e->getMessage());
                Log::error("Error processing message: " . $e->getMessage());
            } finally {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
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

    protected function declareExchangeQueue($channel, $queue)
    {
        $channel->exchange_declare($queue, "fanout", false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $queue);
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
}
