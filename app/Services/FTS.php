<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class FTS
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    const FundAccountBaseURL  = '/fund_account';
    const FundTransferBaseURL = '/fund_transfer';

    const URLS = [
        'create'                => 'create',
        'update'                => 'update',
        'register'              => 'register',
        'status'                => 'status',
        'request'               => 'request',
        'attempt'               => 'attempt',
    ];

    // Headers
    const ACCEPT        = 'Accept';
    const X_MODE        = 'X-Mode';
    const ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const REQUEST_TIMEOUT = 60;

    /**
     * FTS constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.fts');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->request = $app['request'];

        $this->key = $this->config['fts_key'];

        $this->secret = $this->config['fts_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function createFundAccount(array $input, bool $throwExceptionOnFailure = false): array
    {
        //TODO: How to handle update?
        return $this->createAndSendRequest(self::FundAccountBaseURL . '/' . self::URLS['create'], 'POST', $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function registerFundAccount(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->createAndSendRequest(self::FundAccountBaseURL . '/' . self::URLS['register'], 'POST', $input, $throwExceptionOnFailure);
    }

    /**
     * Creates and Sends the Request
     * to FTS endpoint.
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param bool   $throwExceptionOnFailure
     *
     * @return array
     */
    protected function createAndSendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $data);

        $response = $this->sendFTSRequest($request);

        $this->trace->info(TraceCode::FTS_RESPONSE, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::FTS_RESPONSE, $decodedResponse ?? []);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    /**
     * Method to set headers in the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_MODE]        = $this->mode;

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \Requests_Response
     * @throws \Requests_Exception
     */
    protected function sendFTSRequest(array $request): \Requests_Response
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
                    'data' => $e->getMessage()
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
     * @param \Requests_Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(\Requests_Response $response, bool $throwExceptionOnFailure = false): array
    {
        $code = null;

        if(!($response === null))
        {
            $code = $response->status_code;
        }


        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from FTS.',
                [
                    'status_code' => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body),
            'code' => $code,
        ];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

}
