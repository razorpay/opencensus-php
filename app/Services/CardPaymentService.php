<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use Requests_Session;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Models\Payment;
use RZP\Error\ErrorClass;
use RZP\Gateway\Base\Action;
use RZP\Reconciliator\Base\InfoCode;

class CardPaymentService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const APPLICATION_JSON         = 'application/json';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER   = 'X-Razorpay-Mode';
    const X_REQUEST_ID             = 'X-Request-ID';
    const X_RZP_TESTCASE_ID        = 'X-RZP-TESTCASE-ID';

    const REQUEST_TIMEOUT = 75; // Seconds
    const MAX_RETRY_COUNT = 1;

    // request and response fields
    const GATEWAY   = 'gateway';
    const ACTION    = 'action';
    const INPUT     = 'input';
    const DATA      = 'data';
    const ERROR     = 'error';
    const AUTHORIZE = 'authorize';

    // admin path
    const ADMIN_PATH = 'admin/entities/';

    protected $baseUrl;
    protected $config;
    //protected $mozartConfig;
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

        $this->config = $app['config']->get('applications.card_payment_service');

        //$this->mozartConfig = $app['config']->get('gateway.mozart');

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

    public function fetchAuthorizationData(array $input)
    {
        $request = [
            'url'     => $this->getBaseUrl() . 'entities/authorization',
            'method'  => 'POST',
            'content' => $input,
            'headers' => [
                'task_id'       => $this->app['request']->getTaskId(),
                'request_id'    => $this->app['request']->getId(),
            ],
        ];

        $response = $this->sendRawRequest($request);

        return $this->jsonToArray($response->body);
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

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();

            //$input[Entity::TERMINAL] = $this->updateTerminalFromConfig($input);
        }

        if ($this->action === Action::AUTHORIZE)
        {
            $input[self::GATEWAY]['features']['tpv'] = $input[Entity::MERCHANT]->isTPVRequired();
        }

        $content = [
            self::ACTION  => $action,
            self::GATEWAY => $gateway,
            self::INPUT   => $input
        ];

        $response = $this->sendRequest('POST', 'action/' . $action, $content);

        return $response;
    }


    public function authorizeAcrossTerminals(Payment\Entity $payment, array $gatewayInput, array $terminals)
    {
        $input = [];

        $input = $gatewayInput;

        $input['terminals'] = [];

        foreach ($terminals as $terminal)
        {
            $terminalInput = [];

            $terminalInput = $terminal->toArrayWithPassword();


            if ((empty($input['authentication_terminals']) === false) and
                (empty($input['authentication_terminals'][$terminal->getId()]) === false))
            {
                $terminalInput['auth'] = $input['authentication_terminals'][$terminal->getId()];
            }

            $input['terminals'][] = $terminalInput;
        }

        unset($input['authentication_terminals']);

        $content = [
            self::INPUT   => $input
        ];


        $response = $this->sendRequest('POST', self::AUTHORIZE , $content);

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

        if ($this->app->environment('production') === false)
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $request['headers'][self::X_RZP_TESTCASE_ID] = $testCaseId;
            }
        }

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        $response = $this->processResponse($response, $method);

        $this->traceResponse($response);

        return $response;
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


        if (empty($request['content'][self::INPUT]['terminals']) === false)
        {
            foreach ($request['content'][self::INPUT]['terminals'] as $index => $terminal)
            {
                unset($request['content'][self::INPUT]['terminals'][$index][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]);
                unset($request['content'][self::INPUT]['terminals'][$index][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2]);
                unset($request['content'][self::INPUT]['terminals'][$index][Terminal\Entity::GATEWAY_SECURE_SECRET]);
                unset($request['content'][self::INPUT]['terminals'][$index][Terminal\Entity::GATEWAY_SECURE_SECRET2]);
            }
        }

        $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_REQUEST, $request);
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
            catch(\Requests_Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::CARD_PAYMENT_SERVICE_RETRY,
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

    protected function processResponse($response, $method)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);
        $responseBody['success'] = false;

        if ($this->isSuccessResponse($code, $responseBody))
        {
            $responseBody['success'] = true;
            if ($this->action === Action::VERIFY)
            {
                return $this->processVerifyResponse($responseBody);
            }
        }

        return $responseBody;
    }

    protected function traceResponse($response)
    {
       $traceResponse = $response;

       // For axis_migs we don't send gateway request in redirect case,
       // We redirect customer with actual request content which has card and terminal details,
       // Unsetting these fields before logging is mandatory
       unset($traceResponse['data']['content']['vpc_CardNum']);
       unset($traceResponse['data']['content']['vpc_AccessCode']);
       unset($traceResponse['data']['content']['vpc_CardExp']);
       unset($traceResponse['data']['content']['vpc_CardSecurityCode']);

        $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_RESPONSE, $traceResponse ?? []);
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
                    TraceCode::CARD_PAYMENT_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function isSuccessResponse($code, $responseBody)
    {
        if ($code === 200)
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
        $actualAmount   =  $verify->verifyResponseContent['amount'];

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    // ----------------------- Error ---------------------------------------------

    public function checkForErrors($response)
    {
        if ((empty($response['success']) === false) and
            ($response['success'] === true))
        {
            return;
        }

        if (empty($response[self::ERROR]) === true)
        {
            return;
        }

        $errorCode = $response[self::ERROR]['internal_error_code'];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($response[self::ERROR], $response);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($response[self::ERROR], $response);
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

    protected function handleGatewayErrors(array $error, array $response)
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

    protected function handleBadRequestErrors(array $error, array $response)
    {
        $errorCode = $error['internal_error_code'];

        $data = $response['data'] ?? null;

        $description = $error['description'] ?? null;

        if ($errorCode == ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
        }

        if ($errorCode == ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                null,
                $data
            );
        }

        if (empty($error['gateway_error_code']) === false)
        {
            $this->handleGatewayErrors($error, $response);
        }
        else if ($errorCode !== '')
        {
            throw new Exception\BadRequestException($errorCode);
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

        $description = $error['description'] ?? 'card payment service request failed';

        throw new Exception\LogicException(
            $description,
            $code,
            $data);
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }
}
