<?php

namespace RZP\Services;

use Requests;
use Throwable;
use Requests_Exception;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;

/**
 * Interface for api to talk to Mozart service
 */
class Mozart
{
    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    const DEFAULT_MOZART_VERSION = 'v1';

    // Mozart error constants
    const DESCRIPTION                    = 'description';
    const GATEWAY_ERROR_CODE             = 'gateway_error_code';
    const GATEWAY_STATUS_CODE            = 'gateway_status_code';
    const INTERNAL_ERROR_CODE            = 'internal_error_code';
    const GATEWAY_ERROR_DESCRIPTION      = 'gateway_error_description';

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
    }

    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        $version = self::DEFAULT_MOZART_VERSION)
    {
        $this->namespace = $namespace;
        $this->gateway   = $gateway;
        $this->action    = $action;
        $this->version   = $version;

        $url = $this->getUrl();

        $authentication = $this->getAuthenticationDetails();

        $request = $this->getRequest($url, $authentication, $input);

        $responseBody = $this->sendRawRequest($request);

        $responseArray = $this->jsonToArray($responseBody);

        $this->traceMozartServiceResponse($responseArray ?? $responseBody ?? null, $input);

        // Un-setting the raw field here, this field is the json encoded response from the gateway
        // since we have already logged the response here, there's no need to application logic
        // to use it
        unset($responseArray['data']['_raw']);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($responseArray);

        return $responseArray;
    }

    protected function getUrl(): string
    {
        $baseUrl = $this->config->get('applications.mozart.url');

        $url = "{$baseUrl}{$this->namespace}/{$this->gateway}/{$this->version}/{$this->action}";

        return $url;
    }

    protected function getAuthenticationDetails(): array
    {
        $authentication = [
            'api',
            $this->config->get('applications.mozart.password')
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
        $this->trace->info(TraceCode::MOZART_SERVICE_REQUEST, $request);

        try
        {
            $responseBody = $this->sendRequest($request);

            return $responseBody;
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(
                TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                [
                    'code'          => $exception->getCode(),
                    'message'       => $exception->getMessage(),
                    'type'          => optional($exception->getType()),
                    'data'          => optional($exception->getData()),
                    'request'       => $request,
                ]);

            throw $exception;
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

        try
        {
            $response = Requests::request(
                $request['url'],
                $headers,
                $request['content'],
                strtoupper($method),
                $request['options']);
        }
        catch (Requests_Exception $e)
        {
            $errorCode = ErrorCode::SERVER_ERROR_MOZART_SERVICE_FAILURE;

            if (checkRequestTimeout($e) === true)
            {
                $errorCode = ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT;
            }

            throw new Exception\IntegrationException($e->getMessage(), $errorCode);
        }

        $this->validateResponse($response);

        return $response->body;
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
                    'body' => $response->body,
                ]);
        }
        else if ($statusCode >= 500)
        {
            throw new Exception\IntegrationException(
                'Response status: '. $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
                [
                    'body' => $response->body,
                ]);
        }
        else if ($statusCode >= 300)
        {
            //
            // Trace non 2XX status codes to figure out what else
            // needs to be handled here later.
            //

            $this->trace->error(
                TraceCode::MOZART_SERVICE_UNEXPECTED_RESPONSE,
                [
                    'status_code' => $statusCode,
                    'request'     => $this->getMozartRequestParams(),
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

    protected function checkErrorsAndThrowExceptionFromMozartResponse(array $response)
    {
        if ($response['success'] !== true)
        {
            throw new Exception\IntegrationException(
                'Request to Mozart Service did not return a successful response',
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
                $response);
        }
    }

    protected function traceMozartServiceResponse($response, $input)
    {
        $this->trace->info(
            TraceCode::MOZART_SERVICE_RESPONSE,
            [
                'request'    => $input,
                'response'   => $response,
            ]);
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
}
