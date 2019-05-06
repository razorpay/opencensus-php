<?php

namespace RZP\Services;

use Requests_Session;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class GovernorService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_REQUEST_ID             = 'X-Request-ID';
    const APPLICATION_JSON         = 'application/json';

    const REQUEST_TIMEOUT = 40;
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const ERROR     = 'error';

    const CREATE_NAMESPACE  =   [
        'url'       =>  "rule_engine/namespace",
        'method'    =>  "POST",
    ];

    const DOMAIN_MODEL_LIST  =   [
        'url'       =>  "rule_engine/data_model/:namespace",
        'method'    =>  "GET",
    ];

    const CREATE_DOMAIN_MODEL  =   [
        'url'       =>  "rule_engine/data_model/:namespace",
        'method'    =>  "POST",
    ];

    const UPDATE_DOMAIN_MODEL  =   [
        'url'       =>  "rule_engine/data_model/:namespace",
        'method'    =>  "PUT",
    ];

    const CREATE_RULE  =   [
        'url'       =>  "rule_engine/rule/:namespace",
        'method'    =>  "POST",
    ];

    const CREATE_RULES  =   [
        'url'       =>  "rule_engine/rule/:namespace/bulk",
        'method'    =>  "POST",
    ];

    const UPDATE_RULE  =   [
        'url'       =>  "rule_engine/rule/:namespace",
        'method'    =>  "PUT",
    ];

    const RULE_LIST  =   [
        'url'       =>  "rule_engine/rule/:namespace",
        'method'    =>  "GET",
    ];


    const CREATE_RULE_CHAIN  =   [
        'url'       =>  "rule_engine/rule_chain/:namespace",
        'method'    =>  "POST",
    ];


    const UPDATE_RULE_CHAIN  =   [
        'url'       =>  "rule_engine/rule_chain/:namespace",
        'method'    =>  "PUT",
    ];


    const RULE_CHAIN_LIST  =   [
        'url'       =>  "rule_engine/rule_chain/:namespace",
        'method'    =>  "GET",
    ];


    const EXECUTE_CHAINS  =   [
        'url'       =>  "rule_engine/execute/rule_chain/:namespace",
        'method'    =>  "POST",
    ];

    protected $config;

    protected $trace;

    protected $request;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.governor');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->getBaseUrl();

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    protected function sendRequest(string $method, string $url, array $auth, array $data = [])
    {
        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
                self::X_REQUEST_ID             => $this->app['request']->getId(),
            ],
            'options' => [
                'auth' => $auth,
            ]
        ];

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_REQUEST, $request);

        $response = $this->sendRawRequest($request);

        $parsedResponse = $this->processResponse($response);

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_RESPONSE, $parsedResponse['response_body'] ?? []);

        return $parsedResponse;
    }

    protected function sendRawRequest($request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = $this->request->request(
                    $request['url'],
                    $request['headers'],
                    json_encode($request['content']),
                    $request['method'],
                    $request['options']);

                break;
            }
            catch(\Requests_Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::GOVERNOR_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;

                    continue;
                }

                $this->throwServiceErrorException($e);
            }
        }

        return $response;
    }

    protected function getBaseUrl(): string
    {
        $baseUrl = $this->config['url'];

        return $baseUrl;
    }

    protected function getUrl($requestArray, $namespace = ''): string
    {
        $baseUrl = $this->getBaseUrl();

        $url = $baseUrl . str_replace_first(':namespace', $namespace, $requestArray['url']);

        return $url;
    }

    protected function getMethod($requestArray): string
    {
        return $requestArray['method'];
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        return $options;
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
            self::X_RAZORPAY_APP_HEADER    => 'api',
        ];

        return $headers;
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_GOVERNOR_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_GOVERNOR_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }


    protected function jsonToArray($json)
    {
        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::GOVERNOR_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function processResponse($response)
    {
        return [
            'response_body' => $this->jsonToArray($response->body),
            'response_code' => $response->status_code,
        ];
    }

    public function getAuthDetails(string $source) {
        switch ($source) {
            case 'cps':
                return [
                    $this->config['cps']['username'],
                    $this->config['cps']['password']
                ];
            case 'smart_routing':
                return [
                    $this->config['smart_routing']['username'],
                    $this->config['smart_routing']['password']
                ];
            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    public function createNamespace(array $input, $source)
    {
        $url = $this->getUrl(self::CREATE_NAMESPACE);

        $method = $this->getMethod(self::CREATE_NAMESPACE);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function getDomainModels(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::DOMAIN_MODEL_LIST, $namespace);

        $method = $this->getMethod(self::DOMAIN_MODEL_LIST);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function createDomainModel(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::CREATE_DOMAIN_MODEL, $namespace);

        $method = $this->getMethod(self::CREATE_DOMAIN_MODEL);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function updateDomainModel(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::UPDATE_DOMAIN_MODEL, $namespace);

        $method = $this->getMethod(self::UPDATE_DOMAIN_MODEL);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function createRule(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::CREATE_RULE, $namespace);

        $method = $this->getMethod(self::CREATE_RULE);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function createRules(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::CREATE_RULES, $namespace);

        $method = $this->getMethod(self::CREATE_RULES);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function updateRule(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::UPDATE_RULE, $namespace);

        $method = $this->getMethod(self::UPDATE_RULE);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function getRules(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::RULE_LIST, $namespace);

        $method = $this->getMethod(self::RULE_LIST);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function createRuleChain(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::CREATE_RULE_CHAIN, $namespace);

        $method = $this->getMethod(self::CREATE_RULE_CHAIN);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }


    public function updateRuleChain(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::UPDATE_RULE_CHAIN, $namespace);

        $method = $this->getMethod(self::UPDATE_RULE_CHAIN);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function getRuleChains(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::RULE_CHAIN_LIST, $namespace);

        $method = $this->getMethod(self::RULE_CHAIN_LIST);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }

    public function executeChains(array $input, string $source, string $namespace)
    {
        $url = $this->getUrl(self::EXECUTE_CHAINS, $namespace);

        $method = $this->getMethod(self::EXECUTE_CHAINS);

        $auth = $this->getAuthDetails($source);

        $response = $this->sendRequest($method, $url, $auth, $input);

        return $response;
    }
}
