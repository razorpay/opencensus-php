<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Models\Gateway\Downtime\Core;
use RZP\Models\Payment;
use Throwable;
use \WpOrg\Requests\Exception as Requests_Exception;
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

    const FPX_PAYMENT_NAMESPACE = "fpxPayments";

    const FPX = "fpx";

    // Mozart constants
    const HEADERS = 'headers';
    const CONTENT = 'content';
    const OPTIONS = 'options';
    const STATUS_CODE = 'status_code';
    const URL = 'url';
    const WEBHOOK = 'webhook';
    const TRANSLATE = 'translate';

    // Mozart error constants
    const DESCRIPTION = 'description';
    const GATEWAY_ERROR_CODE = 'gateway_error_code';
    const GATEWAY_STATUS_CODE = 'gateway_status_code';
    const INTERNAL_ERROR_CODE = 'internal_error_code';
    const GATEWAY_ERROR_DESCRIPTION = 'gateway_error_description';
    const ERROR = 'error';
    const DATA = 'data';
    const MORE_INFORMATION = 'moreInformation';
    const NO_ERROR_MAPPING_DESCRIPTION = '(No error description was mapped for this error code)';

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

        if (isset($this->app['rzp.mode'])) {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        string $version = self::DEFAULT_MOZART_VERSION,
        bool $useMozartMappedInternalErrorCode = false,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT,
        bool $logResponse = true, bool $addEntities = true)
    {
        $this->namespace = $namespace;
        $this->gateway = $gateway;
        $this->action = $action;
        $this->version = $version;

        $url = $this->getUrl();

        $authentication = $this->getAuthenticationDetails();

        $request = $this->getRequest($url, $authentication, $input, $timeout, $connectTimeout, $addEntities);

        $this->traceMozartServiceRequest($request);

        $responseBody = $this->sendRawRequest($request);

        $responseArray = $this->jsonToArray($responseBody);

        $this->traceMozartServiceResponse($responseArray ?? $responseBody ?? null, $logResponse);

        // Un-setting the raw field here, this field is the json encoded response from the gateway
        // since we have already logged the response here, there's no need to application logic
        // to use it
        unset($responseArray['data']['_raw']);

        $this->checkGatewayErrorsAndThrowException($responseArray, $useMozartMappedInternalErrorCode);

        return $responseArray;
    }

    public function translateWebhook(string $gateway, string $payload, string $mode): array
    {
        $this->mode = $mode;

        $translateWebhookRequest = $this->getRequestV2(
            $payload,
            self::WEBHOOK,
            $gateway,
            self::DEFAULT_MOZART_VERSION,
            self::TRANSLATE);

        $this->trace->info(TraceCode::MOZART_SERVICE_REQUEST, [
            self::URL => $translateWebhookRequest[self::URL],
            self::CONTENT => $translateWebhookRequest[self::CONTENT],
        ]);

        try {
            $translateWebhookResponse = $this->sendRequest($translateWebhookRequest);
        } catch (\Exception $exception) {
            $data = [
                'exception' => $exception->getMessage(),
                self::URL => $translateWebhookRequest[self::URL],
                self::CONTENT => $translateWebhookRequest[self::CONTENT],
            ];

            $this->trace->error(TraceCode::MOZART_SERVICE_REQUEST_FAILED, $data);

            throw new Exception\IntegrationException(
                $exception->getMessage(),
                ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR);

        }

        $this->trace->info(TraceCode::MOZART_SERVICE_RESPONSE, [
            self::STATUS_CODE => $translateWebhookResponse[self::STATUS_CODE],
            self::CONTENT => $translateWebhookResponse[self::CONTENT],
        ]);

        return $translateWebhookResponse;
    }

    protected function getUrl(): string
    {
        $baseUrl = $this->config->get('applications.mozart.url');

        $url = "{$baseUrl}{$this->namespace}/{$this->gateway}/{$this->version}/{$this->action}";

        return $url;
    }

    protected function getUrlV2(string $namespace, string $gateway, string $version, string $action): string
    {
        $urlConfig = 'applications.mozart.' . $this->mode . '.url';

        $baseUrl = $this->config->get($urlConfig);

        return "{$baseUrl}{$namespace}/{$gateway}/{$version}/{$action}";
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

    protected function getRequest(string $url, array $authentication, array $input,
                                  int $timeout = self::TIMEOUT, int $connectTimeout = self::CONNECT_TIMEOUT, $addEntities): array
    {
        if ($addEntities) {
            $requestBody['entities'] = $input;
        } else {
            $requestBody = $input;
        }
        $request = [
            'url' => $url,
            'method' => Requests::POST,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
                RequestHeader::X_TASK_ID => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($requestBody),
            'options' => [
                'auth' => $authentication,
                'timeout' => $timeout,
                'connect_timeout' => $connectTimeout
            ]
        ];

        return $request;
    }

    protected function getRequestV2(string $content, string $namespace, string $gateway, string $version, string $action)
    {
        $request = [
            self::URL => $this->getUrlV2($namespace, $gateway, $version, $action),
            'method' => Requests::POST,
            self::HEADERS => [
                RequestHeader::CONTENT_TYPE => 'application/json',
                RequestHeader::X_TASK_ID => $this->app['request']->getTaskId(),
            ],
            self::CONTENT => $content,
            self::OPTIONS => ['auth' => $this->getAuthenticationDetails()],
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
        try {
            $responseBody = $this->sendRequest($request)[self::CONTENT];

            return $responseBody;
        } catch (Requests_Exception $e) {
            $errorCode = TraceCode::MOZART_SERVICE_REQUEST_FAILED;

            if (checkRequestTimeout($e) === true) {
                $errorCode = TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT;
            }

            $this->trace->traceException($e, Trace::ERROR, $errorCode);

            throw $e;
        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::MOZART_SERVICE_REQUEST_FAILED
            );

            throw $ex;
        }
    }

    public function getDowntimeIssuerData($input, $gateway)
    {
        switch ($gateway)
        {
            case self::FPX:
                $response = $this->sendMozartRequest(self::FPX_PAYMENT_NAMESPACE, $gateway, Core::MOZART_GET_DOWNTIME_ACTION, $input);
        }

        return $response;
    }

    protected function sendRequest(array $request)
    {
        if (isset($request['options']) === false) {
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
                self::HEADERS => $response->headers->getAll(),
                self::CONTENT => $response->body,
                self::STATUS_CODE => $response->status_code,
            ];
    }

    protected function validateResponse($response)
    {
        $statusCode = $response->status_code;

        if (in_array($statusCode, [503, 504], true) === true) {
            throw new Exception\IntegrationException(
                'Response status: ' . $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
                [
                    'status_code' => $statusCode,
                    'body' => $response->body,
                ]);
        } else if ($statusCode >= 500) {
            throw new Exception\IntegrationException(
                'Response status: ' . $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
                [
                    'status_code' => $statusCode,
                    'body' => $response->body,
                ]);
        } else if ($statusCode >= 400) {
            throw new Exception\IntegrationException(
                'Response status: ' . $statusCode,
                ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
                [
                    'status_code' => $statusCode,
                    'body' => $response->body,
                ]);
        }
    }

    protected function getMozartRequestParams()
    {
        return [
            'namespace' => $this->namespace,
            'gateway' => $this->gateway,
            'action' => $this->action,
            'version' => $this->version,
        ];
    }

    /**
     * Check for gateway errors
     *
     * @param array $response
     * @param bool $useMozartMappedInternalErrorCode whether to use internal error codes mapped by mozart.
     *
     * @throws Exception\GatewayErrorException
     */
    protected function checkGatewayErrorsAndThrowException(array $response, bool $useMozartMappedInternalErrorCode)
    {
        if ($response['success'] !== true) {
            $errorCode = ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR;

            if ($useMozartMappedInternalErrorCode === true) {
                $errorCode = $response['error']['internal_error_code'];
            }

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [
                    'error' => $response['error'],
                    'data' => $response['data']
                ],
                null,
                $this->getUrl());
        }
    }

    protected function traceMozartServiceRequest($request)
    {
        unset($request['options']['auth']);

        $traceRequest = $this->unsetSensitiveValuesOfGateways($request);

        $this->trace->info(TraceCode::MOZART_SERVICE_REQUEST, $traceRequest);
    }

    protected function traceMozartServiceResponse($response, $logResponse)
    {
        if ($logResponse) {
            $this->trace->info(TraceCode::MOZART_SERVICE_RESPONSE, $response);
        }
    }

    protected function jsonToArray($json)
    {
        $decodedJson = json_decode($json, true);

        if ($decodedJson === null) {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_JSON_TO_ARRAY,
                [
                    'json' => $json,
                    'error' => json_last_error_msg(),
                ]);
        }

        return $decodedJson;
    }

    protected function unsetSensitiveValuesOfGateways(array $request)
    {
        $content = $this->jsonToArray($request['content']);

        // for now RBL is the only gatewayn
        unset($content['entities']['source_account']['credentials']);

        return $content;
    }
}
