<?php

namespace RZP\Services;

use App;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use \WpOrg\Requests\Session as Requests_Session;

use RZP\Http\Request\Requests;
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

    const REQUEST_TIMEOUT = 90;
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const ERROR     = 'error';
    const ERROR_MESSAGE = 'error_message';
    const ERROR_CODE = 'error_code';

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

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    protected $config;

    protected $trace;

    protected $request;

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = App::getFacadeRoot();
        }

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

    public function sendRequest(array $requestSchema, $data, $source, $namespace = null, $getEntityIdentifier = null, array $queryParams = [])
    {
        $url = $this->getUrl($requestSchema, $namespace, $getEntityIdentifier);

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
            catch(\WpOrg\Requests\Exception $e)
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
        if (empty($json) === true)
        {
            return [];
        }

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

    protected function processResponseV1($response)
    {
        $responseBody = $this->jsonToArray($response->body);

        if ( $response->status_code != 200 )
        {
            if ((isset($responseBody[self::ERROR]) === true) &&
                (isset($responseBody[self::ERROR][self::ERROR_CODE])  === true) &&
                ($responseBody[self::ERROR][self::ERROR_CODE] === ErrorCode::BAD_REQUEST_ERROR))
            {
                $this->trace->error(
                    TraceCode::GOVERNOR_SERVICE_BAD_REQUEST_ERROR,
                    ['response' => $response]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_GOVERNOR,
                    null,
                    [],
                    $responseBody[self::ERROR][self::ERROR_MESSAGE]
                );
            }

            $this->trace->error(
                TraceCode::GOVERNOR_SERVICE_ERROR,
                ['response' => $response]);

            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR
            );
        }

        return $responseBody;
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

    public function getAdminAuthDetails() {

        return [
            $this->config['adminapi']['username'],
            $this->config['adminapi']['password']
        ];

    }

    public function sendRequestV1(string $method, string $path, array $content)
    {
        $url = $this->getBaseUrl() . preg_replace('/^v1\//', '', $path);

        $auth = $this->getAdminAuthDetails();

        $isRaaSRequest = false;

        // For merchant dashboard requests : RaaS
        if (strpos($path, "merchant/mid/") !== false)
        {
            $isRaaSRequest = true;

            $mid = $this->app['basicauth']->getMerchant()->getId();

            $url =  str_replace_first('/mid/', "/" . $mid . "/", $url);
        }

        if ($method == 'POST')
        {
            if ($isRaaSRequest === true)
            {
                $userEmail = $this->app['basicauth']->getUser()->getEmail();
            }
            else
            {
                $userEmail = $this->app['basicauth']->getAdmin()->getEmail();
            }

            $content['created_by'] = $userEmail;
        }

        $headers[self::X_RAZORPAY_TASKID_HEADER] = $this->app['request']->getTaskId();

        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $content,
            'headers' => $headers,
        ];

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_REQUEST, $request);

        $request['options'] = [
            'auth' => $auth,
            'hooks' => $this->getRequestHooks(),
        ];

        $response = $this->sendRawRequest($request);

        $parsedResponse = $this->processResponseV1($response);

        $this->trace->info(TraceCode::GOVERNOR_SERVICE_RESPONSE, $parsedResponse ?? []);

        return $parsedResponse;
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Expect:'));
    }

    public function fetch(string $entity, string $id, array $input)
    {
        $path = "admin/" . $entity . "/" . $id;

        return $this->sendRequestV1("GET", $path, $input);
    }

    public function fetchMultiple(string $entity, array $input)
    {
        $path = "admin/" . $entity;

        return $this->sendRequestV1("POST", $path, $input);
    }
}
