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
    abstract protected function getQueueName(): string;

    /**
     * Transform the payload before posting to the destination API
     */
    abstract protected function transformPayload($data);

    /**
     * Get HTTP method for the destination API
     */
    abstract protected function getHttpMethod(): string;

    /**
     * Prepare source API request URL
     */
    protected function getSourceApiUrl(): string
    {
        return Config::get('services.' . $this->getQueueName() . '.source_api') . $this->username;
    }

    /**
     * Prepare destination API request URL
     */
    protected function getDestinationApiUrl(): string
    {
        return Config::get('services.' . $this->getQueueName() . '.destination_api') . $this->username;
    }

    /**
     * Fetch data from source API and perform HTTP request after payload transformation
     */
    public function processQueue($data)
    {
        try {
            $sourceData = $this->getDataFromSourceApi();
            $transformedData = $this->transformPayload($sourceData);
            $this->performHttpRequest($transformedData);
        } catch (\Exception $e) {
            // Log the error
            Log::error("Error processing queue '{$this->getQueueName()}': " . $e->getMessage());
            // Handle the error, for example, retrying the operation or logging the failure
            $this->handleError($e);
        }
    }

    /**
     * Get data from source API
     */
    protected function getDataFromSourceApi()
    {
        $response = $this->httpClient->get($this->getSourceApiUrl());
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Source API returned error: " . $response->getBody()->getContents());
        }
        return json_decode($response->getBody(), true);
    }

    /**
     * Perform HTTP request as per HTTP method to the destination API
     */
    protected function performHttpRequest($data)
    {
        $method = $this->getHttpMethod();
        $response = $this->httpClient->$method($this->getDestinationApiUrl(), [
            'json' => $data,
        ]);
        // Check the response status code and handle any errors if necessary
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Destination API returned error: " . $response->getBody()->getContents());
        }
    }

    /**
     * Handle error occurred during processing
     */
    protected function handleError(\Exception $e)
    {
        // Perform actions such as retrying, logging, or sending notifications
        // For now, we are just logging the error
        Log::error("Error handling failed: " . $e->getMessage());
    }
}
