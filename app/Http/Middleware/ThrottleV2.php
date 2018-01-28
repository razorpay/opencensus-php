<?php

namespace RZP\Http\Middleware;

use Jitendra\PhpValve\LeakyBucket;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Application;
use Illuminate\Http\{Request, JsonResponse as Response};

use ApiResponse;

final class ThrottleV2
{
    private $config;
    private $trace;
    private $redis;

    public function __construct(Application $app)
    {
        $this->config = $app['config']->get('throttle');
        $this->trace  = $app['trace'];
        $this->redis  = Redis::connection($this->config['driver'])->client();
    }

    public function handle($request, \Closure $next)
    {
        list($allowed, $limits) = $this->throttle($request);

        $response = $allowed ? $next($request) : $this->rateLimitExceededResponse();

        return $this->responseWithThrottleHeaders($response, $limits);
    }

    private function throttle(Request $request): array
    {
        if ($this->shouldSkipThrottle() === true)
        {
            return [1, []];
        }

        list($identifier, $settings) = $this->getIdentifierAndThrottleSettings($request);

        return $this->throttleWithRedisWithoutFail($identifier, $settings);
    }

    private function shouldSkipThrottle(): bool
    {
        return ($this->config['skip'] === true);
    }

    private function getIdentifierAndThrottleSettings(Request $request): array
    {
        return ['testing', [100, 1, 1000]];
    }

    private function throttleWithRedisWithoutFail(string $identifier, array $settings): array
    {
        $allowed = 1;
        $limits  = [];

        try
        {
            $limiter = new LeakyBucket\Redis($settings[0], $settings[1], $settings[2], $this->redis);

            $response = $limiter->attempt($identifier);
            $allowed  = array_shift($response);
            $limits   = $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        return [$allowed, $limits];
    }

    private function rateLimitExceededResponse(): Response
    {
        return ApiResponse::rateLimitExceeded();
    }

    private function responseWithThrottleHeaders(Response $response, array $limits): Response
    {
        $headers = $response->headers;
        $limits  += array_fill(0, 4, -1);

        $headers->set('X-RateLimit-Limit', $limits[0]);
        $headers->set('X-RateLimit-Remaining', $limits[1]);
        $headers->set('X-RateLimit-Reset', $limits[2]);
        $headers->set('X-RateLimit-RetryAfter', $limits[3]);

        return $response;
    }
}
