<?php

namespace RZP\Services\Relay;

use RZP\Error\ErrorCode;
use RZP\Exception;
use \WpOrg\Requests\Response;
use RZP\Constants\Mode;
use RZP\Exception\ServerErrorException;
use RZP\Http\RequestHeader;
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

    protected $mode;

    protected $request;

    const KEY                   = 'key';
    const SECRET                = 'secret';

    const BODY                  = 'body';
    const CODE                  = 'code';

    // Headers
    const ACCEPT                = 'Accept';
    const ADMIN_EMAIL           = 'admin_email';
    const CONTENT_TYPE          = 'Content-Type';
    const X_REQUEST_ID          = 'X-Request-ID';
    const REQUEST_TIMEOUT       = 60;
    const OWNER_ID = "owner_id";
    const OWNER_TYPE = "owner_type";
    const ORG_ID = "org_id";
    const CREATOR_ID = "creator_id";
    const CREATOR_TYPE = "creator_type";

    const SERVICE_RELAY        = 'relay';

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PATCH = 'PATCH';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * Refunds Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->config  = $app['config']->get('applications.relay');

        $this->baseUrl = $this->config[$this->mode]['url'];

        $this->auth    = $app['basicauth'];

        $this->request = $app['request'];

        $this->setHeaders();
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param string $service
     * @param string|null $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    public function makeRequest(string $endpoint, string $method, array $data, string $mode = null): array
    {
        if ($mode === null)
        {
            $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;
        }

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT, Requests::DELETE], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : '{}';
        }

        $url = $this->baseUrl . $endpoint;

        $auth = $this->getAuth($mode);

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $auth,
        ];

        if ($method === Requests::DELETE)
        {
            $options += [ 'data_format' => 'body' ];
        }

        $request = [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];

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
                TraceCode::RELAY_REQUEST_FAILED,
                [
                    'message'      => $e->getMessage(),
                    'request_body' => $request['content'],
                ]);

            throw $e;
        }

        $this->trace->info(TraceCode::RELAY_RESPONSE, [
            'response' => $response->body
        ]);

        $resp = $this->parseResponse($response);

        $this->handleResponseCodes($resp);

        return $resp;
    }

    /**
     * Method to parse response from refunds.
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

        $this->trace->info(TraceCode::RELAY_REQUEST, $request);
    }

    /**
     * Method to set auth in the request
     *
     * @param string $service
     * @param string $mode
     * @return array
     */
    protected function getAuth(string $mode) : array
    {
        $service = $this->config[$mode];

        return [
            $service[self::KEY],
            $service[self::SECRET],
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
        $headers[RequestHeader::X_USER_EMAIL] = $this->getAdminEmail();

        $this->headers = $headers;
    }

    /**
     * @param array  $response
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    protected function handleResponseCodes(array $response)
    {
        $code = $response[self::CODE];
        $body = $response[self::BODY];

        if (in_array($code, [200, 400, 401, 500], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from relay.',
                [
                    'status_code'   => $code,
                    'response_body' => $body,
                ]);
        }

        if ($code !== 200)
        {
            $this->trace->warning(TraceCode::RELAY_REQUEST_FAILED, [
                'status_code'   => $code,
                'response_body' => $body,
            ]);

            throw new ServerErrorException(
                'Received Server Error in relay service response',
                ErrorCode::SERVER_ERROR,
                [ 'body'    => $body ]);
        }
    }

    protected function setApprovalDetails(): array
    {
        $details = [];

        $details[self::CREATOR_ID] = $this->auth->getAdmin()->getId();
        $details[self::CREATOR_TYPE] = "user";
        $details[self::ORG_ID] = $this->auth->getAdmin()->getOrgId();

        //hard code owner details for our use case.
        $details[self::OWNER_ID] = "10000000000000";
        $details[self::OWNER_TYPE] = "user";

        return $details;
    }

    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()[self::ADMIN_EMAIL] ?? 'EMAIL_NOT_FOUND';
    }
}
