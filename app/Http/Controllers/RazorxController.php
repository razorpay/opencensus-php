<?php

namespace RZP\Http\Controllers;

use Request;
use Requests;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RazorxController extends Controller
{
    const REQUEST_TIMEOUT = 5;

    const CONTENT_TYPE_JSON = 'application/json';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /*
     * @var string
     */
    protected $secret;

    const READ_METHODS = [
        Requests::GET,
        Requests::HEAD,
    ];

    public function __construct()
    {
        parent::__construct();

        $razorxConfig  = $this->config->get('applications.razorx');
        $this->baseUrl = $razorxConfig['url'];
        $this->key     = $razorxConfig['username'];
        $this->secret  = $razorxConfig['secret'];
    }

    public function sendRequest()
    {
        $requestParams = $this->getRequestParams();

        try
        {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            $res = $this->parseAndReturnResponse($response);

            return ApiResponse::json($res);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_RAZORX_FAILURE
            );
        }
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'Malformed json response');
        }

        $razorxResponse = [
            'status_code' => $code,
            'response'    => $res,
        ];

        return $razorxResponse;
    }

    protected function getRequestParams()
    {
        $path = $this->validateAndGetServicePathParam();

        $url = $this->baseUrl . $path;

        $method = Request::method();

        $headers = [];

        $parameters = [];

        if (in_array($method, self::READ_METHODS, true) === false)
        {
            $parameters = Request::all();

            unset($parameters['service_path']);

            $parameters = json_encode($parameters);

            $headers['content_type'] = self::CONTENT_TYPE_JSON;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::RAZORX_REQUEST, ['url' => $url, 'parameters' => $parameters]);

        $response = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $parameters,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }

    protected function validateAndGetServicePathParam(): string
    {
        $path = Request::get('service_path');

        if (empty($path) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Valid path parameter required');
        }

        return $path;
    }
}
