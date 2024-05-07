<?php

namespace App\Services;

use GuzzleHttp\Client;

class SlackService
{
    /**
     * The base uri to consume the slack
     * @var string
     */
    public $baseUri;
    /**
     * The secret to consume the slack
     * @var string
     */
    public $secret;

    public $version;

    public $tslVersioning;

    public function __construct()
    {
        $this->baseUri = config('services.slackService.base_uri');
        $this->version = config('services.slackService.version');
        $this->tslVersioning = [
            'curl' => [
                CURLOPT_PROXY => "http://http-proxy.wlink.com.np",
                CURLOPT_PROXYPORT => '5178',
                CURLOPT_TIMEOUT => 4
            ]
        ];
    }
    /**
     * Obtain the full list of Users from the slack 
     * Esupport push online users to relay[node project]
     * @return string
     */
    public function send(string $sendText)
    {
        $client = new Client(['base_uri' => $this->baseUri]);

        $payload = json_encode(['text' => $sendText]);

        try {
            $response = $client->request('POST', '', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $payload,
            ]);

            if ($response->getStatusCode() == 200) {
                return $response->getBody();
            } else {
                throw new \Exception('Failed to send message to Slack');
            }
        } catch (\Exception $e) {
            // Log the exception message for debugging
            logger('Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
