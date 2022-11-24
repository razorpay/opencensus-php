<?php

namespace RZP\Services\Wallet;

use RZP\Error\ErrorCode;
use RZP\Exception;
use \WpOrg\Requests\Response;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;

class Base
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $headers;

    protected $auth;

    protected $request;

    const USERNAME              = 'username';
    const SECRET                = 'secret';

    const BODY                  = 'body';
    const CODE                  = 'code';
    const MESSAGE               = 'message';
    // Headers
    const ACCEPT                = 'Accept';
    const ADMIN_EMAIL           = 'admin_email';
    const CONTENT_TYPE          = 'Content-Type';
    const X_REQUEST_ID          = 'X-Request-ID';
    const REQUEST_TIMEOUT       = 30;

    /**
     * Wallet Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->config  = $app['config']->get('applications.wallet');

        $this->baseUrl = $this->config['url'];

        $this->auth    = $app['basicauth'];

        $this->request = $app['request'];

        $this->setHeaders();
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @param string $user
     * @param string|null $mode
     * @return array
     * @throws \Throwable
     */
    public function makeRequest(string $method, string $endpoint, array $body, string $user, string $mode = null): array
    {
        if ($mode === null)
        {
            $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;
        }

        $url = $this->baseUrl[$mode] . $endpoint;

        $auth = $this->getAuth($user, $mode);

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $auth,
        ];

        $request = [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
        ];

        if ($method !== Requests::GET)
        {
            $request['content'] = (empty($body) === false) ? json_encode($body) : [];
        }

        try
        {
            $this->traceRequest($request);

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
                TraceCode::WALLET_REQUEST_EXCEPTION,
                [
                    'message'      => $e->getMessage(),
                    'request_body' => $request['content'],
                ]);

            throw $e;
        }

        $this->trace->info(TraceCode::WALLET_RESPONSE, [
            'response'    => $response->body,
            'status_code' => $response->status_code,
        ]);

        $resp = $this->parseResponse($response);

        $this->handleResponseCodes($resp);

        return $resp[self::BODY];
    }

    /**
     * Method to parse response from Wallet.
     *
     * @param  $response
     * @return array
     */
    protected function parseResponse($response): array
    {
        $code = null;

        $body = null;

        if($response !== null)
        {
            $code = $response->status_code;
            $body = json_decode($response->body, true);
        }

        return [
            'body' => $body,
            'code' => $code,
        ];
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::WALLET_REQUEST, $request);
    }

    /**
     * Method to set auth in the request
     *
     * @param string $user
     * @param string $mode
     * @return array
     */
    protected function getAuth(string $user, string $mode) : array
    {
        $user = $this->config[$user][$mode];

        return [
            $user[self::USERNAME],
            $user[self::SECRET],
        ];
    }

    /**
     * Method to set headers in the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]       = 'application/json';
        $headers[self::CONTENT_TYPE] = 'application/json';
        $headers[self::X_REQUEST_ID]  = $this->request->getId();

        $this->headers = $headers;
    }

    /**
     * @param array  $response
     * @throws \Throwable
     */
    protected function handleResponseCodes(array $response)
    {
        $code = $response[self::CODE];
        $body = $response[self::BODY];

        if ($code == 200)
        {
            return;
        }

        $this->trace->warning(TraceCode::WALLET_REQUEST_EXCEPTION, [
            'status_code'   => $code,
            'response_body' => $body,
        ]);

        if ($code >= 400 and $code < 500)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RAZORPAY_WALLET_ERROR,null,
                ['method'=>'wallet'],$body['error']['description']);
        }
        else if ($code >= 500)
        {
            throw new Exception\ServerErrorException(
                'Unexpected response code received from wallet service.',
                ErrorCode::SERVER_ERROR,
                [
                    'status_code'   => $code,
                    'response_body' => $body,
                ]);
        }
    }
}
