<?php

namespace RZP\Http\Controllers;

use Request;
use Response;

use Razorpay\Trace\Logger;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Exception\NotFoundException;

use function GuzzleHttp\json_encode;
use GuzzleHttp\Exception\InvalidArgumentException;

class EdgeThrottleController extends Controller
{
    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * contains host and api key which should be used to make request
     * @var array
     */
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->httpClient  = app('throttler_http_client');
        $this->config      = app('config')->get('services.throttler');
    }

    /**
     * lists all the services configured on edge
     *
     * @throws NotFoundException|InvalidArgumentException
     */
    public function getServices()
    {
        $request = Request::instance();
        $method = $request->method();
        $path = '/services' . $this->constructQueryParam();

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'name',
            'host',
        ], true);
    }

    /**
     * lists all the routes configured on edge for a given service
     *
     * @param string $serviceId
     * @return mixed
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function getRoutes(string $serviceId)
    {
        $request = Request::instance();

        $method = $request->method();
        $path = '/services/' . $serviceId . '/routes' . $this->constructQueryParam();

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'name',
            'methods',
            'paths',
            'hosts',
         ], true);
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

        $path = $this->rulePathPrefix() . '/rate-limit-rules';

        $input = Request::all();
        unset($input['service_id']);
        unset($input['route_id']);

        $input['enabled'] = (empty($input['enabled']) === true) ? false : true;

        $response = $this->request($method, $path, $input);

        return $this->finalizeResponse($response, [
            'service',
            'route',
            'id',
            'rule',
            'enabled',
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

        $path = $this->rulePathPrefix() . '/rate-limit-rules' . $this->constructQueryParam();

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'route',
            'service',
            'rule',
            'enabled',
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
        $request = Request::instance();

        $method = $request->method();

        $path = $this->rulePathPrefix() . '/rate-limit-rules/' . $id;

        $input = Request::all();

        $body = [
           'enabled' => (empty($input['enabled']) === true) ? false : true,
        ];

        $response = $this->request($method, $path, $body);

        return $this->finalizeResponse($response, [
            'id',
            'route',
            'service',
            'rule',
            'enabled',
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

        $path = $this->rulePathPrefix() . '/rate-limit-rules/' . $id;

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, []);
    }

    /**
     * create a new limit associated with rule id given
     *
     * @param $ruleId
     * @return mixed
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function createLimit($ruleId)
    {
        $request = Request::instance();

        $method = $request->method();

        $path = '/rate-limits';

        $input = Request::all();

        $input['rule'] = [
            'id' => $ruleId,
        ];

        // if key is set in the request then convert the value to bool
        if (array_key_exists('strictly_consistent', $input['config']) === true)
        {
            $input['config']['strictly_consistent'] = !(empty($input['config']['strictly_consistent']) === true);
        }

        // when key is sent and its empty, then set the value buffer bucket as null
        if ((array_key_exists('bucket', $input['config']) === true) and
            (array_key_exists('buffer', $input['config']['bucket']) === true) and
            (empty($input['config']['bucket']['buffer']) === true))
        {
            $input['config']['bucket']['buffer'] = null;
        }

        $response = $this->request($method, $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'key',
            'config',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * lists limits configured without any filter. (as edge dao does not support filtering yet)
     *
     * @throws NotFoundException|InvalidArgumentException
     */
    public function listLimits()
    {
        $request = Request::instance();

        $method = $request->method();

        $path = '/rate-limits' . $this->constructQueryParam();

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'key',
            'config',
            'created_at',
            'updated_at',
        ], true);
    }

    /**
     * update the limit for the given id with the data provided in request body
     *
     * @param $id
     * @return mixed
     * @throws NotFoundException|InvalidArgumentException
     */
    public function updateLimit($id)
    {
        $request = Request::instance();

        $method = $request->method();

        $path = '/rate-limits/' . $id;

        $input = Request::all();

        // for now we will not let key to be updated from admin dashboard
        unset($input['key']);

        // if key is set in the request then convert the value to bool
        if (array_key_exists('strictly_consistent', $input['config']) === true)
        {
            $input['config']['strictly_consistent'] = !(empty($input['config']['strictly_consistent']) === true);
        }

        // when key is sent and its empty, then set the value buffer bucket as null
        if ((array_key_exists('bucket', $input['config']) === true) and
            (array_key_exists('buffer', $input['config']['bucket']) === true) and
            (empty($input['config']['bucket']['buffer']) === true))
        {
            $input['config']['bucket']['buffer'] = null;
        }

        $response = $this->request($method, $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'key',
            'config',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * deletes the limit configured which is identified by the id provided
     *
     * @param $id
     * @return mixed
     * @throws NotFoundException|InvalidArgumentException
     */
    public function deleteLimit($id)
    {
        $request = Request::instance();

        $method = $request->method();

        $path = '/rate-limits/' . $id;

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, []);
    }

    /**
     * construct the query param which has to be sent to edge
     *
     * @return string
     */
    protected function constructQueryParam(): string
    {
        $input = Request::all();
        return isset($input['offset']) ? '?offset=' . $input['offset'] : '';
    }

    /**
     * Used only for routes which operates on rate limit rules
     * It'll construct the route path based on the attributes of request body or query param
     *
     * @return string
     * @throws BadRequestException
     */
    protected function rulePathPrefix(): string
    {
        $input = Request::all();

        if (isset($input['route_id']) === true)
        {
            return '/routes/' . $input['route_id'];
        }
        else if (isset($input['service_id']) === true)
        {
            return '/services/' . $input['service_id'];
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS);
    }

    /**
     * given the response collected from edge, format it based on the API contract defined
     *
     * @param ResponseInterface $response
     * @param array $keys
     * @param bool $isList
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function finalizeResponse(ResponseInterface $response, array $keys, bool $isList = false)
    {
        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        if (empty($arrayResponse) === true)
        {
            return Response::make(null, $response->getStatusCode());
        }

        //
        // if the response status code is not 2xx then return received response directly with status code
        //
        if (($response->getStatusCode() < 200) or ($response->getStatusCode() >= 300))
        {
            return Response::make((string) $response->getBody(), $response->getStatusCode());
        }

        $finalResult = [];
        if ($isList === true)
        {
            $finalResult = [
                'data'   => [],
                'offset' => $this->extractOffset($arrayResponse['next'] ?? '')
            ];

            $arrayData = $arrayResponse['data'] ?? [];
            foreach ($arrayData as $data)
            {
                $finalResult['data'][] = $this->extractKeys($data, $keys);
            }
        }
        else
        {
            $finalResult = $this->extractKeys($arrayResponse, $keys);
        }

        return Response::make(json_encode($finalResult), $response->getStatusCode());
    }

    /**
     * extract the offset index from the response used for pagination by edge
     *
     * @param string $offsetString
     * @return mixed|null
     */
    protected function extractOffset(string $offsetString)
    {
        if (preg_match('/.+\?offset=(.+)/', $offsetString, $matches) > 0)
        {
            return $matches[1];
        }

        return null;
    }

    /**
     * extract the <key, value> from the data for all the key defined in `$keys`
     *
     * @param array $data
     * @param array $keys
     * @return array
     */
    protected function extractKeys(array $data, array $keys) {
        $result = [];

        foreach ($keys as $key)
        {
            if (array_key_exists($key, $data) === true)
            {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    /**
     * makes rest call to edge rate limit service with given arguments
     *
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    protected function request(
        string $method,
        string $path,
        array $body = null): ResponseInterface
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $request = $requestFactory->createRequest($method, $this->config['host'] . $path)
                                  ->withHeader('Content-Type', 'application/json')
                                  ->withHeader('apikey', $this->config['apikey'])
                                  ->withHeader('X-Razorpay-Request-ID', $this->app->request->getTaskId());

        if ($body !== null)
        {
            $bodyStream = $streamFactory->createStream(json_encode($body, JSON_NUMERIC_CHECK));
            $request = $request->withBody($bodyStream);
        }

        $this->trace->info(TraceCode::EDGE_RATE_LIMITER_REQUEST, [
            'method' => $method,
            'url'    => $this->config['host'] . $path,
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
                TraceCode::EDGE_RATE_LIMITER_ERROR,
                [
                    'method' => $method,
                    'url'    => $this->config['host'] . $path,
                    'body'   => $body,
                ]);

            throw $e;
        }

        return $response;
    }
}
