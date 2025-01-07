<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis as RRR;

class RedisHelper
{
    private $redisConnection;

    public function __construct()
    {
        $this->redisConnection = RRR::connection();
    }

    public function cacheResult($username, $result, $min, $timestamp = null)
    {
        $module = 'messagingError';
        $redisKey = $this->buildRedisKey($username, $module, $timestamp);
        $this->redisConnection->setex($redisKey, $min * 60, json_encode($result));
    }

    public function getCachedResult($username, $module)
    {
        $redisKey = $this->buildRedisKey($username, $module);
        return json_decode($this->redisConnection->get($redisKey));
    }

    public function getKeys()
    {
        $searchKeyPhrase =  '-messagingError-*';
        $keys = $this->redisConnection->keys($searchKeyPhrase);

        return $keys;
    }

    public function getMessage($key)
    {
        $key = substr($key, 13);
        $message = $this->redisConnection->get($key);
        return $message;
    }

    public function deleteKey($username, $module, $timestamp = null)
    {
        $redisKey = $this->buildRedisKey($username, $module, $timestamp);
        $this->redisConnection->del($redisKey);
    }

    public function buildRedisKey($username, $module = 'messagingError', $timestamp = null)
    {
        return "-" . $module . "-" . $username . '-' . $timestamp;
    }

    public function setKey($key, $value)
    {
        return $this->redisConnection->set($key, $value);
    }

    public function getKey($key)
    {
        return $this->redisConnection->get($key);
    }
}
