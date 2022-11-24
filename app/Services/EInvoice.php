<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\RuntimeException;

class EInvoice
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $request;

    protected $headers;

    protected $auth;

    const REQUEST_TIMEOUT = 900;
    const RESPONSE_SUCCESS_CODES = [200];

    const URLS = [
        'get_access_token'  => '/oauth/access_token',
        'get_e_invoice'     => '/generateEinvoice'
    ];

    // Headers
    const ACCEPT        = 'Accept';
    const CONTENT_TYPE  = 'Content-Type';

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';
    const RESPONSE_STATUS        = 'status';

    const MODE = 'mode';

    /**
     * EInvoice constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.einvoice');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->request = $app['request'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param string $mode
     * @throws \Throwable
     *
     * @return array
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        string $mode,
        array $data = []): array
    {
        $request = $this->generateRequest($endpoint, $method, $data, $mode);

        $response = $this->makeRequest($request);

        $this->trace->info(TraceCode::EINVOICE_RESPONSE, [
            'response' => $response->body
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws \WpOrg\Requests\Exception
     */
    protected function makeRequest(array $request)
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
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EINVOICE_REQUEST_EXCEPTION,
                [
                    'message' => $e->getMessage(),
                    'data'    => $e->getData(),
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
        $content = json_decode($request['content'], true);
        unset($content['access_token']);
        $request['content'] = $content;
        $this->trace->info(TraceCode::EINVOICE_REQUEST, $request);
    }

    /**
     * @param \WpOrg\Requests\Response $response
     *
     * @return array
     * @throws RuntimeException
     */
    protected function parseResponse($response): array
    {
        $code = $response->status_code;

        if (in_array($code, [200], true) === false)
        {

            throw new RuntimeException(
                'Non 200 response code: '. $code.' received from GSP.',
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
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param string $mode
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, string $mode): array
    {
        $url = $this->baseUrl[$mode] . $endpoint;

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
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
     * @param string $mode
     * @return array
     * @throws \Throwable
     */
    public function getAccessToken(string $mode)
    {
        $input = $this->config['access_token'][$mode];

        return $this->sendRequest(self::URLS['get_access_token'], Requests::POST, $mode, $input);
    }

    /**
     * @param string $mode
     * @param array $input
     * @return array
     * @throws \Throwable
     */
    public function getEInvoice(string $mode, array $input)
    {
        return $this->sendRequest(self::URLS['get_e_invoice'], Requests::POST, $mode, $input);
    }
}
