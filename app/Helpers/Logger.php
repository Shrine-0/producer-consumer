<?php

namespace App\Helpers;

use App\Helpers\Contracts\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLogger;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard as Auth;

class Logger implements LoggerInterface
{
    private $logger;
    private $request;
    private $auth;
    private $jwt;

    protected $level;
    protected $message;
    protected $context;

    protected $route;
    protected $extra;

    public function __construct(
        PsrLogger $logger,
        Request $request,
        Auth $auth
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->auth = $auth;
    }

    public function logs(string $status, string $processname, string $queuename, $username = null, $message = null)
    {
        $message = json_encode([
            'status' => ucfirst($status),
            'processName' => $processname,
            'queueName' => $queuename,
            'username' => $username,
            'message' => $message
        ]);

        // Add ANSI escape code for red colour
        $coloredMessage = "\033[32m" . $message . "\033[0m"; // Green colour

        $this->logger->info($coloredMessage);
    }

    public function errorLogs(string $status, string $processname, string $queuename, $username = null, $message = null)
    {
        $message = json_encode([
            'status' => ucfirst($status),
            'processName' => $processname,
            'queueName' => $queuename,
            'username' => $username,
            'message' => $message
        ]);
        // Add ANSI escape code for red colour
        $coloredMessage = "\033[31m" . $message . "\033[0m"; // Red colour

        $this->logger->error($coloredMessage);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($this->parse($message), $context);
    }

    public function info(string $message, array $context = [], string $queuename = null, string $username = null): void
    {
        $this->logger->info($this->parse($message), $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($this->parse($message), $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($this->parse($message), $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($this->parse($message), $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($this->parse($message), $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($this->parse($message), $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($this->parse($message), $context);
    }

    private function parse(string $message): string
    {
        $message = $message . ' [' . $this->route . ']';

        return $message;
    }


    private function getApplicationName(): ?string
    {
        return config('app.name');
    }
}
