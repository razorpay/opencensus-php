<?php

namespace RZP\Services;

use Requests;
use Requests_Session;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class CorePaymentService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const APPLICATION_JSON         = 'application/json';

    const REQUEST_TIMEOUT = 20;
    const MAX_RETRY_COUNT = 1;

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $request;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.core_payment_service');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->config['url'];

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    public function action(string $action, array $input)
    {
        $response = $this->sendRequest($action, 'post', $input);

        return $response;
    }

    public function sendRequest(string $action, string $method, array $data = [])
    {
        $request = [
            'url'     => $action,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            ],
        ];

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        $response = json_decode($response->body, true);

        $this->traceResponse($response);

        $this->checkErrors($response);

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
                    $request['content'],
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

        return $this->parseResponse($response);
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

        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_REQUEST, $request);
    }

    protected function traceResponse(array $response)
    {
        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_RESPONSE, $reponse);
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

    protected function parseResponse($response)
    {
        $code = $response->status_code;

        // TODO: handle json decode errors here
        $responseBody = json_decode($response->body, true);

        if ($response->success === true)
        {
            return $this->checkAndFormatResponse($responseBody);
        }
        elseif ($code >= 400 and $code < 500)
        {
            $this->handleBadRequestErrors($responseBody['error']);
        }
        else
        {
            $this->handleInternalServerErrors($responseBody['error']);
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
            ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_FAILURE,
            $error);
    }

    protected function checkAndFormatResponse($response)
    {
        // TODO. Need to confirm response format before implementing it.
    }
}
