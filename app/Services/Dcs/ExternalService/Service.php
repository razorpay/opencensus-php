<?php

namespace RZP\Services\Dcs\ExternalService;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Service
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const APPLICATION_JSON         = 'application/json';
    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER   = 'X-Razorpay-Mode';
    const X_MODE_HEADER            = 'X-Mode';
    const X_REQUEST_ID             = 'X-Request-ID';
    const X_RAZORPAY_TRACKID       = 'X-Razorpay-TrackId';
    const X_RZP_TESTCASE_ID        = 'X-RZP-TESTCASE-ID';
    const RZPCTX_MERCHANT_ID       = 'RZPCTX-MERCHANT-ID';

    const REQUEST_TIMEOUT = 75; // Seconds
    const MAX_RETRY_COUNT = 1;
    const DCS_BULK_LIMIT  = 500;

    protected $baseUrl;
    protected $config;
    protected $trace;
    protected $request;
    protected $service;
    protected $mode;
    protected $name;
    protected $input;
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.dcs_service_integrations');
    }

    public function sendRequest(array $input)
    {
        $request = [
            'url'     => $this->getBaseUrl() . '/v1/dcs/config/set',
            'method'  => 'POST',
            'content' => $input,
            'headers' => $this->getDefaultHeaders()
        ];

        $response = $this->sendRawRequest($request);

        return $this->processResponse($response, 'POST');
    }

    protected function getBaseUrl(): string
    {
        $mode = $this->mode;
        $service = $this->service;
        return $this->config[$service][$mode]['url'];
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    protected function getDefaultOptions($service, $mode = 'live'): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->config[$service][$mode]['username'],
                $this->config[$service][$mode]['password']
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
            self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            self::X_REQUEST_ID             => $this->app['request']->getId(),
            self::X_RAZORPAY_TRACKID       => $this->app['req.context']->getTrackId(),
            self::X_MODE_HEADER            => $this->mode
        ];

        return $headers;
    }

    public function action(string $service, array $input, string $mode = Mode::TEST)
    {
        $this->service = $service;
        $this->mode = $mode;
        $this->input = $input;

        try
        {
            return $this->sendRequest($input);
        }
        catch (\Throwable $ex)
        {
            $this->throwServiceErrorException($ex);
        }
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

                $response = Requests::request(
                    $request['url'],
                    $request['headers'],
                    $content,
                    $request['method'],
                    $this->getDefaultOptions($this->service, $this->mode));

                return $response;
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::DCS_SERVICE_RETRY,
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
    }

    protected function processResponse($response, $method)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);
        $responseBody['status_code'] = $code;

        if ($this->isSuccessResponse($code, $responseBody) === false)
        {
            $responseBody['success'] = false;
        }

        return $responseBody;
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
                    TraceCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function isSuccessResponse($code, $responseBody)
    {
        if (($code === 200 || $code === 204) &&
            (key_exists('success', $responseBody) && ($responseBody['success'] === true)))
        {
            return true;
        }

        return false;
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_DCS_EXTERNAL_SERVICE_FAILURE;

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    public function buildExternalRequest(string $key, string $entity_id, array $fieldValues, string $mode): array
    {
        $request = [];
        $request['key'] = $key;
        if($mode === Mode::LIVE)
        {
            $request['live_mode'] = true;
        }

        $request['entity_id'] = $entity_id;
        $request['value'] = json_encode($fieldValues);
        $request['mode'] = $mode;
        // audit_log is right now default
        //@TODO Audit logs should be from dashboard right now this is default values
        $request['audit_log']['change_by'] = 'api@razorpay.com';
        $request['audit_log']['change_reason'] = 'api proxy request';
        $request['audit_log']['change_approved_by'] = 'api@razorpay.com';

        return $request;
    }

    public function handleResponse($response)
    {
        if (key_exists('success', $response) === false || $response['success'] !== true)
        {
            $this->trace->info(TraceCode::DCS_EXTERNAL_REQUEST_FAILED,
                [
                    'response' => $response,
                    'successKeyExists' => key_exists('success', $response),
                ]);

            $description = ($response['error'] !== null && $response['error']['description'] !== null) ?
                $response['error']['description']: "Error in DCS Client Service Request";
            $ex = new Exception\ServerErrorException($description,
                ErrorCode::SERVER_ERROR_DCS_CLIENT_REQUEST_FAILURE, // TODO add it in error module repo
                "failure response from external service");

            $this->trace->traceException($ex);
            throw $ex;
        }
        return $response;
    }
}
