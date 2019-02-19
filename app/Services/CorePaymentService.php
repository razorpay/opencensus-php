<?php

namespace RZP\Services;

use Requests;
use Requests_Session;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;

class CorePaymentService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER   = 'X-Razorpay-Mode';
    const APPLICATION_JSON         = 'application/json';

    const REQUEST_TIMEOUT = 20;
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const GATEWAY   = 'gateway';
    const ACTION    = 'action';
    const INPUT     = 'input';
    const DATA      = 'data';

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $request;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.cps');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->getBaseUrl();

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    public function action(string $gateway, string $action, array $input)
    {
        $content = [
            self::ACTION  => $action,
            self::GATEWAY => $gateway,
            self::INPUT   => $input
        ];

        $response = $this->sendRequest('POST', 'action', $content);

        return $response;
    }

    public function sendRequest(string $method, string $url, array $data = [])
    {
        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
                self::X_RAZORPAY_MODE_HEADER   => $this->app['rzp.mode'],
            ],
        ];

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        $response = $this->processResponse($response);

        $this->traceResponse($response);

        return $response;
    }

    protected function sendRawRequest($request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = $this->request->request(
                    $request['url'],
                    $request['headers'],
                    json_encode($request['content']),
                    $request['method']);

                break;
            }
            catch(\Requests_Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::CORE_PAYMENT_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;

                    continue;
                }

                $this->throwServiceErrorException($e);
            }
        }

        return $response;
    }

    protected function getBaseUrl(): string
    {
        $mode = $this->app['rzp.mode'];

        $url = $this->config['url'][$mode];

        return $url;
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->config['username'],
                $this->config['password']
            ],
        ];

        return $options;
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
            self::X_RAZORPAY_APP_HEADER    => 'api',
        ];

        return $headers;
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);
        unset($request['content']['card']);

        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_REQUEST, $request);
    }

    protected function traceResponse(array $response)
    {
        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_RESPONSE, $response);
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_CORE_PAYMENT_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_CORE_PAYMENT_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function processResponse($response)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);

        if ($code === 200)
        {
            return $responseBody[self::DATA];
        }
        else
        {
            $this->checkForErrors($responseBody);
        }
    }

    protected function handleBadRequestErrors(array $error)
    {
        $code = $error['internal_error_code'];

        $field = $error['field'] ?? null;

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? null;

        throw new Exception\BadRequestException($code, $field, $data, $description);
    }

    protected function handleInternalServerErrors(array $error)
    {
        $message = $error['description'] ?? 'core payment service request failed';

        throw new Exception\ServerErrorException(
            $message,
            ErrorCode::SERVER_ERROR_CORE_PAYMENT_SERVICE_FAILURE,
            $error);
    }

    protected function handleGatewayErrors(array $error)
    {
        $errorCode = $error['internal_error_code'];

        switch ($errorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($errorCode);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($errorCode);

            default:
                throw new Exception\GatewayErrorException($error['internal_error_code']);
        }
    }

    protected function checkForErrors($response)
    {
        $errorCode = $response['internal_error_code'];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($response['error']);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($response['error']);
                break;

            case ErrorClass::SERVER:
                $this->handleInternalServerErrors($response['error']);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class');
        }
    }

    protected function jsonToArray($json)
    {
        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::CORE_PAYMENT_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function getErrorClassFromErrorCode($code)
    {
        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if ($class === 'BAD')
        {
            $class = ErrorClass::BAD_REQUEST;
        }

        return $class;
    }
}
