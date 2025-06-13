<?php

namespace RZP\Services\PayoutService;

use GuzzleHttp\Client;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class PayoutShadowService
{
    /**
     * Check if request mirroring should be enabled for this request
     *
     * @return bool
     */
    public static function shouldMirrorRequest($merchantID, $killSwitch): bool
    {
        $trace = app('trace');

        if (!$killSwitch) {
            return false;
        }

        // Check split experiment
        return self::isSplitzExperimentEnable($merchantID);
    }

    /**
     * Mirror the request to the shadow service
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @param array $body Request body
     * @param array $headers Request headers to forward
     * @return void
     */
    public static function mirrorRequest(string $method, string $path, array $body, array $headers): void
    {
        $app = \App::getFacadeRoot();
        $config = $app['config']->get('applications.payouts_shadow_router');

        $requestId = app('request')->getTaskId();
        $startTime = microtime(true);
        $trace = app('trace');
        $url = null;

        $merchantId = $body['merchant_id'];

        $redis = app('redis')->Connection('mutex_redis');

        try {
            if (self::shouldMirrorRequest($merchantId, $config['kill_switch']) === false) {
                return;
            }

            $awsTraceId = (new PayoutShadowService)->getAwsTraceId();

            // Store the AWS trace ID in cache with the request ID as the key
            $cacheKey = 'payout_shadow_request:' . $requestId;

            $redis->set($cacheKey, $awsTraceId, 'EX', 60 * 10); // Store for 5 minutes

            // Log request start with caller information
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]) ? ($backtrace[1]['class'] ?? '') . '::' . ($backtrace[1]['function'] ?? '') : 'unknown';

            $trace->info(
                TraceCode::SHADOW_REQUEST_MIRROR_DEBUG,
                [
                    'request_id' => $requestId,
                    'aws_trace_id' => $awsTraceId,
                    'caller' => $caller,
                    'method' => $method,
                    'path' => $path,
                    'body_hash' => md5(json_encode($body)),
                    'start_time' => $startTime,
                ]
            );

            // Snapshot the request information immediately to avoid modifications by other middleware
            $requestMethod = $method;
            $requestContent = $body;
            $requestHeaders = self::getHeadersToForward($headers);

            // Add tracking header to identify mirrored requests
            $requestHeaders['X-Mirrored-Request'] = 'true';
            $requestHeaders['X-Request-ID'] = $requestId;
            $requestHeaders['X-Amazon-Trace-Id'] = $awsTraceId;

            $url = rtrim($config['payouts_shadow_router_url'], '/') . '/' . ltrim($path, '/');

            if ($config['debug']) {
                $trace->info(
                    TraceCode::SHADOW_REQUEST_MIRROR_ATTEMPT,
                    [
                        'request_id' => $requestId,
                        'url' => $url,
                        'method' => $requestMethod,
                        'headers' => $requestHeaders,
                        'body' => $requestContent,
                    ]
                );
            }

            // Create client for each request with original timeouts
            $client = new Client([
                                     'timeout' => $config['timeout'] ?? 0.02,
                                     'connect_timeout' => $config['connect_timeout'] ?? 0.02,
                                 ]);

            // Send request synchronously with timeout
            $response = $client->request($requestMethod, $url, [
                'json' => $requestContent,
                'headers' => $requestHeaders,
            ]);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // in milliseconds

            // Log success
            $trace->info(
                TraceCode::SHADOW_REQUEST_MIRROR_SUCCESS,
                [
                    'request_id' => $requestId,
                    'url' => $url,
                    'status' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'caller' => $caller,
                ]
            );

            // Only log response body in debug mode
            if ($config['debug']) {
                $trace->info(
                    TraceCode::SHADOW_REQUEST_MIRROR_SUCCESS,
                    [
                        'request_id' => $requestId,
                        'response' => $response->getBody()->getContents(),
                    ]
                );
            }
        }
        catch (\Throwable $e) {
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // in milliseconds

            $trace->traceException($e, Trace::ERROR, TraceCode::SHADOW_REQUEST_MIRROR_FAILURE, [
                'request_id' => $requestId,
                'url' => $url ?? 'unknown_url',
                'exception' => $e->getMessage(),
                'duration_ms' => $duration,
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Filter and format headers for forwarding to the shadow service
     *
     * @param array $headers
     * @return array
     */
    protected static function getHeadersToForward(array $headers): array
    {
        // List of headers to not forward
        $excludedHeaders = [
            'authorization',
            'cookie',
            'x-razorpay-auth',
            'x-razorpay-trace-id',
            'host', // Exclude host header to prevent cyclic requests
        ];

        $formattedHeaders = [];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $excludedHeaders)) {
                continue;
            }

            $formattedHeaders[$key] = is_array($value) ? implode(', ', $value) : $value;
        }

        return $formattedHeaders;
    }

    /**
     * Get configuration from config file
     *
     * @return array
     */
    protected static function getConfig(): array
    {

        $app = \App::getFacadeRoot();

        $config = $app['config']->get('applications.payouts_shadow_router');

        return config([
                          'debug' => false,
                          'shadow_service_url' => $config['payouts_shadow_router_url'],
                          'timeout' => $config['timeout'] ?? 0.1, // Default to 100ms
                          'connect_timeout' =>  $config['connect_timeout'] ?? 0.1, // Default to 100ms
                          'kill_switch' => $config['kill_switch'] ?? false
                      ]);
    }

    protected static function isSplitzExperimentEnable($merchantId): bool
    {
        $app = \App::getFacadeRoot();

        try
        {
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get('app.payouts_shadow_router_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];

            $app = \App::getFacadeRoot();
            $response   = $app['splitzService']->evaluateRequest($properties);
            $variant = $response['response']['variant']['name'] ?? '';

            if (strtolower($variant) === 'enable'){
                return true;
            }
        }
        catch (\Throwable $e)
        {
            $trace = app('trace');
            $trace->warning(
                TraceCode::SHADOW_REQUEST_MIRROR_EXPERIMENT_EXCEPTION,
                [
                    'exception' => $e->getMessage(),
                ]
            );
        }

        return false;
    }

    public static function getAwsTraceId(): string
    {
        try {
            $app = \App::getFacadeRoot();
            return $app['request']->headers?->get(RequestHeader::X_AMAZON_TRACE_ID) ?? 'Root=1-' . $app['request']->getTaskId();

        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}

