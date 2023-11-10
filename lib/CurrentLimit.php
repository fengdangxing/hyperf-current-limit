<?php

namespace Fengdangxing\CurrentLimit;

use App\Constants\CommonCode;
use Hyperf\Utils\Codec\Json;
use Fengdangxing\HyperfRedis\RedisHelper;

class CurrentLimit
{
    public $currentLimit = 'current-limit';
    public $redisRateLimit = 'rate-limit';
    public $redisRateLimitPause = 'rate-limit:pause';
    public $redisRateLimitForbid = 'rate-limit:forbid';

    //限流时间间隔内下限次数: 默认20
    public $rateLimitMin = 50;

    //限流时间间隔内上限次数: 默认100
    public $rateLimitMax = 100;

    //限流时间间隔
    public $rateLimitPeriod = 5;

    //限流暂停时间
    public $rateLimitPausePeriod = 5;

    //封禁时间
    public $rateLimitForbidPeriod = 2 * 3600;

    public function __invoke()
    {
        $this->rateLimitMin = config('app.fengdangxing.rateLimitMin') ?: $this->rateLimitMin;
        $this->rateLimitMax = config('app.fengdangxing.rateLimitMax') ?: $this->rateLimitMax;
        $this->rateLimitPeriod = config('app.fengdangxing.rateLimitPeriod') ?: $this->rateLimitPeriod;
        $this->rateLimitPausePeriod = config('app.fengdangxing.rateLimitPausePeriod') ?: $this->rateLimitPausePeriod;
        $this->rateLimitForbidPeriod = config('app.fengdangxing.rateLimitForbidPeriod') ?: $this->rateLimitForbidPeriod;
    }


    /**
     * 并发处理
     */
    public function isConcurrentRequests(array $params, string $url, $userId)
    {
        [$key, $hashKey, $v] = $this->getRedisKey($params, $url, $userId);
        $ret = RedisHelper::init()->hSet($key, $hashKey, $v);
        if (!$ret) {
            throw new \Exception("Concurrent limit", 11009);
        }
        return RedisHelper::init()->expire($key, 10);
    }

    /**
     *并发处理
     */
    public function delConcurrentRequests(array $params, string $url, $userId)
    {
        [$key, $hashKey, $v] = $this->getRedisKey($params, $url, $userId);
        return RedisHelper::init()->del($key);
    }

    /**
     * 限流
     */
    public function isActionAllowed($userId, $url)
    {
        $redis = RedisHelper::init();
        $hkey = md5($userId . $url);
        $limitKey = sprintf("%s:%s", $this->redisRateLimit, $hkey);
        $forbidKey = sprintf("%s:%s", $this->redisRateLimitForbid, $hkey);
        $pauseKey = sprintf("%s:%s", $this->redisRateLimitPause, $hkey);

        if ($redis->exists($forbidKey)) {
            throw new \Exception("limit request", 11008);
        }

        if ($redis->exists($pauseKey)) {
            throw new \Exception("limit request", 11008);
        }

        $this->rateLimit($url, $limitKey, $forbidKey, $pauseKey);
    }

    /**
     * 限流
     */
    private function rateLimit($url, $limitKey, $forbidKey, $pauseKey)
    {
        $redis = RedisHelper::init();

        $count = $redis->incr($limitKey);
        if ($count == 1) {
            $redis->expire($limitKey, $this->rateLimitPeriod);

            return true;
        }

        if ($redis->ttl($limitKey)) {
            if ($count >= $this->rateLimitMin && $count <= $this->rateLimitMax) {
                $redis->set($pauseKey, $url, $this->rateLimitPausePeriod);

                return false;
            }

            if ($count > $this->rateLimitMax) {
                $redis->set($forbidKey, $url, $this->rateLimitForbidPeriod);

                return false;
            }
        }

        return true;
    }

    /**
     * 获取定义key
     */
    private function getRedisKey(array $params, $url, $userId)
    {
        $hashKey = md5(Json::encode($params) . $url . $userId);
        $key = sprintf("%s:%s", $this->currentLimit, $hashKey);
        $v = sprintf("%s?%s", $url, Json::encode($params));
        return [$key, $hashKey, $v];
    }
}
