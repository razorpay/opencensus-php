<?php

namespace RZP\Services\NbPlus;

use App;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Exception;
use \WpOrg\Requests\Session as Requests_Session;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorClass;
use Illuminate\Support\Arr;
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

    // admin path
    const ADMIN_PATH = 'admin/entities/';

    const NBPLUS_SUPPORTED_PAYMENT_METHODS = [
        Payment\Method::NETBANKING,
        Payment\Method::EMANDATE,
        Payment\Method::CARDLESS_EMI,
        Payment\Method::APP,
        Payment\Method::PAYLATER,
        Payment\Method::WALLET,
    ];

    protected $baseUrl;
    protected $config;
    protected $trace;
    protected $request;
    protected $action;
    protected $gateway;
    protected $input;
    protected $app;
    protected $exception;

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

        return $url . 'v1/';
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->config['username'],
                $this->config['password']
            ],
            'hooks' => $this->getRequestHooks(),
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

    public function action(string $method, string $gateway, string $action, array $input)
    {
        $driver = $this->getDriver($method);

        return $driver->action($method, $gateway, $action, $input);
    }

    public function fetchMultiple(string $entityName, array $input)
    {
        $path = self::ADMIN_PATH . $entityName;

        return $this->sendRequest('GET', $path, $input, false);
    }

    public function fetch(string $entityName, string $id, $input)
    {
        $path = self::ADMIN_PATH . $entityName . '/' . $id;

        return $this->sendRequest('GET', $path, $input, false);
    }

    public function fetchNbplusData(array $input, string $entity)
    {
        return $this->sendRequest('POST', 'entities/' . $entity, $input, false);
    }

    public function sendRequest(string $method, string $url, array $data = [], bool $shouldTraceResponse = true)
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

        list($response, $code) = $this->parseResponse($response);

        if ($shouldTraceResponse === true)
        {
            $this->traceResponse($response);
        }

        try
        {
            $this->checkForErrors($response, $code, $data);
        }
        catch (\Exception $exc)
        {
            $this->exception = $exc;

            // errors are handled in a different manner for verify related flows
            if (($exc instanceof Exception\ServerErrorException) or
                ($this->action !== Action::VERIFY and $this->action !== Action::AUTHORIZE_FAILED))
            {
                throw $exc;
            }
        }

        return $response[Response::RESPONSE];
    }

    protected function getMetaData($input)
    {
        $meta_data = [];

        if(array_key_exists('order_id', $input))
        {
            $meta_data['order_id'] = $input['order_id'];
        }
        if(isset($this->action) and $this->action !== Action::CHECK_ACCOUNT)
        {
            $meta_data['payment_id'] = $input['payment']['id'];
        }
        return $meta_data;
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
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->traceException($e);

            $this->throwServiceErrorException($e);
        }

        return $response;
    }

    protected function traceRequest(array $request)
    {
        $traceMap = [
            'payment.id'                    => 'content.input.payment.id',
            'payment.amount'                => 'content.input.payment.amount',
            'payment.status'                => 'content.input.payment.status',
            'payment.gateway'               => 'content.input.payment.gateway',
            'payment.billing_address'       => 'content.input.payment.billing_address',
            'wallet'                        => 'content.input.wallet',
            'merchant.id'                   => 'content.input.merchant.id',
            'merchant.features'             => 'content.input.merchant.features',
            'merchant.billing_label'        => 'content.input.merchant.billing_label',
            'terminal.id'                   => 'content.input.terminal.id',
            'terminal.merchant_id'          => 'content.input.terminal.merchant_id',
            'terminal.gateway_merchant_id'  => 'content.input.terminal.gateway_merchant_id',
            'terminal.gateway_merchant_id2' => 'content.input.terminal.gateway_merchant_id2',
            'terminal.gateway_terminal_id'  => 'content.input.terminal.gateway_terminal_id',
            'terminal.gateway_access_code'  => 'content.input.terminal.gateway_access_code',
            'gateway.data'                  => 'content.input.gateway',
            'gateway.callback_url'          => 'content.input.callbackUrl',
            'gateway.static_callback_data'  => 'content.input.gateway_data',
            'callback_type.s2s'             => 'content.input.s2s',
            'verification_id'               => 'content.input.verification_id'
        ];

        $requestTrace = [];

        foreach ($traceMap as $key => $srcPath)
        {
            $value = Arr::get($request, $srcPath);

            if (is_null($value) === false)
            {
                Arr::set($requestTrace, $key, $value);
            }
        }

        $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_REQUEST, $requestTrace);
    }

    protected function traceResponse($response)
    {
        unset($response[Response::RESPONSE][Response::DATA][Response::ACCOUNT_INFO]);

        $traceData = $response;

        unset($traceData[Response::RESPONSE][RESPONSE::DATA][Response::TOKEN]);

        $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_RESPONSE, $traceData ?? []);
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

    // ----------------------- Verify ---------------------------------------------

    protected function verifyPayment($response)
    {
        $verify = new Verify($this->gateway, []);

        $verify->verifyResponseContent = $response[Response::DATA];

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
            $verify->error = $this->exception->getError()->getAttributes();

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

    public function checkForErrors($response, $code, $data)
    {
        if (empty($response) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        if (($code === 200) and (empty($response[Response::ERROR]) === true))
        {
            return;
        }

        if(array_key_exists('input', $data))
        {
            $input = $data['input'];

            if (array_key_exists('method', $input) and $input['method'] === Payment\Gateway::PAYLATER and
                array_key_exists('provider', $input) and $input['provider'] === Payment\Processor\PayLater::LAZYPAY)
            {
                $response['meta_data'] = $this->getMetaData($input);
            }
        }

        $error = $response[Response::ERROR];

        if ($error[Error::CODE] !== Error::GATEWAY)
        {
            $this->handleInternalServerErrors(ErrorCode::SERVER_ERROR_NBPLUS_PAYMENT_SERVICE_FAILURE);
        }

        $errorCode = $error[Error::CAUSE][Error::MOZART_ERROR_CODE];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($errorCode, $response);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($errorCode, $response);
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

    protected function handleGatewayErrors($errorCode, array $response)
    {
        switch ($errorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($errorCode);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($errorCode);

            default:
                $data = [];

                if(isset($response['meta_data']) === true)
                {
                    $data = array_merge($response['meta_data'], $data);
                }

                $error = $response[Response::ERROR];
                $gatewayErrorCode = $error[Error::CAUSE]['gateway_error_code'] ?? null;
                $gatewayErrorDesc = $error[Error::CAUSE]['gateway_error_description'] ?? null;
                throw new Exception\GatewayErrorException($errorCode, $gatewayErrorCode, $gatewayErrorDesc, $data);
        }
    }

    protected function handleBadRequestErrors($errorCode, array $response)
    {

        if ($errorCode === ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
        }

        $this->handleGatewayErrors($errorCode, $response);
    }

    protected function handleInternalServerErrors($code)
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

    protected function getDriver($method)
    {
        Payment\Method::validateMethod($method);

        switch ($method)
        {
            case 'netbanking':
                $class = new Netbanking();
                break;
            case Payment\Method::EMANDATE:
                $class = new Emandate();
                break;
            case Payment\Method::CARDLESS_EMI:
                $class = new CardlessEmi();
                break;
            case Payment\Method::APP:
                $class = new AppMethod();
                break;
            case PAYMENT\METHOD::PAYLATER:
                $class = new Paylater();
                break;
            case PAYMENT\METHOD::WALLET:
                $class = new Wallet();
                break;
            default:
                throw new Exception\LogicException('Should not have reached here');
        }

        return $class;
    }

    protected function parseResponse($response)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);

        return [$responseBody, $code];
    }

    public static function isNbplusSupportedMethods($method)
    {
        return in_array($method, self::NBPLUS_SUPPORTED_PAYMENT_METHODS, true);
    }
}
