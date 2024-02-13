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
     * Creates a rate limit on a rule id
     *
     * @param $ruleId
     * @throws NotFoundException
     * @throws BadRequestException|InvalidArgumentException
     */
    public function createLimit($ruleId)
    {
        $request = Request::instance();
        $method = $request->method();
        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_CREATE);

        unset($input['context']);
        unset($input['rule_name']);
        unset($input['service_id']);
        unset($input['service_name']);

        $path = '/limit';

        $response = $this->fetchResponse($method, $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule_id',
            'key',
            'config',
            'action',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * update the limit for the given id with the data provided in request body
     *
     * @param $id
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function updateLimit($id)
    {
        $request = Request::instance();
        $method = $request->method();
        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_UPDATE, $id, $this->getRateLimit($id));

        $path = '/limit/' . $id;

        unset($input['rule_name']);
        unset($input['service_id']);
        unset($input['service_name']);
        unset($input['context']);


        $response = $this->fetchResponse('PATCH', $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule_id',
            'key',
            'config',
            'action',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * deletes the limit configured which is identified by the id provided
     *
     * @param $id
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function deleteLimit($id)
    {
        $request = Request::instance();
        $method = $request->method();
        $path = '/limit/' . $id;

        $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_DELETE, $id, $this->getRateLimit($id));

        $response = $this->fetchResponse('DELETE', $path);

        return $this->finalizeResponse($response, []);
    }


    /**
     * Creates a rate limit rule on a service / route level
     *
     * @throws NotFoundException
     * @throws BadRequestException|InvalidArgumentException
     */
    public function createRule()
    {
        $request = Request::instance();
        $method = $request->method();
        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_CREATE);
        unset($input['context']);

        $input['enabled'] = (empty($input['enabled']) === true) ? false : true;

        $path = '/rule';

        $response = $this->fetchResponse($method, $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'enabled',
            'priority',
            'rule_type_id',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * lists rate limit rules configured on a service/route.
     *
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function listRules()
    {
        $request = Request::instance();
        $method = $request->method();
        $path = '/rules' . '?'. http_build_query(Request::all());
        $response = $this->fetchResponse($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'enabled',
            'priority',
            'rule_type_id',
            'created_at',
            'updated_at',
        ], true);
    }

    /**
     * update the rule for the given id with the data provided in request body
     *
     * @param $id
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function updateRule($id)
    {
        $path = '/rule/' . $id;
        $request = Request::instance();
        $method = $request->method();

        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_UPDATE, $id, $this->getRateLimitRule($id));

        $response = $this->fetchResponse('PATCH', $path, $input);


        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'enabled',
            'priority',
            'rule_type_id',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * deletes the rule configured which is identified by the id provided
     *
     * @param $id
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function deleteRule($id)
    {
        $request = Request::instance();
        $method = $request->method();

        $path = '/rule/' . $id;
        $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_DELETE, $id, $this->getRateLimitRule($id));

        $response = $this->fetchResponse('DELETE', $path);

        return $this->finalizeResponse($response, []);
    }

    /**
     * lists rate limit rules configured on a service/route.
     *
     * @param string $path
     * @return mixed
     */
    protected function getRateLimitRule(string $id)
    {
        $path = '/rules?id='.$id;

        $response = $this->fetchResponse('GET', $path);

        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        return $this->extractKeys($arrayResponse["data"][0], [
            'id',
            'rule_type_id',
            'rule',
            'enabled',
        ]);
    }

    /**
     * lists rate limit rules configured on a service/route.
     *
     * @param string $path
     * @return mixed
     */
    protected function getRateLimit(string $id)
    {
        $path = '/limits?id='.$id;
        $response = $this->fetchResponse('GET', $path);


        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        return $this->extractKeys($arrayResponse["data"][0], [
            'id',
            'rule_id',
            'key',
            'config',
            'action'
        ]);
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
        string $path,
        array $body = null
    ): ResponseInterface
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $username = $this->rateLimiterConfig['username'];
        $password = $this->rateLimiterConfig['password'];
        $authorization = "Basic " . base64_encode($username.":".$password);
        $request = $requestFactory->createRequest($method, $this->rateLimiterConfig['host'] . $path)
                                  ->withHeader('Content-Type', 'application/json')
                                  ->withHeader('Authorization', $authorization)
                                  ->withHeader('X-Razorpay-Request-ID', $this->app->request->getTaskId());

        if ($body !== null)
        {
            $bodyStream = $streamFactory->createStream(json_encode($body, JSON_NUMERIC_CHECK));
            $request = $request->withBody($bodyStream);
        }


        $this->trace->info(TraceCode::RATE_LIMITER_SERVICE_REQUEST, [
            'method' => $method,
            'url'    => $this->rateLimiterConfig['host'] . $path,
            'body'   => $body,
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
