<?php

namespace RZP\Services;

use Razorpay\Trace\Logger as Trace;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class SmartCollect
{
    const CONTENT_TYPE_JSON = 'application/json';

    const PROCESS_BANK_TRANSFER = "/v1/ecollect/validate/rbl";

    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $app;

    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    const X_REQUEST_TASK_ID = 'X-Razorpay-TaskId';
    const ROUTE_NAME        = 'X-Razorpay-Route-Name';

    const DEFAULT_REQUEST_TIMEOUT = 60;

    public function __construct($app)
    {
        $this->app = $app;

        $config = $app['config']->get('applications.smart_collect');

        $this->trace = $app['trace'];

        $this->baseUrl = $config['url'];

        $this->key     = $config['username'];
        $this->secret  = $config['password'];
        $this->timeOut = $config['timeout'];
        $this->ba      = $app['basicauth'];
        $this->mock    = $config['mock'];

        $this->request = $app['request'];

    }

    public function processBankTransfer($data)
    {
        return $this->sendRequest(self::PROCESS_BANK_TRANSFER, 'POST' , $data);
    }

    public function processQrCodePayment($path, $data)
    {
        return $this->sendRequest($path, 'POST' , $data);
    }

    public function sendRequest($endPoint, $method, $input = [], $merchant = null)
    {
        $parsedResponse = [];
        try
        {
            $request = $this->generateRequest($endPoint, $method, $input);
            if ($merchant !== null)
            {
                $enabledFeatures = $merchant->getEnabledFeatures();

                $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);
            }
            $response       = $this->sendSmartCollectRequest($request);
            $parsedResponse = $this->parseAndReturnResponse($response);
            $this->trace->info(TraceCode::SMARTCOLLECT_SERVICE_RESPONSE, $parsedResponse);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SMARTCOLLECT_REDIRECT_REQUEST_FAILURE);
        }

        return $parsedResponse;

    }

    protected function generateRequest(string $endpoint, string $method, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        $this->trace->info(TraceCode::SMARTCOLLECT_SERVICE_REQUEST, [
            'url'     => $url,
            "content" => $this->getTraceRequest($endpoint, $data)
        ]);

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $this->setHeaders();

        $options = [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];

        return [
            'url'     => $url,
            'method'  => $method,
            'headers' => $this->headers,
            'options' => $options,
            'content' => $data
        ];
    }

    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]            = 'application/json';
        $headers[self::CONTENT_TYPE]      = 'application/json';
        $headers[self::X_MODE]            = $this->ba->getMode();
        $headers[self::X_REQUEST_ID]      = $this->request->getId();
        $headers[self::X_REQUEST_TASK_ID] = $this->request->getTaskId();
        $headers[self::ROUTE_NAME]        = $this->app['api.route']->getCurrentRouteName();

        $this->headers = $headers;
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $contentType  = $res->headers['content-type'];

        $res = json_decode($res->body, true);

        return ['status_code' => $code, 'body' => $res];
    }

    protected function sendSmartCollectRequest(array $request)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SMARTCOLLECT_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $response;
    }

    protected function getTraceRequest(string $endpoint, array $request)
    {
        switch ($endpoint)
        {
            case self::PROCESS_BANK_TRANSFER:
            {
                unset($request['data']['payer_account']);
                unset($request['request_payload']['Data'][0]['senderAccountNumber']);
            }
            break;

            default:
                break;
        }
        return $request;
    }
}
