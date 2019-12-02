<?php

namespace RZP\Services\FTS;

use Requests;
use Requests_Response;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Base
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    protected $mode;

    protected $redis;

    // Account related URIs
    const FUND_ACCOUNT_CREATE_URI  = '/account';
    const FUND_ACCOUNT_REGISTER_URI  = '/account/register';

    // Transfer related URIs
    const FUND_TRANSFER_CREATE_URI = '/transfer';

    // Source Account related URIs
    const SOURCE_ACCOUNT_CREATE_URI = '/source_account';

    const SOURCE_ACCOUNT_DELETE_URI = '/source_account';

    const FUND_ACCOUNT_FETCH_URI  = '/admin/account';

    const FUND_TRANSFER_FETCH_URI = '/admin/transfer';

    const FUND_ACCOUNT_STATUS_FETCH_URI  = '/admin/account/status';

    const FUND_TRANSFER_STATUS_FETCH_URI = '/admin/transfer/status';

    const FUND_TRANSFER_ATTEMPTS_UPDATE_URI = '/admin/attempts/update';

    const FUND_TRANSFER_ATTEMPTS_FETCH_STATUS = '/admin/transfers/status';

    const FUND_TRANSFER_ATTEMPTS_CHECK_STATUS = '/admin/transfers/check';

    const FUND_TRANSFER_ATTEMPTS_RAW_BANK_STATUS = '/admin/attempts/verify';

    // Headers
    const ACCEPT        = 'Accept';
    const ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const TRANSFER_RETRY = 'transfer_retry';

    const ALLOWED_FTS_ACTION = [
        self::TRANSFER_RETRY,
    ];

    const REQUEST_TIMEOUT = 30;

    /**
     * FTS Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->request = $app['request'];

        $this->mode    = $app['rzp.mode'];

        $this->auth    = $app['basicauth'];

        $this->config  = $app['config']->get('applications.fts');

        $this->baseUrl = $this->config[$this->mode]['url'];

        $this->key     = $this->config[$this->mode]['fts_key'];

        $this->secret  = $this->config[$this->mode]['fts_secret'];

        $this->redis   = $app['redis']->connection();

        $this->setHeaders();
    }

    /**
     * Creates and Sends the Request
     * to FTS endpoint.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAndSendRequest(
        string $endpoint,
        string $method,
        array $data = []): array
    {
        $request = $this->generateRequest($method, $endpoint, $data);

        if ($this->mode === Mode::TEST)
        {
            $response = $this->getMockResponseByEndpoint($endpoint);
        }
        else
        {
            $response = $this->sendFtsRequest($request);
        }

        $this->trace->info(TraceCode::FTS_RESPONSE, [
            'response' => $response->body
        ]);

        if ($response->status_code === 409)
        {
            throw new Exception\RecordAlreadyExists(
                'record already exists',
                ErrorCode::BAD_REQUEST_FTS_DUPLICATE_TRANSFER_REQUEST_SENT, [
                'response' => $response->body,
            ]);
        }

        return $this->parseResponse($response);
    }

    /**
     * Generates request using the given params
     * Issue with DELETE method: https://github.com/rmccue/Requests/issues/91
     * Fix for DELETE method: https://github.com/rmccue/Requests/pull/188
     *
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $method, string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT, Requests::DELETE], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret,
            ],
        ];

        if ($method === Requests::DELETE)
        {
            $options += [ 'data_format' => 'body' ];
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * Method to set headers in the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';

        $this->headers = $headers;
    }

    /**
     * Method to send request to FTS endpoint
     *
     * @param array $request
     * @return \Requests_Response
     * @throws \Throwable
     */
    protected function sendFtsRequest(array $request): Requests_Response
    {
        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }

        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_FAILURE_EXCEPTION,
                [
                    'message'      => $e->getMessage(),
                    'request_body' => $request['content'],
                ]);

            throw $e;
        }

        return $response;
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::FTS_REQUEST, $request);
    }


    /**
     * Method to parse response from FTS
     *
     * @param  $response
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(Requests_Response $response): array
    {
        $code = null;

        if($response !== null)
        {
            $code = $response->status_code;
        }

        if (in_array($code, [200, 201, 204], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from FTS.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];
    }

    /**
     * Checks if a valid action is initiated to FTS
     *
     * @param string $action
     * @return bool
     */
    public static function isValidFtsAction(string $action): bool
    {
        return (in_array($action, self::ALLOWED_FTS_ACTION, true) === true);
    }

    protected function getMockResponseByEndpoint(string $endpoint)
    {
        if ($endpoint === self::FUND_TRANSFER_CREATE_URI)
        {
            return $this->mockCreateFundTransferResponse();
        }
    }

    public function mockCreateFundTransferResponse()
    {
        $response = new Requests_Response();

        $response->status_code = 201;

        $content = json_encode([
                Constants::STATUS           => Constants::STATUS_CREATED,
                Constants::MESSAGE          => 'fund transfer sent to fts.',
                Constants::FUND_TRANSFER_ID => random_integer(2),
                Constants::FUND_ACCOUNT_ID  => random_integer(2),
        ]);

        $response->body = $content;

        return $response;
    }

    protected function setDashboardAuth()
    {
        $this->key     = $this->config[$this->mode]['fts_dashboard_key'];

        $this->secret  = $this->config[$this->mode]['fts_dashboard_secret'];
    }
}
