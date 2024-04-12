<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis as RRR;

class RedisHelper
{
    private $redisHelper;
    private $redisPrefix;

    public function __construct()
    {
        $this->redisHelper = RRR::connection();
        $this->redisPrefix = env('REDIS_PREFIX');
    }

    public function cacheResult($username, $result, $min, $timestamp = null)
    {
        $module = 'messagingError';
        $redisKey = $this->buildRedisKey($username, $module, $timestamp);
        $this->redisHelper->setex($redisKey, $min * 60, json_encode($result));
    }

    public function getCachedResult($username, $module)
    {
        $redisKey = $this->buildRedisKey($username, $module);
        return json_decode($this->redisHelper->get($redisKey));
    }

    public function getRetryMessages()
    {
        $keys = $this->redisHelper->keys($this->redisPrefix . '-*');
        $messages = [];
        foreach ($keys as $key => $value) {
            $value = substr($value, 10);
            $message = $this->redisHelper->get($value);
            $messages[$value] = json_decode($message, true);
        }
        return $messages;
    }

    public function deleteKey($username, $module, $timestamp = null)
    {
        $redisKey = $this->buildRedisKey($username, $module, $timestamp);
        $this->redisHelper->del($redisKey);
    }

    public function buildRedisKey($username, $module = 'messagingError', $timestamp = null)
    {
        return $this->redisPrefix . "-" . $username . "-" . $module . '-' . $timestamp;
    }
}
