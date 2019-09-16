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

        $this->traceMozartServiceRequest($request);

        $responseBody = $this->sendRawRequest($request);

        $responseArray = $this->jsonToArray($responseBody);

        $this->traceMozartServiceResponse($responseArray ?? $responseBody ?? null);

        // Un-setting the raw field here, this field is the json encoded response from the gateway
        // since we have already logged the response here, there's no need to application logic
        // to use it
        unset($responseArray['data']['_raw']);

        $this->checkGatewayErrorsAndThrowException($responseArray);

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
        try
        {
            $responseBody = $this->sendRequest($request);

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
     * @throws Exception\GatewayErrorException
     */
    protected function checkGatewayErrorsAndThrowException(array $response)
    {
        if ($response['success'] !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
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
}
