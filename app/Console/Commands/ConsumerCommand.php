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
        $queueName = $this->argument('queue');

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD')
        );

        $channel = $connection->channel();

        $this->declareQueue($channel, $queueName);

        $this->info(" [*] Waiting for messages in $queueName. To exit press CTRL+C");

        $callback = function ($msg) use ($queueName) {

            $this->info(" [x] Received in queue :");
            $this->line($msg->body);

            // NEED TO GET USERNAME HERE
            $username = 'samirhusen_home'; // Default username for now

            try {
                $consumer = $this->getConsumer($queueName, $username);
                $consumer->processQueue($msg->body);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $this->error("Error processing message: " . $e->getMessage());
                // Log the error
                Log::error("Error processing message: " . $e->getMessage());
            }
        };

        $channel->basic_consume(
            $queueName,
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

    protected function declareQueue($channel, $queueName)
    {
        $channel->queue_declare(
            $queueName,
            false,
            true,
            false,
            false
        );
    }

    protected function getConsumer($queueName, $username): QueueConsumer
    {
        $className = 'App\\Consumers\\' . $queueName . 'Consumer';
        if (class_exists($className)) {
            return new $className($username);
        } else {
            throw new \InvalidArgumentException("No consumer found for queue: $queueName");
        }
    }
}
