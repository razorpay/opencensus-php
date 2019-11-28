<?php

namespace RZP\Services;

use Requests;
use Throwable;
use Requests_Exception;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;

/**
 * Interface for api to talk to Mozart service
 */
class Mozart
{
    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    const DEFAULT_MOZART_VERSION = 'v1';

    // Mozart constants
    const HEADERS                        = 'headers';
    const BODY                           = 'body';
    const CONTENT                        = 'content';
    const STATUS_CODE                    = 'status_code';
    const URL                            = 'url';
    const WEBHOOK                        = 'webhook';
    const TRANSLATE                      = 'translate';

    // Mozart error constants
    const DESCRIPTION                    = 'description';
    const GATEWAY_ERROR_CODE             = 'gateway_error_code';
    const GATEWAY_STATUS_CODE            = 'gateway_status_code';
    const INTERNAL_ERROR_CODE            = 'internal_error_code';
    const GATEWAY_ERROR_DESCRIPTION      = 'gateway_error_description';
    const ERROR                          = 'error';
    const DATA                           = 'data';
    const MORE_INFORMATION               = 'moreInformation';

    protected $app;

    protected $trace;

    protected $config;

    protected $gateway;

    protected $action;

    protected $namespace;

    protected $version;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $this->mode = $app['rzp.mode'];
    }

    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        string $version = self::DEFAULT_MOZART_VERSION,
        bool $useMozartMappedInternalErrorCode = false)
    {
        $this->namespace = $namespace;
        $this->gateway   = $gateway;
        $this->action    = $action;
        $this->version   = $version;

        $url = $this->getUrl();

        $authentication = $this->getAuthenticationDetails();

        $request = $this->getRequest($url, $authentication, $input);

        $this->traceMozartServiceRequest($request);

        $responseBody = $this->sendRawRequest($request);

        $responseArray = $this->jsonToArray($responseBody);

        $this->traceMozartServiceResponse($responseArray ?? $responseBody ?? null);

        // Un-setting the raw field here, this field is the json encoded response from the gateway
        // since we have already logged the response here, there's no need to application logic
        // to use it
        unset($responseArray['data']['_raw']);

        $this->checkGatewayErrorsAndThrowException($responseArray, $useMozartMappedInternalErrorCode);

        return $responseArray;
    }

    public function translateWebhook(string $path, string $payload) : array
    {
        $request = $this->getTranslateWebhookRequest($path, $payload);

        $this->trace->info(TraceCode::MOZART_SERVICE_REQUEST, [
            self::URL           => $request[self::URL],
            self::CONTENT       => $request[self::CONTENT],
        ]);

        try
        {
            $response = $this->sendRequest($request);
        }
        catch (\Exception $exception)
        {
            $data = [
                'exception'          => $exception->getMessage(),
                self::URL            => $request[self::URL],
                self::CONTENT        => $request[self::CONTENT],
            ];

            $this->trace->error(TraceCode::MOZART_SERVICE_REQUEST_FAILED, $data);

            throw new Exception\IntegrationException(
                $exception->getMessage(),
                ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR);

        }

        $this->trace->info(TraceCode::MOZART_SERVICE_RESPONSE, [
            self::STATUS_CODE   => $response[self::STATUS_CODE],
            self::BODY          => $response[self::BODY],
        ]);

        return $response;
    }

    protected function getUrl(): string
    {
        $baseUrl = $this->config->get('applications.mozart.url');

        $url = "{$baseUrl}{$this->namespace}/{$this->gateway}/{$this->version}/{$this->action}";

        return $url;
    }

    protected function getAuthenticationDetails(): array
    {
        $usernameConfig = 'applications.mozart.' . $this->mode . '.username';
        $passwordConfig = 'applications.mozart.' . $this->mode . '.password';

        $authentication = [
            $this->config->get($usernameConfig),
            $this->config->get($passwordConfig)
        ];

        return $authentication;
    }

    protected function getRequest(string $url, array $authentication, array $input): array
    {
        $requestBody['entities'] = $input;

        $request = [
            'url' => $url,
            'method' => Requests::POST,
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::X_TASK_ID     => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($requestBody),
            'options' => [
                'auth' => $authentication
            ]
        ];

        return $request;
    }

    /**
     * @param array $request
     *
     * @return string $response
     * @throws Throwable
     */
    protected function sendRawRequest(array $request)
    {
        try
        {
            $responseBody = $this->sendRequest($request)[self::BODY];

            return $responseBody;
        }
        catch (Requests_Exception $e)
        {
            $errorCode = TraceCode::MOZART_SERVICE_REQUEST_FAILED;

            if (checkRequestTimeout($e) === true)
            {
                $errorCode = TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT;
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                $errorCode
            );

            throw $e;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::MOZART_SERVICE_REQUEST_FAILED
            );

            throw $ex;
        }
    }

    protected function sendRequest(array $request)
    {
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
        }

        $headers = $request['headers'] ?? [];

        $method = $request['method'] ?? Requests::POST;

        $request['options']['timeout'] = $request['options']['timeout'] ?? static::TIMEOUT;

        $request['options']['connect_timeout'] = $request['options']['connect_timeout'] ?? static::CONNECT_TIMEOUT;

        $response = Requests::request(
            $request['url'],
            $headers,
            $request['content'],
            strtoupper($method),
            $request['options']);

        $this->validateResponse($response);

        return
            [
                self::HEADERS       =>  $response->headers->getAll(),
                self::BODY          =>  $response->body,
                self::STATUS_CODE   =>  $response->status_code,
            ];
    }

    protected function validateResponse(\Requests_Response $response)
    {
        $statusCode = $response->status_code;

        if (in_array($statusCode, [503, 504], true) === true)
        {
            throw new Exception\IntegrationException(
                'Response status: '. $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
                [
                    'status_code'   => $statusCode,
                    'body'          => $response->body,
                ]);
        }
        else if ($statusCode >= 500)
        {
            throw new Exception\IntegrationException(
                'Response status: '. $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
                [
                    'status_code'   => $statusCode,
                    'body'          => $response->body,
                ]);
        }
        else if ($statusCode >= 400)
        {
            throw new Exception\IntegrationException(
                'Response status: '. $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
                [
                    'status_code'   => $statusCode,
                    'body'          => $response->body,
                ]);
        }
    }

    protected function getMozartRequestParams()
    {
        return [
            'namespace'   => $this->namespace,
            'gateway'     => $this->gateway,
            'action'      => $this->action,
            'version'     => $this->version,
        ];
    }

    /**
     * Check for gateway errors
     *
     * @param array $response
     * @param bool $useMozartErrorCode whether to use internal error codes mapped by mozart.
     * @throws Exception\GatewayErrorException
     */
    protected function checkGatewayErrorsAndThrowException(array $response, bool $useMozartMappedInternalErrorCode)
    {
        if ($response['success'] !== true)
        {
            $errorCode = ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR;

            if ($useMozartMappedInternalErrorCode === true)
            {
                $errorCode = $response['error']['internal_error_code'];
            }

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [
                    'error' => $response['error'],
                    'data'  => $response['data']
                ],
                null,
                $this->getUrl());
        }
    }

    protected function traceMozartServiceRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::MOZART_SERVICE_REQUEST, $request);
    }

    protected function traceMozartServiceResponse($response)
    {
        $this->trace->info(TraceCode::MOZART_SERVICE_RESPONSE, $response);
    }

    protected function jsonToArray($json)
    {
        $decodedJson = json_decode($json, true);

        if ($decodedJson === null)
        {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_JSON_TO_ARRAY,
                [
                    'json' => $json,
                    'error' => json_last_error_msg(),
                ]);
        }

        return $decodedJson;
    }

    protected function getTranslateWebhookRequest(string $path, string $payload)
    {
        return [
            'url'       => $this->getTranslateWebhookUrl($path),
            'content'   => $payload,
            'method'    => Requests::POST,
            'options'   => ['auth'  => $this->getAuthenticationDetails()],
        ];
    }

    protected function getTranslateWebhookUrl(string $path) : string
    {
        $baseUrl = $this->config->get('applications.mozart.url');

        $namespace = self::WEBHOOK;

        $version = self::DEFAULT_MOZART_VERSION;

        $action = self::TRANSLATE;

        return "{$baseUrl}{$namespace}/{$path}/{$version}/{$action}";
    }
}
