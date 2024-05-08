<?php

namespace App\Consumers;

use App\Helpers\Logger;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

abstract class QueueConsumer
{
    protected $httpClient;
    protected $username;
    public $logger;
    public function __construct($username, Logger $logger)
    {
        $this->httpClient = new Client();
        $this->username = $username;
        $this->logger = $logger;
    }

    /**
     * Get name of queue
     */
    abstract protected function getEventName(): string;

    /**
     * Source api configurations
     */
    abstract protected function sourceApiConfig($username): array;

    /**
     * Transform the payload before posting to the destination API
     */
    abstract protected function transformPayload($data): array;

    /**
     * Get HTTP method for the destination API
     */
    abstract protected function getDestinationApiHttpMethod(): string;

    /**
     * Get query params for the destination API
     */
    abstract protected function destinationApiQueryParams($username): string;

    /**
     * Prepare destination API request URL
     */
    protected function getDestinationApiUrl(): string
    {
        return Config::get('services.' . $this->getEventName() . '.destination_api') . $this->destinationApiQueryParams($this->username);
    }

    /**
     * Prepare destination API KEY
     */
    protected function getDestinationApiKey(): string
    {
        return Config::get('services.' . $this->getEventName() . '.destination_api_key');
    }

    /**
     * Fetch data from source API and perform HTTP request after payload transformation
     */
    public function processQueue($username)
    {
        try {
            $sourceData = $this->getDataFromSourceApi($username);
            $transformedData = $this->transformPayload($sourceData);
            $this->performHttpRequest($transformedData);
        } catch (\Exception $e) {
            $messages[] = explode("\n", $e->getMessage());
            $this->logger->errorLogs('error', 'ProcessQueue', $this->queueNameSpecifier($this->getEventName()), $this->username, json_encode($messages[0]));
            sleep(5);
        }
    }

    /**
     * Get data from source API
     */
    protected function getDataFromSourceApi($username)
    {
        $this->logger->logs('start', 'GetDataFromSourceApi', $this->queueNameSpecifier($this->getEventName()), $this->username);
        $apiConfigs = $this->sourceApiConfig($username);

        $data = [];
        foreach ($apiConfigs as $key => $value) { //asynchronous call instead of loop
            $response[$key] = $this->httpClient->get(
                $value['api'],
                $value['headers']
            );
            $data[$key] = json_decode($response[$key]->getBody(), true);
        }
        $this->logger->logs('finish', 'GetDataFromSourceApi', $this->queueNameSpecifier($this->getEventName()), $this->username, json_encode($data));

        return $data;
    }

    /**
     * Perform HTTP request as per HTTP method to the destination API
     */
    protected function performHttpRequest($data)
    {
        // echo ($this->getDestinationApiUrl());
        $this->logger->logs('start', 'PerformHttpRequest', $this->queueNameSpecifier($this->getEventName()), $this->username, json_encode($data));
        $method = $this->getDestinationApiHttpMethod();
        $response = $this->httpClient->$method($this->getDestinationApiUrl(), [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $data,
        ]);
        $this->logger->logs('finish', 'PerformHttpRequest', $this->queueNameSpecifier($this->getEventName()), $this->username, json_encode($response->getBody()->getContents()));
        // Check the response status code and handle any errors if necessary
        if ($response->getStatusCode() !== 200) {
            $this->logger->errorLogs('error', 'PerformHttpRequest', $this->queueNameSpecifier($this->getEventName()), $this->username, json_encode($response->getBody()->getContents()));
        }

        // print_r(json_decode($response->getBody()->getContents()));
    }

    public function queueNameSpecifier(string $eventName)
    {
        $array  = array_flip(config('rabbitmq.consumerCommandName'));
        return $array[$eventName];
    }
}
