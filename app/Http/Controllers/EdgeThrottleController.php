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

use RZP\Models\FileStore\Storage\AwsS3\Handler;
use RZP\Models\FileStore\Type;

class EdgeThrottleController extends Controller
{
    /**
     * Rate limiter entities definition to use in workflows
     */
    CONST ENTITY_RATE_LIMITER_RULE_CREATE   = 'rate_limiter_rule_create';
    CONST ENTITY_RATE_LIMITER_RULE_UPDATE   = 'rate_limiter_rule_update';
    CONST ENTITY_RATE_LIMITER_RULE_DELETE   = 'rate_limiter_rule_delete';
    CONST ENTITY_RATE_LIMITER_LIMIT_CREATE  = 'rate_limiter_limit_create';
    CONST ENTITY_RATE_LIMITER_LIMIT_UPDATE  = 'rate_limiter_limit_update';
    CONST ENTITY_RATE_LIMITER_LIMIT_DELETE  = 'rate_limiter_limit_delete';
    CONST WAF_RULES_KEY                     = 'ddos-visibility/raw/raw_waf.csv';

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
    public function listServices()
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
    public function listRoutes(string $serviceId)
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
        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_CREATE);

        $path = $this->rulePathPrefix($input, true) . '/rate-limit-rules';

        unset($input['service_id']);
        unset($input['route_id']);
        unset($input['service_name']);
        unset($input['context']);

        $input['enabled'] = (empty($input['enabled']) === true) ? false : true;

        $response = $this->request('POST', $path, $input);

        return $this->finalizeResponse($response, [
            'service',
            'route',
            'id',
            'rule',
            'rule_component_preference',
            'enabled',
            'created_at',
            'updated_at',
            'priority',
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

        if (isset($request['route_id']) === false and isset($request['service_id']) === false)
        {
            $path = '/rate-limit-rules' . $this->constructQueryParam();
        }
        else
        {
            $path = $this->rulePathPrefix($request, true) . '/rate-limit-rules' . $this->constructQueryParam();
        }

        $response = $this->request($method, $path);

        return $this->finalizeResponse($response, [
            'id',
            'route',
            'service',
            'rule',
            'rule_component_preference',
            'enabled',
            'created_at',
            'updated_at',
            'priority'
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
        $input = Request::all();

        $path = $this->rulePathPrefix($input, true) . '/rate-limit-rules/' . $id;

        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_UPDATE, $id, $this->getRule($path));

        $body = [
           'enabled' => (empty($input['enabled']) === true) ? false : true,
        ];

        // add priority only of provided
        if (empty($input['priority']) === false)
        {
            $body['priority'] = $input['priority'];
        }

        $response = $this->request('PATCH', $path, $body);

        return $this->finalizeResponse($response, [
            'id',
            'route',
            'service',
            'rule',
            'enabled',
            'created_at',
            'updated_at',
            'priority'
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
        $request = Request::all();

        $path = $this->rulePathPrefix($request, true) . '/rate-limit-rules/' . $id;

        $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_RULE_DELETE, $id, $this->getRule($path));

        $response = $this->request('DELETE', $path);

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
        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_CREATE);

        $request = Request::instance();

        $method = $request->method();

        $path = '/rate-limits';

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

        unset($input['rule_id']);
        unset($input['rule_name']);
        unset($input['service_id']);
        unset($input['service_name']);
        unset($input['context']);

        $response = $this->request($method, $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'action',
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
            'action',
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
        $path = '/rate-limits/' . $id;

        $input = $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_UPDATE, $id, $this->getLimit($path));

        // for now we will not let key to be updated from admin dashboard
        unset($input['key']);
        unset($input['rule_id']);
        unset($input['rule_name']);
        unset($input['service_id']);
        unset($input['service_name']);
        unset($input['context']);

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

        $response = $this->request('PATCH', $path, $input);

        return $this->finalizeResponse($response, [
            'id',
            'rule',
            'key',
            'action',
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
        $path = '/rate-limits/' . $id;

        $this->routeViaWorkflow(self::ENTITY_RATE_LIMITER_LIMIT_DELETE, $id, $this->getLimit($path));

        $response = $this->request('DELETE', $path);

        return $this->finalizeResponse($response, []);
    }

    /**
     * list the consumer for the given id/username
     *
     * @throws BadRequestException
     * @throws NotFoundException|InvalidArgumentException
     */
    public function getConsumer($id)
    {
        $request = Request::instance();
        $method = $request->method();
        $path = '/consumers/' . $id;
        $response = $this->request($method, $path);
        return $this->finalizeResponse($response, [
            'id',
            'username'
        ]);
    }

    /**
     * construct the query param which has to be sent to edge
     *
     * @return string
     */
    protected function constructQueryParam(): string
    {
        $input = Request::all();
        $params = '';
        if (isset($input['size']) === true)
        {
            $params .= '?size=' . $input['size'];
            if (isset($input['offset']))
            {
                $params .= '&offset=' . urlencode($input['offset']);
            }
        }
        else if (isset($input['offset']))
        {
            $params .= '?offset=' . urlencode($input['offset']);
        }
        return $params;
    }

    /**
     * Used only for routes which operates on rate limit rules
     * It'll construct the route path based on the attributes of request body or query param
     *
     * @param $input
     * @param $serviceOperationAllowed
     * @return string
     * @throws BadRequestException
     */
    protected function rulePathPrefix($input, $serviceOperationAllowed): string
    {
        if (isset($input['route_id']) === true)
        {
            return '/routes/' . $input['route_id'];
        }
        else if (($serviceOperationAllowed === true) and (isset($input['service_id']) === true))
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
     * @throws BadRequestException
     */
    protected function finalizeResponse(ResponseInterface $response, array $keys, bool $isList = false)
    {
        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        if (empty($arrayResponse) === true)
        {
            return Response::make(null, $response->getStatusCode());
        }

        //
        // if the response status code is not 2xx then throw bad request exception
        //
        if (($response->getStatusCode() < 200) or ($response->getStatusCode() >= 300))
        {
            $this->trace->info(TraceCode::EDGE_RATE_LIMITER_ERROR, [
                'status' => $response->getStatusCode(),
                'body'   => $response->getBody(),
            ]);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY);
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

    /**
     * lists rate limit rules configured on a service/route.
     *
     * @param string $path
     * @return mixed
     */
    protected function getRule(string $path)
    {
        $response = $this->request('GET', $path);

        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        return $this->extractKeys($arrayResponse, [
            'id',
            'route',
            'service',
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
    protected function getLimit(string $path)
    {
        $response = $this->request('GET', $path);

        $arrayResponse = json_decode($response->getBody()->getContents(), true);

        return $this->extractKeys($arrayResponse, [
            'id',
            'rule',
            'key',
            'config',
        ]);
    }

    /**
     * Route the request via workflow.
     *
     * @param string $entity name of the entity which is being updated
     * @param string|null $id entity id. If not present then use subset of request id
     * @param array $originalValue
     * @return array
     */
    protected function routeViaWorkflow(string $entity, string $id = null, array $originalValue = [])
    {
        $input = Request::all();

        // If the request is triggered via workflow then let it perform the operation
        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $this->app['trace']->info(TraceCode::EDGE_CONTROLLER_WORKFLOW_REQUEST, [
                'input' => $input,
            ]);

            return $input;
        }

        //
        // ID is required to associate with entity while creating workflow.
        // So in our case when we do create request there won't any id so using request ID as reference
        //
        if ($id === null)
        {
            $id = $this->app['request']->getId();
        }

        //
        // At this point ID could be UUID as per rate limiter configs or request ID
        // in both the cases, id length is greater 14 characters.
        // Whereas entity id could be only 14 char hence taking first 14 chars
        //
        $id = substr($id,0,14);

        $this->app['trace']->info(TraceCode::EDGE_CONTROLLER_WORKFLOW_CREATE_REQUEST, [
            'input' => $input,
        ]);

        $this->app['workflow']
             ->setEntityAndId($entity, $id)
             ->handle(
                 ['data' => json_encode($originalValue, JSON_NUMERIC_CHECK)],
                 ['data' => json_encode($input, JSON_NUMERIC_CHECK)]);

        return $input;
    }

    /**
     * returns signed URL of WAF Rules csv file
     * @return array
     * @throws \Exception
     */
    public function getWAFRulesSignedURL()
    {
        $handler = new Handler();
        $env = $this->app['env'];
        $bucketConfig = $handler->getBucketConfig(Type::WAF_RULES_FILE, $env, null, false);
        $signedURL = $handler->getSignedUrl($bucketConfig, self::WAF_RULES_KEY);
        $data = [
            'signed_url' => $signedURL,
        ];
        return $data;
    }

}
