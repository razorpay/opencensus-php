<?php

namespace RZP\Services;

use Requests_Session;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class GovernorService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
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

    const UPDATE_RULES  =   [
        'url'       =>  "rule_engine/rule/:namespace/bulk",
        'method'    =>  "PUT",
    ];

    const RULE_LIST  =   [
        'url'       =>  "rule_engine/rule/:namespace",
        'method'    =>  "GET",
    ];

    const GET_RULE   =   [
        'url'       =>  "rule_engine/rule/:namespace/:entity_identifier",
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

    // For new proxy APIs

    const GET_CLIENTS_V1  =   [
        'url'       =>  "clients",
        'method'    =>  "GET",
    ];

    const CREATE_NAMESPACE_V1  =   [
        'url'       =>  "clients/:client_id/namespaces",
        'method'    =>  "POST",
    ];

    const LIST_NAMESPACES_V1  =   [
        'url'       =>  "clients/:client_id/namespaces",
        'method'    =>  "GET",
    ];

    const GET_NAMESPACE_V1  =   [
        'url'       =>  "namespaces/:namespace_id",
        'method'    =>  "GET",
    ];

    const UPDATE_NAMESPACE_V1  =   [
        'url'       =>  "clients/:client_id/namespaces/:namespace_id",
        'method'    =>  "PUT",
    ];

    const DELETE_NAMESPACE_V1  =   [
        'url'       =>  "client/:client/namespaces/:namespace_id",
        'method'    =>  "DELETE",
    ];
    const LIST_RULE_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id/rules",
        'method'    =>  "GET",
    ];

    const GET_RULE_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id/rules/:rule_id",
        'method'    =>  "GET",
    ];

    const DELETE_RULE_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id/rules/:rule_id",
        'method'    =>  "DELETE",
    ];

    const LIST_RULE_CHAIN_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains",
        'method'    =>  "GET",
    ];

    const LIST_RULE_GROUPS_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups",
        'method'    =>  "GET",
    ];

    const CREATE_RULE_GROUP_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups",
        'method'    =>  "POST",
    ];

    const GET_RULE_GROUP_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id",
        'method'    =>  "GET",
    ];

    const DELETE_RULE_GROUP_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id",
        'method'    =>  "DELETE",
    ];

    const UPDATE_RULE_GROUP_V1 =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id",
        'method'    =>  "PUT",
    ];

    const CREATE_RULE_V1  =   [
        'url'       =>  "namespaces/:namespace_id/rule_chains/:rule_chain_id/rule_groups/:rule_group_id/rules",
        'method'    =>  "POST",
    ];

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

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

    public function sendRequest(array $requestSchema, $data, $source, $namespace = null, $getEntityIdentifier = null, array $queryParams = [], $client_id = null, $namespace_id = null, $client = null, $rule_chain_id = null, $rule_group_id = null, $rule_id = null)
    {
        $url = $this->getUrl($requestSchema, $namespace, $getEntityIdentifier, $client_id, $namespace_id, $client, $rule_chain_id, $rule_group_id, $rule_id);

        $method = $this->getMethod($requestSchema);

        $auth = $this->getAuthDetails($source);

        if (empty($queryParams) === false)
        {
            $url = $url . '?';

            foreach ($queryParams as $key => $value)
            {
                $url .= $key . '=' . $value . '&';
            }
        }

        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            ]
        ];

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_REQUEST, $request);

        $request['options'] = [
            'auth' => $auth
        ];

        $response = $this->sendRawRequest($request);

        $parsedResponse = $this->processResponse($response);

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_RESPONSE, $parsedResponse['response_body'] ?? []);

        return $parsedResponse;
    }

    public function sendRequestV1(array $requestSchema, $data, $client_id = null, $namespace_id = null, $client = null, $rule_chain_id = null, $rule_group_id = null, $rule_id = null)
    {
        $url = $this->getUrlV1($requestSchema, $client_id, $namespace_id, $client, $rule_chain_id, $rule_group_id, $rule_id);

        $auth = $this->getAuthDetailsV1();

        $method = $this->getMethod($requestSchema);

        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            ]
        ];

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_REQUEST, $request);

        $request['options'] = [
            'auth' => $auth
        ];

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
                switch($request['method']) {
                    case Requests::POST:
                    case Requests::PUT:
                        $response = $this->request->request(
                            $request['url'],
                            $request['headers'],
                            json_encode($request['content']),
                            $request['method'],
                            $request['options']);
                            break;
                    default:
                        $response = $this->request->request(
                            $request['url'],
                            $request['headers'],
                            null,
                            $request['method'],
                            $request['options']);
                }

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

    protected function getUrl($requestArray, $namespace = '', $getEntityIdentifier = ''): string
    {
        $baseUrl = $this->getBaseUrl();

        $url = $baseUrl . str_replace_first(':namespace', $namespace, $requestArray['url']);

        $url = str_replace_first(':entity_identifier', $getEntityIdentifier, $url);

        return $url;
    }

    protected function getUrlV1($requestArray, $client_id = '', $namespace_id = '', $client = '', $rule_chain_id = '', $rule_group_id = '', $rule_id = ''): string
    {
        $baseUrl = $this->getBaseUrl();

        $url = $baseUrl . str_replace_first(':namespace_id', $namespace_id, $requestArray['url']);

        $url = str_replace_first(':client_id', $client_id, $url);

        $url = str_replace_first(':client', $client, $url);

        $url = str_replace_first(':rule_chain_id', $rule_chain_id, $url);

        $url = str_replace_first(':rule_group_id', $rule_group_id, $url);

        $url = str_replace_first(':rule_id', $rule_id, $url);

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

    public function getAuthDetailsV1() {

        return [
            $this->config['adminapi']['username'],
            $this->config['adminapi']['password']
        ];

    }
}
