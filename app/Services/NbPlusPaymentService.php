<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use Requests_Session;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Error\ErrorClass;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class NbPlusPaymentService
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
    const GATEWAY   = 'gateway';
    const ACTION    = 'action';
    const INPUT     = 'input';
    const DATA      = 'data';
    const ERROR     = 'error';

    // Supported Actions
    const AUTHORIZE        = 'authorize';
    const CALLBACK         = 'callback';
    const VERIFY           = 'verify';
    const AUTHORIZE_FAILED = 'authorize_failed';

    // admin path
    const ADMIN_PATH = 'admin/entities/';

    const SUPPORTED_ACTIONS = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::VERIFY,
        self::AUTHORIZE_FAILED
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

    public function action(string $gateway, string $action, array $input)
    {
        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if ($this->action === Action::AUTHORIZE)
        {
            $input[self::GATEWAY]['features']['tpv'] = $input[Entity::MERCHANT]->isTPVRequired();
        }

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();

            // TODO: in case secrets are stored in credstash on api
            //$input[Entity::TERMINAL] = $this->updateTerminalFromConfig($input);
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }

        $content = [
            self::ACTION  => $action,
            self::GATEWAY => $gateway,
            self::INPUT   => $input
        ];

        $response = $this->sendRequest('POST', 'action/' . $action, $content);

        return $response;
    }

    public function fetchMultiple(string $entityName, array $input)
    {
        $path = self::ADMIN_PATH . $entityName;

        return $this->sendRequest('GET', $path, $input);
    }

    public function fetch(string $entityName, string $id, $input)
    {
        $path = self::ADMIN_PATH . $entityName . '/' . $id;

        return $this->sendRequest('GET', $path, $input);
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

        $response = $this->processResponse($response, $method);

        $this->traceResponse($response);

        return $response;
    }

    protected function traceRequest(array $request)
    {
        // TODO
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

    protected function processResponse($response, $method)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);

        if ($this->isSuccessResponse($code, $responseBody))
        {
            if ($this->action === self::VERIFY)
            {
                return $this->processVerifyResponse($responseBody);
            }
        }

        return $responseBody;
    }

    protected function traceResponse($response)
    {
        // TODO : redact?
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

    protected function processVerifyResponse($response)
    {
        $verify = $this->verifyPayment($response);

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway
                ]
            );
        }

        return $verify->getDataToTrace();
    }

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

        $this->checkAmountMismatch($verify);

        return $verify;
    }

    protected function checkApiSuccess(Verify &$verify)
    {
        $verify->apiSuccess = true;

        // If payment status is either failed or created, this is an api failure
        if (($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify &$verify)
    {
        $verify->gatewaySuccess = $verify->verifyResponseContent['gateway_success'];
    }

    protected function checkAmountMismatch(Verify &$verify)
    {
        $expectedAmount = $this->input[Entity::PAYMENT][Payment\Entity::AMOUNT];
        $actualAmount   = $verify->verifyResponseContent['amount'];

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    // ----------------------- Error ---------------------------------------------

    public function checkForErrors($response)
    {
        if (empty($response[self::ERROR]) === true)
        {
            return;
        }

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

    protected function handleBadRequestErrors(array $error)
    {
        $errorCode = $error['internal_error_code'];

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? null;

        if ($errorCode === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
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

        $description = $error['description'] ?? 'nb plus service request failed';

        throw new Exception\LogicException(
            $description,
            $code,
            $data);
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
}
