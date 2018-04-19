<?php

namespace RZP\Http\Controllers;

use Route;
use ApiResponse;
use Request;
use Requests;
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
        $path = Request::get('url');

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

        return ApiResponse::json(json_decode($response->body, true));
    }

    protected function getRequestParams($path)
    {
        $url = $this->baseUrl ."/feature_flags/" . $path;

        $parameters = Request::all();

        $method = Request::method();

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::RAZORX_REQUEST, ['url' => $url, 'paramters' => $parameters]);

        $response = [
            'url' => $url,
            'headers' => [],
            'data' => $parameters,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }
}
