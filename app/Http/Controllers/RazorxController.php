<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use Requests;
use Route;
use RZP\Trace\TraceCode;

class RazorxController extends Controller
{
    const REQUEST_TIMEOUT = 5;

    const CONTENT_TYPE_JSON = 'application/json';

    protected $razorxConfig;

    protected $baseUrl;

    protected $key,$secret;

    public function __construct()
    {
        parent::__construct();

        $this->razorxConfig  = $this->config->get('applications.razorx');
        $this->baseUrl = $this->razorxConfig['url'];
        $this->key     = $this->razorxConfig['username'];
        $this->secret  = $this->razorxConfig['secret'];
    }

    public function sendRequest()
    {
        $path = Request::input('service_path');

        $requestParams = $this->getRequestParams($path);

        try
        {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);
        }
        catch(\Throwable $e)
        {
            $this->trace->error(TraceCode::RAZORX_REQUEST_FAILED, ['error' => 'Server error occurred']);

            return ApiResponse::json(["error_message" => $e->getMessage()]);
        }

        $result = json_decode($response->body, true);

        if (empty($result) === true)
        {
            $result = $response->body;
        }

        $razorxResponse = [
            "status_code" => $response->status_code,
            "response"    => $result,
        ];

        return ApiResponse::json($razorxResponse);
    }

    protected function getRequestParams($path)
    {
        $url = $this->baseUrl . $path;

        $parameters = Request::all();

        unset($parameters['service_path']);

        $method = Request::method();

        if ((Request::header('content_type') === self::CONTENT_TYPE_JSON) and
            ($method != Requests::GET and $method != Requests::HEAD))
        {
            $parameters = json_encode($parameters);
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::RAZORX_REQUEST, ['url' => $url, 'paramters' => $parameters]);

        $response = [
            'url'     => $url,
            'headers' => [],
            'data'    => $parameters,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }
}
