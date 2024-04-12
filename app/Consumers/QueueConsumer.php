<?php

namespace App\Consumers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

abstract class QueueConsumer
{
    protected $httpClient;
    protected $username;

    public function __construct($username)
    {
        $this->httpClient = new Client();
        $this->username = $username;
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
            Log::error("Error processing queue '{$this->getEventName()}': " . $e->getMessage());
            $this->handleError($e);
        }
    }

    /**
     * Get data from source API
     */
    protected function getDataFromSourceApi($username)
    {
        $apiconfigs = $this->sourceApiConfig($username);

        $data = [];
        foreach ($apiconfigs as $key => $value) { //asynchronous call instead of loop
            $response[$key] = $this->httpClient->get(
                $value['api'],
                $value['headers']
            );
            $data[$key] = json_decode($response[$key]->getBody(), true);
        }
        return $data;
    }

    /**
     * Perform HTTP request as per HTTP method to the destination API
     */
    protected function performHttpRequest($data)
    {
        echo ($this->getDestinationApiUrl());

        $method = $this->getDestinationApiHttpMethod();
        $response = $this->httpClient->$method($this->getDestinationApiUrl(), [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $data,
        ]);

        // Check the response status code and handle any errors if necessary
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Destination API returned error: " . $response->getBody()->getContents());
        }

        print_r(json_decode($response->getBody()->getContents()));
    }

    /**
     * Handle error occurred during processing
     */
    protected function handleError(\Exception $e)
    {
        // Perform actions such as retrying, logging, or sending notifications
        Log::error("Error handling failed: " . $e->getMessage());
    }
}
