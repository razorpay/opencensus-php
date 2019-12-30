<?php

namespace RZP\Services\NbPlus;

use App;
use RZP\Exception;
use Requests_Session;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Error\ErrorClass;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class Service
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const APPLICATION_JSON         = 'application/json';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER   = 'X-Razorpay-Mode';
    const X_REQUEST_ID             = 'X-Request-ID';

    const REQUEST_TIMEOUT = 75; // Seconds
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const GATEWAY     = 'gateway';
    const ACTION      = 'action';
    const INPUT       = 'input';
    const METHOD_DATA = 'method_data';
    const DATA        = 'data';
    const ACQUIRER    = 'acquirer';
    const ERROR       = 'error';
    const RESPONSE    = 'response';

    // Supported Actions
    const AUTHORIZE        = 'authorize';
    const CALLBACK         = 'callback';
    const VERIFY           = 'verify';
    const AUTHORIZE_FAILED = 'authorize_failed';

    const SUPPORTED_ACTIONS = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::VERIFY,
        self::AUTHORIZE_FAILED
    ];

    const GATEWAY_TO_METHOD_MAP = [
      Payment\Gateway::ATOM => Payment\Method::NETBANKING
    ];

    protected $baseUrl;
    protected $config;
    protected $trace;
    protected $request;
    protected $action;
    protected $gateway;
    protected $input;
    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.nbplus_payment_service');

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

    protected function getBaseUrl(): string
    {
        $mode = $this->app['rzp.mode'];

        $url = $this->config['url'][$mode];

        // TODO how to determine versioning? Defaulted to v1 for now
        return $url . 'v1/';
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

    public function action(string $gateway, string $action, array $input)
    {
        $driver = $this->getDriver($gateway);

        return $driver->action($gateway, $action, $input);
    }

    public function sendRequest(string $method, string $url, array $data = [])
    {
        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
                self::X_REQUEST_ID             => $this->app['request']->getId(),
            ],
        ];

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        list($response, $code) = $this->processResponse($response, $method);

        $this->checkForErrors($response, $code);

        $this->traceResponse($response['response']);

        if ($this->action === self::AUTHORIZE)
        {
            return $response[Response::RESPONSE]['data']['next']['redirect'];
        }

        if ($this->action === self::CALLBACK)
        {
            return $this->getCallbackResponseData($response['response']);
        }

        return $response[Response::RESPONSE][self::DATA];
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);
        unset($request['content'][self::INPUT]['gateway_config']);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET2]);

        $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_REQUEST, $request);
    }

    protected function sendRawRequest($request)
    {
        try
        {
            $content = $request['content'];

            if ($request['method'] === 'POST')
            {
                $content = json_encode($request['content']);
            }

            $response = $this->request->request(
                $request['url'],
                $request['headers'],
                $content,
                $request['method']);

        }
        catch(\Requests_Exception $e)
        {
            $this->trace->traceException($e);

            $this->throwServiceErrorException($e);
        }

        return $response;
    }

    protected function traceResponse($response)
    {
        $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_RESPONSE, $response ?? []);
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
                    TraceCode::NBPLUS_PAYMENT_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function isSuccessResponse($code, $responseBody)
    {
        if (($code === 200) and (empty($responseBody[self::ERROR]) === true))
        {
            return true;
        }

        return false;
    }

    // ----------------------- Verify ---------------------------------------------

    protected function verifyPayment($response)
    {
        $verify = new Verify($this->gateway, []);

        $verify->verifyResponseContent = $response[self::DATA];

        $verify->status = VerifyResult::STATUS_MATCH;

        $this->checkGatewaySuccess($verify);

        $this->checkApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        if (($verify->match === true) and
            ($verify->apiSuccess === false))
        {
            return $verify;
        }

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify;
    }

    // ----------------------- Error ---------------------------------------------

    public function checkForErrors($response, $code)
    {
        if (empty($response) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        if ($code === 200)
        {
            return;
        }

        $error = $response[self::ERROR];

        if ($error[Error::CODE] !== Error::GATEWAY)
        {
            $this->handleInternalServerErrors($response[self::ERROR]);
        }

        $errorCode = $response[self::ERROR]['cause'];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($errorCode);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($errorCode);
                break;

            case ErrorClass::SERVER:
                $this->handleInternalServerErrors($errorCode);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                    ['errorClass' => $class]);
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

    protected function handleGatewayErrors(array $errorCode)
    {
        switch ($errorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($errorCode);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($errorCode);

            default:
                throw new Exception\GatewayErrorException($errorCode);
        }
    }

    protected function handleBadRequestErrors(array $errorCode)
    {

        if ($errorCode === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
        }

        $this->handleGatewayErrors($errorCode);
    }

    protected function handleInternalServerErrors(array $code)
    {

        throw new Exception\LogicException(null, $code);
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_NBPLUS_PAYMENT_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_NBPLUS_PAYMENT_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function getDriver($gateway)
    {
        $method = self::GATEWAY_TO_METHOD_MAP[$gateway];

        switch ($method)
        {
            case 'netbanking':
                $class = new Netbanking();
                break;
            default:
                throw new Exception\LogicException('Should not have reached here');
        }

        return $class;
    }
}
