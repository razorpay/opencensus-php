<?php

namespace RZP\Services;

use Config;
use Illuminate\Http\Request;
use RZP\Exception;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;

/**
 * Class XAccountStatements
 *
 * @package RZP\Services
 *
 * Service client for x-account-statements microservice
 * Handles banking account statements data retrieval from the new microservice
 */
class XAccountStatements
{
    // RPC endpoints
    const GET_ACCOUNT_STATEMENTS_PATH = 'v1/account_statements';
    const GET_MULTIPLE_ACCOUNT_STATEMENTS_BY_REFERENCE_NUMBERS_PATH = 'v1/account_statements/fetch_multiple_by_reference_numbers';

    // header constants
    const X_APP_MODE = 'X-App-Mode';
    const X_REQUEST_ID = 'x-razorpay-request-id';
    const X_TASK_ID = 'X-Task-ID';
    const CONTENT_TYPE = 'Content-Type';
    const JSON_CONTENT = 'application/json';
    const BASIC_AUTH_USER = 'internal';

    // http method constants
    const GET = 'GET';
    const POST = 'POST';

    protected $baseUrl;
    protected $secret;
    protected $config;
    protected $trace;
    protected $timeout;
    protected $app;

    public function __construct($app)
    {
        $this->trace = $app['trace'];
        $this->config = $app['config'];
        $this->app = $app;

        $xAccountStatementsConfig = $this->config->get('applications.x_account_statements');

        $this->baseUrl = "https://x-account-statements-ext.razorpay.com";

        $this->secret = $xAccountStatementsConfig['secret'];

        $this->timeout = 60;
    }

    protected function getMode()
    {
        $mode = Mode::LIVE;

        if (isset($this->app['rzp.mode']))
        {
            $mode = $this->app['rzp.mode'];
        }

        return $mode;
    }

    protected function getConstructedUrl(string $path)
    {
        $this->trace->info(TraceCode::DEBUG_LOGGING, [
            "XAS_DEBUG" => $this->baseUrl
        ]);

        return sprintf('%s/%s', $this->baseUrl, $path);
    }

    /**
     * Dashboard backend passes the get params in request body. In case of GET request, this function extracts the query
     * params from request body and adds them in the URL, setting the request body to empty array.
     *
     * @param $url
     * @param $content
     *
     * @return void
     */
    public function processQueryParamsForDashboardGetRequest(&$url, &$content): void
    {
        /* @var Request $request */
        $request = $this->app['request'];

        $body = $request->post();
        $method = $request->getMethod();
        $queryString = $request->getQueryString();
        $urlAppend = '?';

        if (empty($queryString) and !empty($body) and $method === Request::METHOD_GET) // Dashboard requests
        {
            $queryStringFromBody = http_build_query($body);
            $url .= $urlAppend . $queryStringFromBody;
            $content = [];
        }
        elseif (!empty($queryString)) // Non-dashboard requests
        {
            $url .= $urlAppend . $queryString;
        }
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    protected function makeRequest(string $url,
                                   array  $data = [],
                                   array  $headers = [],
                                   string $method = self::GET,
                                   string $mode = Mode::LIVE)
    {
        $globalHeaders = [
            self::CONTENT_TYPE => self::JSON_CONTENT,
            self::X_REQUEST_ID => $this->app['request']->getId(),
            self::X_TASK_ID    => $this->app['request']->getTaskId(),
            self::X_APP_MODE   => $mode,
        ];

        $headers = array_merge($headers, $globalHeaders);

        $devstackLabel = $this->app['request']->header(RequestHeader::DEV_SERVE_USER);

        if ($this->app['env'] !== Environment::PRODUCTION && empty($devstackLabel) === false)
        {
            $headers[RequestHeader::DEV_SERVE_USER] = $devstackLabel;
        }

        $options = [
            'auth'    => [
                self::BASIC_AUTH_USER,
                $this->secret
            ],
            'timeout' => $this->timeout,
        ];

        // JSON encode and add request body if it's not a GET request
        if ($method === self::GET)
        {
            // Dashboard backend passes the get params in request body
            $this->processQueryParamsForDashboardGetRequest($url, $data);
            $requestData = ''; // GET request doesn't have request body
        }
        else
        {
            $requestData = !empty($data) ? json_encode($data) : null;
        }

        $this->trace->info(TraceCode::X_ACCOUNT_STATEMENTS_SERVICE_REQUEST,
            [
                'url'  => $url,
                'mode' => $mode,
            ]);

        $response = Requests::request(
            $url,
            $headers,
            $requestData,
            $method,
            $options);

        $this->trace->info(TraceCode::X_ACCOUNT_STATEMENTS_SERVICE_RESPONSE,
            [
                'url'         => $url,
                'status_code' => $response->status_code
            ]);

        $parsedResponse = json_decode($response->body, true);

        if ($response->status_code >= 500)
        {
            $this->trace->error(
                TraceCode::X_ACCOUNT_STATEMENTS_SERVICE_SERVER_ERROR,
                [
                    'response' => $response->body,
                ]);

            throw new ServerErrorException(
                'Internal Server Error occurred',
                ErrorCode::SERVER_ERROR,
                [
                    'errorDetail' => $response->body,
                    'status_code' => $response->status_code,
                ]);
        }
        else if ($response->status_code >= 400)
        {
            $this->trace->error(
                TraceCode::X_ACCOUNT_STATEMENTS_SERVICE_CLIENT_ERROR,
                [
                    'response' => $response->body,
                ]);

            if ($response->status_code == 400)
            {
                if (isset($parsedResponse['message']) and !empty($parsedResponse['message']))
                {
                    $description = $parsedResponse['message'];

                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR, null,
                        [
                            'errorDetail' => $response->body,
                            'status_code' => $response->status_code,
                        ], $description);
                }
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'errorDetail' => $response->body,
                    'status_code' => $response->status_code,
                ], 'Something went wrong. Please try again.');
        }

        return json_decode($response->body, true);
    }

    /**
     * Get account statements
     * Maps to: rpc GetAccountStatements(GetAccountStatementsRequest) returns (GetAccountStatementsResponse)
     *
     * @param array $queryParams Query parameters for filtering statements
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function getAccountStatements(array $queryParams = [])
    {
        $this->trace->info(TraceCode::DEBUG_LOGGING, [
            "xas_query_params" => $queryParams
        ]);

        // Strip bas_ prefix from id if present
        if (isset($queryParams['id']) && strpos($queryParams['id'], 'bas_') === 0) {
            $queryParams['id'] = substr($queryParams['id'], 4); // Remove 'bas_' prefix if present
        }

        $url = $this->getConstructedUrl(self::GET_ACCOUNT_STATEMENTS_PATH);

        // Add query parameters to URL if they exist
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $this->trace->info(TraceCode::DEBUG_LOGGING, [
            "xas_final_url" => $url,
            "xas_processed_params" => $queryParams
        ]);

        $response = $this->makeRequest($url, [], [], self::GET, $this->getMode());

        return $response;
    }

    /**
     * Get multiple account statements by reference numbers
     * Maps to: rpc GetMultipleAccountStatementByReferenceNumbers(GetMultipleAccountStatementByReferenceNumbersRequest) returns (GetAccountStatementsResponse)
     *
     * @param array $queryParams Query parameters including reference_numbers
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function getMultipleAccountStatementsByReferenceNumbers(array $queryParams = [])
    {
        $url = $this->getConstructedUrl(self::GET_MULTIPLE_ACCOUNT_STATEMENTS_BY_REFERENCE_NUMBERS_PATH);

        $response = $this->makeRequest($url, $queryParams, [], self::GET, $this->getMode());

        return $response;
    }
}
