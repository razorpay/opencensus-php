<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use RZP\Error\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use RZP\Http\RequestHeader;

/**
 * Class IdempotentHandler
 *
 *  Handles an incoming request with Header X-Idempotent-Key to serve idempotent/identical/same
 *  response. The X-Idempotent-Key value is stored in Redis for ttl of 7200 seconds.
 * @package RZP\Http\Middleware
 */
class IdempotentHandler
{
    protected $app;

    protected $router;

    protected $trace;

    protected $config;

    /**
     * @var RedisManager
     */
    protected $redis;

    protected $mutex;

    /**
     * Lock wait timeout in seconds.
     */
    const MUTEX_LOCK_TTL = 10;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->config = $app['config']->get('database');

        $this->router = $this->app['router'];

        $this->trace = $this->app['trace'];

        $this->mutex = $this->app['api.mutex'];

        $this->setConnection();
    }

    public function setConnection()
    {
        $this->redis = $this->app['redis']->connection();
    }

    public function handle(Request $request, Closure $next)
    {
        // Only internal Apps/services can pass X-Idempotent-Key
        if ($this->app['basicauth']->getInternalApp() === null)
        {
            return $next($request);
        }

        $idempotentId = $request->headers->get(RequestHeader::X_IDEMPOTENT_KEY);

        if ($idempotentId === null)
        {
            return $next($request);
        }

        $redisKey = $this->getKey($request, $idempotentId);

        //
        // Mutex Lock TTL is 10 seconds. If new requests comes
        // in between total 10 retry will be done between
        // 200 to 800 milliseconds of delay.
        //
        $retryCount = 10;
        $minRetryDelay = 200;
        $maxRetryDelay = 800;

        $lockResponse = $this->mutex->acquireAndRelease(
            $redisKey,
            function() use ($redisKey, $request, $next) {
                $redisValueForRequest = $this->redis->get($redisKey);

                if ($redisValueForRequest !== null)
                {
                    $redisValueForRequest = unserialize($redisValueForRequest);

                    return $redisValueForRequest;
                }

                $response = $next($request);

                //
                // Check and confirm with examples that it is safe to cache all responses,
                // errors and exceptions. As of now just storing 2xx success responses.
                //
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)
                {
                    // setting ttl to 2 hours (7200 seconds)
                    $ttl =  7200;

                    $this->redis->set($redisKey, serialize($response), 'ex', $ttl, 'nx');
                }

                return $response;

            },
            static::MUTEX_LOCK_TTL,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            $retryCount,
            $minRetryDelay,
            $maxRetryDelay
        );

        return $lockResponse;
    }

    protected function getKey(Request $request, string $idempotentValue): string
    {
        $method = $request->getMethod();

        $routeName = $this->router->currentRouteName();

        return implode(":", array($method, $routeName, $idempotentValue));
    }
}
