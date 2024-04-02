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
     * Transform the payload before posting to the destination API
     */
    abstract protected function transformPayload($data);

    /**
     * Get HTTP method for the destination API
     */
    abstract protected function getHttpMethod(): string;

    /**
     * Get query params for the source API
     */
    abstract protected function sourceApiQueryParams(): string;

    /**
     * Get query params for the destination API
     */
    abstract protected function destinationApiQueryParams(): string;

    /**
     * Prepare source API request URL
     */
    protected function getSourceApiUrl(): string
    {
        return Config::get('services.' . $this->getEventName() . '.source_api') . $this->username . $this->sourceApiQueryParams();
    }

    /**
     * Prepare destination API request URL
     */
    protected function getDestinationApiUrl(): string
    {
        return Config::get('services.' . $this->getEventName() . '.destination_api') . $this->username . $this->destinationApiQueryParams();
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
            Log::error("Error processing queue '{$this->getEventName()}': " . $e->getMessage());
            $this->handleError($e);
        }
    }

    /**
     * Get data from source API
     */
    protected function getDataFromSourceApi()
    {
        echo ($this->getSourceApiUrl());

        $response = $this->httpClient->get($this->getSourceApiUrl());
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Source API returned error: " . $response->getBody()->getContents());
        }

        print_r(json_decode($response->getBody()->getContents()));

        return json_decode($response->getBody(), true);
    }

    /**
     * Perform HTTP request as per HTTP method to the destination API
     */
    protected function performHttpRequest($data)
    {
        echo ($this->getDestinationApiUrl());

        $method = $this->getHttpMethod();
        $response = $this->httpClient->$method($this->getDestinationApiUrl(), [
            'form_params' => $data,
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
