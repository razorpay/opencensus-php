<?php

namespace RZP\Services\FTS;

use Requests;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
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

    // Account related URIs
    const FUND_ACCOUNT_CREATE_URI  = '/account';
    const FUND_ACCOUNT_REGISTER_URI  = '/account/register';

    // Transfer related URIs
    const FUND_TRANSFER_CREATE_URI = '/transfer';

    // Source Account related URIs
    const SOURCE_ACCOUNT_CREATE_URI = '/source_account';

    // Headers
    const ACCEPT        = 'Accept';
    const ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const REQUEST_TIMEOUT = 30;

    /**
     * FTS Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->auth = $app['basicauth'];

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.fts');

        $this->mode = $app['rzp.mode'];

        $this->baseUrl = $this->config[$this->mode]['url'];

        $this->key = $this->config[$this->mode]['fts_key'];

        $this->secret = $this->config[$this->mode]['fts_secret'];

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
    protected function createAndSendRequest(
        string $endpoint,
        string $method,
        array $data = []): array
    {
        $request = $this->generateRequest($method, $endpoint, $data);

        $response = $this->sendFtsRequest($request);

        $this->trace->info(TraceCode::FTS_RESPONSE, [
            'response' => $response->body
        ]);

        if ($response->status_code === 409)
        {
            // ToDo check Tracecode with Ratan
            throw new Exception\RecordAlreadyExists(
                'record already exists',
                TraceCode::FTS_DUPLICATE_TRANSFER_REQUEST_SENT, [
                'response' => $response->body,
            ]);
        }

        return $this->parseResponse($response);
    }

    /**
     * Generates request using the given params
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
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
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
    protected function sendFtsRequest(array $request): \Requests_Response
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
     * @param \Requests_Response $response
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(\Requests_Response $response): array
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
}
