<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use \WpOrg\Requests\Session as Requests_Session;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;

class CorePaymentService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER   = 'X-Razorpay-Mode';
    const X_REQUEST_ID             = 'X-Request-ID';
    const APPLICATION_JSON         = 'application/json';

    const REQUEST_TIMEOUT = 75; // Seconds
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const GATEWAY   = 'gateway';
    const ACTION    = 'action';
    const INPUT     = 'input';
    const DATA      = 'data';
    const ERROR     = 'error';

    protected $baseUrl;

    protected $config;

    protected $mozartConfig;

    protected $trace;

    protected $request;

    protected $action;

    protected $gateway;

    protected $input;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.cps');

        $this->mozartConfig = $app['config']->get('gateway.mozart');

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
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();

            $input[Entity::TERMINAL] = $this->updateTerminalFromConfig($input);
        }

        if ($this->action === Action::AUTHORIZE)
        {
            $input[self::GATEWAY]['features']['tpv'] = $input[Entity::MERCHANT]->isTPVRequired();
        }

        if (empty($input[Entity::UPI]) === false &&
            empty($input['upi']['expiry_time']) === false)
        {
            $input['upi']['expiry_time'] = (float)$input['upi']['expiry_time'];
        }


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
                self::X_REQUEST_ID             => $this->app['request']->getId(),
            ],
        ];

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        $response = $this->processResponse($response);

        $this->traceResponse($response);

        return $response;
    }

    public function syncCron(array $data = [])
    {
        $count = $data['count'] ?? 100;

        $request = [
            'url'     => 'sync',
            'method'  => 'POST',
            'content' => [
                'count'       => intval($count),
                'payment_ids' => $data['payment_ids'] ?? [],
            ],
            'headers' => [
                self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
                self::X_REQUEST_ID             => $this->app['request']->getId(),
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

                break;
            }
            catch(\WpOrg\Requests\Exception $e)
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
        unset($request['content'][self::INPUT]['card']);
        unset($request['content'][self::INPUT]['gateway_config']);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET]);
        unset($request['content'][self::INPUT][Entity::TERMINAL][Terminal\Entity::GATEWAY_SECURE_SECRET2]);

        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_REQUEST, $request);
    }

    protected function traceResponse($response)
    {
        $this->trace->info(TraceCode::CORE_PAYMENT_SERVICE_RESPONSE, $response ?? []);
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

        if ($this->isSuccessResponse($code, $responseBody))
        {
            if ($this->action === Action::VERIFY)
            {
                return $this->processVerifyResponse($responseBody);
            }

            return $responseBody[self::DATA];
        }
        else
        {
            $this->checkForErrors($responseBody);
        }
    }

    protected function processVerifyResponse($responseBody)
    {
        $errorCode = $responseBody[self::ERROR]['internal_error_code'] ?? null;

        $verify = $this->getVerifyObject($responseBody[self::DATA]);

        if ($errorCode === ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }
        else if ($errorCode === ErrorCode::SERVER_ERROR_RUNTIME_ERROR)
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id' => $this->input[Entity::PAYMENT]['id'],
                    'gateway'    => $this->gateway
                ]
            );
        }
        else if ($errorCode !== null)
        {
            throw new Exception\GatewayErrorException(
                $errorCode,
                $responseBody[self::ERROR]['gateway_error_code'] ?? '0',
                $responseBody[self::ERROR]['gateway_error_description'] ?? 'Verify - Payment failed',
                [],
                null,
                $this->action);
        }

        return $responseBody[self::DATA];
    }

    protected function getVerifyObject(array $attributes)
    {
        $verify = new Verify($this->gateway, []);

        $verify->apiSuccess = $attributes['apiSuccess'];

        $verify->gatewaySuccess = $attributes['gatewaySuccess'];

        $verify->amountMismatch = $attributes['amountMismatch'];

        $verify->status = $attributes['status'];

        return $verify;
    }

    protected function isSuccessResponse($code, $responseBody)
    {
        if ($code === 200)
        {
            if ((empty($responseBody[self::ERROR]) === true) or
                ($this->action === Action::VERIFY))
            {
                return true;
            }
        }

        return false;
    }

    protected function handleBadRequestErrors(array $error)
    {
        $errorCode = $error['internal_error_code'];

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? null;

        if ($errorCode == ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
        }

        if (empty($error['gateway_error_code']) === false)
        {
            $this->handleGatewayErrors($error);
        }
        else
        {
            throw new Exception\LogicException(
                $description,
                $errorCode,
                $data);
        }
    }

    protected function handleInternalServerErrors(array $error)
    {
        $code = $error['internal_error_code'];

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? 'core payment service request failed';

        throw new Exception\LogicException(
            $description,
            $code,
            $data);
    }

    protected function handleGatewayErrors(array $error)
    {
        $errorCode = $error['internal_error_code'];

        $gatewayErrorCode = $error['gateway_error_code'] ?? null;

        $gatewayErrorDesc = $error['gateway_error_description'] ?? null;

        switch ($errorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($errorCode);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($errorCode);

            default:
                throw new Exception\GatewayErrorException($errorCode,
                                                          $gatewayErrorCode,
                                                          $gatewayErrorDesc);
        }
    }

    protected function checkForErrors($response)
    {
        $errorCode = $response[self::ERROR]['internal_error_code'];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($response[self::ERROR]);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($response[self::ERROR]);
                break;

            case ErrorClass::SERVER:
                $this->handleInternalServerErrors($response[self::ERROR]);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                ['errorClass' => $class]);
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

    protected function updateTerminalFromConfig($input)
    {
        if (isset($input['payment']['gateway']) === true)
        {
            switch ($input['payment']['gateway']) {
                case Payment\Gateway::NETBANKING_CUB:
                    $input['terminal']['gateway_secure_secret'] = $this->mozartConfig['netbanking_cub']['gateway_secure_secret'];
                    $input['terminal']['gateway_secure_secret2'] = $this->mozartConfig['netbanking_cub']['gateway_secure_secret2'];
                    $input['terminal']['gateway_terminal_password'] = $this->mozartConfig['netbanking_cub']['gateway_terminal_password'];
                    $input['terminal']['gateway_terminal_password2'] = $this->mozartConfig['netbanking_cub']['gateway_terminal_password2'];

                    break;
                case Payment\Gateway::NETBANKING_YESB:
                    $input['terminal']['gateway_secure_secret'] = $this->mozartConfig['netbanking_yesb']['gateway_secure_secret'];
            }
        }

        return $input['terminal'];
    }
}
