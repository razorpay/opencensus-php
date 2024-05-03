<?php

namespace RZP\Services;

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

class EmandateService
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

    const DATA = 'data';

    // admin path
    const ADMIN_PATH = 'admin/entities/';

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

        $this->config = $app['config']->get('applications.emandate_service');

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

        $url = $this->config['url'];

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

        return $response[self::DATA];
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
            'merchant.id'                   => 'content.input.merchant.id',
            'terminal.id'                   => 'content.input.terminal.id',
            'payment.customer_id'           => 'content.input.payment.customer_id'
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

        $this->trace->info(TraceCode::EMANDATE_SERVICE_REQUEST, $requestTrace);
    }

    protected function traceResponse($response)
    {
        $this->trace->info(TraceCode::EMANDATE_SERVICE_RESPONSE, $response ?? []);
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
                    TraceCode::EMANDATE_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    // ----------------------- Error ---------------------------------------------


    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_EMANDATE_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_EMANDATE_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function parseResponse($response)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);

        return [$responseBody, $code];
    }
}
