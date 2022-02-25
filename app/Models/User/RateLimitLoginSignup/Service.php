<?php

namespace RZP\Models\User\RateLimitLoginSignup;

use Throwable;
use RZP\Exception;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Constants;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

class Service
{
    /**
     * Manage rate limiting, account locking and suspension using redis
     * This service is specially used in OTP based Login and Signup.
     */

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * @var Trace
     */
    protected $trace;

    public function __construct($app)
    {
        $this->app = $app;
        $this->trace = $this->app['trace'];
    }

    /**
     * @param string $key
     * @param string $suffix
     * @param int $ttl
     * @param string $traceCode
     * @param string $errorCode
     * @param string $errorDescription
     * @return int
     * @throws Exception\ServerErrorException
     */
    public function incrementAndGetKeyCount(
        string $key, string $suffix, int $ttl,
        string $traceCode=TraceCode::REDIS_SESSION_STORE_ERROR,
        string $errorCode=ErrorCode::SERVER_ERROR,
        string $errorDescription="Redis Server Error"): int
    {
        try
        {
            $fullKey = $key . $suffix;
            $redis = $this->app->redis->Connection('mutex_redis');
            $index = $redis->incr($fullKey);

            if ($index === 1 && $ttl >= 0)
            {
                $redis->expire($fullKey, $ttl);
            }

            return $index;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                $traceCode,
                ['key' => $fullKey]
            );

            throw new Exception\ServerErrorException(
                $errorDescription,
                $errorCode
            );
        }
    }

    /**
     * @param string $key
     * @param string $suffix
     * @param int $ttl
     * @param int $threshold
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function validateKeyLimitExceeded(string $key, string $suffix, int $ttl, int $threshold)
    {
        $map_key = array_key_exists($suffix, Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP) ? $suffix : "default";
        $count = $this->incrementAndGetKeyCount(
            $key, $suffix, $ttl,
            Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisTraceCode"],
            Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisErrorCode"],
            Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisErrorDescription"]
        );

        if ($count > $threshold)
        {
            $this->trace->info(
                Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["thresholdTraceCode"], ['key'=>$key]
            );

            throw new Exception\BadRequestException(
                Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["thresholdErrorCode"],
                null,
                [
                    "internal_error_code" => Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["thresholdErrorCode"]
                ]
            );
        }


    }

    /**
     * @param string $key
     * @param string $suffix
     * @throws Exception\ServerErrorException
     */
    public function resetKey(string $key, string $suffix)
    {
        $map_key = array_key_exists($suffix, Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP) ? $suffix : "default";

        try
        {
            $fullKey = $key . $suffix;
            $redis = $this->app->redis->Connection('mutex_redis');
            $redis->del($fullKey);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisTraceCode"],
                ['key' => $fullKey]
            );

            throw new Exception\ServerErrorException(
                Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisErrorDescription"],
                Constants::RATE_LIMIT_LOGIN_SIGNUP_MAP[$map_key]["redisErrorCode"]
            );
        }
    }
}
