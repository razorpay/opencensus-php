<?php

namespace RZP\Services\Route;

use RZP\Models\Admin;
use Razorpay\Edge\Passport\Passport;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\BasicAuth\BasicAuth;
use \WpOrg\Requests\Response;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;

class Base
{
    protected $app;

    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $headers;

    protected $auth;

    protected $request;

    const KEY                   = 'key';
    const SECRET                = 'secret';

    const BODY                  = 'body';
    const CODE                  = 'code';

    // Headers
    const ACCEPT                = 'Accept';
    const CONTENT_TYPE          = 'Content-Type';
    const X_REQUEST_ID          = 'X-Request-ID';
    const X_TASK_ID             = 'X-Task-ID';
    const X_PASSPORT_JWT_V1     = 'X-Passport-JWT-V1';
    const PASSPORT_AUD          = 'route';
    const REQUEST_TIMEOUT       = 60;
    const CONSUMER              = 'consumer';
    const PASSPORT              = 'passport';
    const NAME                  = 'name';
    const TYPE                  = 'type';
    const APP_USER_ID_HEADER = 'App-User-Id';

    /**
     *  Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->trace   = $app['trace'];

        $this->config  = $app['config']->get('applications.route');

        $this->baseUrl = $this->config['url'];

        $this->auth    = $app['basicauth'];

        $this->request = $app['request'];

        $this->setHeaders();
    }

    /**
     * @param string $endpoint
     * @param array  $data
     * @param string $service
     * @param string $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    protected function sendRequest(string $endpoint, string $method, array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $options = [
            'timeout' => (new Config())->getRequestTimeout(),
            'auth'    => [
                $this->config['username'],
                $this->config['password']
            ],
        ];

        $request = [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
        ];

        if ($method === Requests::POST)
        {
            // here we will be making call to proto endpoints which are POST, we can route a GET request
            // from API as POST request to route service with empty body i.e {}, for requests which
            // do not need a request body but have to be made POST because of protobuf.
            $request['content'] = (empty($data) === false) ? json_encode($data) : json_encode(new \stdClass());
        }
        else if ($method === Requests::GET)
        {
            $request['content'] = $data;
        }

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
                TraceCode::ROUTE_MICROSERVICE_REQUEST_FAILURE,
                [
                    'message'      => $e->getMessage(),
                    'request_body' => $request['content'],
                ]);

            (new Metric)->pushRequestMetrics(null, $e);

            throw $e;
        }

        (new Metric)->pushRequestMetrics($response->status_code);

        $this->traceResponse($response);

        $resp = $this->parseResponse($response);

        $this->handleResponseCodes($resp);

        return $resp['body'];
    }

    /**
     * Method to parse response from Settlements.
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
        if ((new Config())->shouldLogRequest())
        {
            unset($request['options']['auth']);

            unset($request['headers'][self::X_PASSPORT_JWT_V1]);

            $this->trace->info(TraceCode::ROUTE_MICROSERVICE_REQUEST, $request);
        }
    }

    protected function traceResponse($response)
    {
        if ((new Config())->shouldLogResponse())
        {
            $this->trace->info(TraceCode::ROUTE_MICROSERVICE_RESPONSE,
            [
                'status_code'   => $response->status_code,
                'response_body' => $response->body
            ]);
        }
    }

    /**
     * Method to set auth in the request
     *
     * @param string $service
     * @param string $mode
     * @return array
     */
    protected function getAuth(string $service, string $mode) : array
    {
        $service = $this->config[$service][$mode];

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
        $headers[self::X_TASK_ID] = $this->app['request']->getTaskId();

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER)))
        {
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

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


        if ($code !== 200 && isset($body['details']))
        {
            $errorBody = $body['details'][0]['error'];
            $errorCode = $errorBody['code'];
            $description = $errorBody['description'];

            $publicError = str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $errorCode))));
            if ($publicError === 'ServerError')
            {
                throw new Exception\ServerErrorException($description, ErrorCode::SERVER_ERROR);
            }

            $publicError = str_replace('Error', '', $publicError);
            $class = 'RZP\Exception' . '\\' . $publicError . 'Exception';
            $args = [constant(ErrorCode::class . '::' . $errorCode), null, null, $description];

            throw new $class(...$args);
        }

        if (in_array($code, [200, 400, 401, 404, 500], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from route service.',
                [
                    'status_code'   => $code,
                    'response_body' => $body,
                ]);
        }

        if ($code !== 200)
        {
            $this->trace->warning(TraceCode::ROUTE_MICROSERVICE_REQUEST_FAILURE, [
                'status_code'   => $code,
                'response_body' => $body,
            ]);

            throw new Exception\ServerErrorException(
                'Non 200 response code received from Route microservice',
                ErrorCode::SERVER_ERROR_ROUTE_SERVICE_FAILURE,
                [
                    'error'  => $response['error']['internal_error_code']
                ]
            );
        }
    }

    protected function setCustomHeaders(array $customHeaders = [])
    {
        $this->headers = $this->headers + $customHeaders;
    }

    protected function addNewPassportToken()
    {
        $passportHeader = (empty($this->auth->getPassportFromJob()) === false) ? $this->auth->getPassportFromJob() : $this->auth->getPassportJwt(self::PASSPORT_AUD);

        $customHeader = [
            self::X_PASSPORT_JWT_V1 => $passportHeader,
        ];

        // set custom headers
        $this->setCustomHeaders($customHeader);
    }

    protected function addPassportToken()
    {
        $jwt = $this->getJwtTokenFromRequestHeader();

        $customHeader = [
            self::X_PASSPORT_JWT_V1 => $jwt,
        ];

        // set custom headers
        $this->setCustomHeaders($customHeader);

    }

    protected function getHeadersWithJwt()
    {
        $jwt = $this->app['basicauth']->getPassportJwt($this->baseUrl);

        /** @var BasicAuth $ba */
        $ba = $this->app['basicauth'];

        $headers = [];

        if ($ba->isPrivilegeAuth() === true)
        {
            $passport = $ba->getPassport();

            if (array_key_exists(self::CONSUMER, $passport) === true)
            {
                if ($passport[self::CONSUMER][self::TYPE] === BasicAuth::PASSPORT_CONSUMER_TYPE_USER)
                {
                    $this->trace->info(TraceCode::PASSPORT_EDIT_FOR_PRIVILEGE_AUTH_WITH_USER_CLAIMS,
                        [
                            self::PASSPORT => $ba->getPassport(),
                        ]);

                    $baTemp = clone $ba;

                    $baTemp->setPassportConsumerClaims(BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION,
                        $ba->getInternalApp(),
                        true,
                        [self::NAME => $ba->getInternalApp()]);

                    $jwt = $baTemp->getPassportJwt($this->baseUrl);

                    $headers[self::APP_USER_ID_HEADER] = $ba->getUser()->getId();
                }
            }
        }

        $headers[Passport::PASSPORT_JWT_V1] = $jwt;

        return $headers;
    }

    protected function getJwtTokenFromRequestHeader()
    {
        return Request::header('X-Passport-JWT-V1');
    }
}
