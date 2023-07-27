<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Trace\TraceCode;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Razorpay\Trace\Logger;
use RZP\Exception\BadRequestException;
use Http\Discovery\Exception\NotFoundException;
use GuzzleHttp\Exception\InvalidArgumentException;

class RateLimiterController extends EdgeThrottleController
{
     /**
     * contains host and api key of rate limiter service
     * @var array
     */
    protected $rateLimiterConfig;

    const LIMITS_ENDPOINT = "/limits";
    
    public function __construct() {
        parent::__construct();
        $this->rateLimiterConfig = app('config')->get('services.rate_limiter_service');
    }

    /**
     * lists rate limits configured on a service/route.
     *
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function listLimits()
    {        
        $request = Request::instance();
        $method = $request->method();
        $path = self::LIMITS_ENDPOINT . '?'. http_build_query(Request::all());
        $response = $this->fetchResponse($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'rule_id',
            'action',
            'key',
            'config',
            'created_at',
            'updated_at',
        ], true);
    }

    /**
     * makes rest call to rate limit service with given arguments
     * @param string $method
     * @param string $path
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    protected function fetchResponse(
        string $method,
        string $path
    ): ResponseInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $username = $this->rateLimiterConfig['username'];
        $password = $this->rateLimiterConfig['password'];
        $authorization = "Basic " . base64_encode($username.":".$password);
        $request = $requestFactory->createRequest($method, $this->rateLimiterConfig['host'] . $path)
                                  ->withHeader('Content-Type', 'application/json')
                                  ->withHeader('Authorization', $authorization)
                                  ->withHeader('X-Razorpay-Request-ID', $this->app->request->getTaskId());

        $this->trace->info(TraceCode::RATE_LIMITER_SERVICE_REQUEST, [
            'method' => $method,
            'url'    => $this->rateLimiterConfig['host'] . $path
        ]);

        try
        {
            $response = $this->httpClient->sendRequest($request);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e,
                Logger::ERROR,
                TraceCode::RATE_LIMITER_SERVICE_ERROR,
                [
                    'method' => $method,
                    'url'    => $this->rateLimiterConfig['host'] . $path
                ]);

            throw $e;
        }

        return $response;
    }
}
