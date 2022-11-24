<?php

namespace RZP\Gateway\Base;

use App;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;

abstract class Terminal
{
    //actions
    const MERCHANT_ONBOARD = 'merchantOnboard';

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 60;

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Trace instance for tracing
     * @var Trace\Trace
     */
    protected $trace;

    /**
     * The state in which the api is operating
     * that is live/test
     * @var string
     */
    protected $mode;

    protected $requestType;

    /**
     * @var string
     */
    protected $action;

    abstract protected function getOnboardRequestArray(array $input, array $merchantDetails);

    abstract protected function parseOnboardResponse($input, $response);

    abstract protected function getInputValidationRules();

    abstract protected function getMerchantDetailsValidationRules();

    function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->env = $this->app['env'];

        $this->requestType = 'onboard';
    }

    public function setTerminalParams(string $action)
    {
        $this->action = $action;

        return $this;
    }

    protected function validateMerchantOnboard(array $input, array $merchantDetails)
    {
        $validator  = (new JitValidator())->caller($this);

        $validator->setStrictFalse();

        $rules = $this->getInputValidationRules();
        $validator->input($input)->rules($rules)->validate();

        $rules = $this->getMerchantDetailsValidationRules();
        $validator->input($merchantDetails)->rules($rules)->validate();
    }

    public function merchantOnboard(array $input, array $merchantDetails)
    {
        $this->validateMerchantOnboard($input, $merchantDetails);

        $request = $this->getOnboardRequestArray($input, $merchantDetails);

        $this->traceGatewayOnboard($request,TraceCode::GATEWAY_ONBOARD_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $response = $this->getArrayFromResponse($response);

        $this->traceGatewayOnboard($response, TraceCode::GATEWAY_ONBOARD_RESPONSE);

        return $this->parseOnboardResponse($input, $response);
    }

    protected function getArrayFromResponse($response)
    {
        $body = $response->body;

        return $this->parseResponseBody($body);
    }

    protected function parseResponseBody(string $body)
    {
        $responseArray = $this->jsonToArray($body);

        return $responseArray;
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = array(
            'url'       => $this->getUrl(),
            'method'    => $method,
            'content'   => $content,
        );

        return $request;
    }

    protected function sendGatewayRequest($request)
    {
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
        }

        if (isset($request['headers']) === false)
        {
            $request['headers'] = [];
        }

        $method = 'post';

        if (isset($request['method']))
        {
            $method = $request['method'];
        }

        if (isset($request['options']['timeout']) === false)
        {
            $request['options']['timeout'] = static::TIMEOUT;
        }

        try
        {
            $method = strtoupper($method);

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $method,
                $request['options']);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw new Exception\GatewayRequestException($e->getMessage(), $e);
            }
        }

        $this->validateResponse($response);

        return $response;
    }

    protected function validateResponse($response)
    {
        if (in_array($response->status_code, [503, 504], true) === true)
        {
            throw new Exception\GatewayTimeoutException('Response status: '. $response->status_code);
        }
        else if ($response->status_code >= 500)
        {
            $e = new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR);

            $data = ['status_code' => $response->status_code, 'body' => $response->body];
            $e->setData($data);

            throw $e;
        }
        else if ($response->status_code >= 300)
        {
            //
            // Trace non 200 status codes to figure out what else
            // needs to be handled here later.
            //

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_RESPONSE,
                [
                    'status_code' => $response->status_code,
                ]);
        }
    }

    final protected function traceGatewayOnboard(array $content, $traceCode)
    {
        $this->trace->info(
            $traceCode,
            [
                'content'     => $content,
            ]);
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
                    TraceCode::GATEWAY_PAYMENT_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function arrayToJson($array)
    {
        $encodedJson = json_encode($array, true);

        $error = json_last_error();

        switch ($error)
        {
            case JSON_ERROR_NONE:
                return $encodedJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::GATEWAY_PAYMENT_ERROR,
                    ['array' => $array,
                     'error' => $error,
                    ]);

                throw new Exception\RuntimeException(
                    'Failed to convert array to json',
                    ['array' => $array],
                    null,
                    ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_ARRAY_TO_JSON
                    );
        }
    }
}
