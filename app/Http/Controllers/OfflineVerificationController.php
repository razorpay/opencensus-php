<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests as RzpRequest;

class OfflineVerificationController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    protected function handleAny($path = null)
    {
        $request = Request::instance();

        $url =  $path;

        $method = $request->method();

        $body = $this->getRequestBody($request);

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        $this->trace->info(TraceCode::OFFLINE_VERIFICATION_SERVICE_PROXY_REQUEST, [
            'request'      => $url,
            'method'       => $method,
            'body'         => $body,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $method, $body);

        return $response;
    }

    protected function handleWebhook()
    {
        $request = Request::instance();

        $url = 'v1/ecom/update_status';

        $method = $request->method();

        $body = $this->getRequestBody($request);

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        $this->trace->info(TraceCode::OFFLINE_VERIFICATION_SERVICE_PROXY_REQUEST, [
            'request'      => $url,
            'method'       => $method,
            'body'         => $body,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $method, $body);

        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        string $method,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config = config('applications.offline_verification');

        $baseUrl = $config['url'];

        $username = $config['username'];

        $password = $config['secret'];

        $timeout = $config['timeout'];

        $defaultHeaders = [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Service-ID'      => $username,
        ];

        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => [$username, $password],
        ];

        try
        {
            $response = RzpRequest::request(
                 $baseUrl . $url,
                $defaultHeaders,
                empty($body)? []: json_encode($body),
                $method,
                $defaultOptions);
        }
        catch (\Requests_Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::SERVER_ERROR_OFFLINE_VERIFICATION_SERVICE_TIMEOUT :
                ErrorCode::SERVER_ERROR_OFFLINE_VERIFICATION_SERVICE_FAILURE;

            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e);
        }

        return $this->parseResponse($response);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }


    protected function parseResponse($response)
    {
        $code = $response->status_code;

        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::OFFLINE_VERIFICATION_SERVICE_PROXY_RESPONSE, [
            'code' => $code,
            'body' => $body,
        ]);

        if ($body['success'] === false)
        {
            throw new Exception\BadRequestException($body['error']['public_error']['code'], null, null,
                                                        $body['error']['internal_error']['message']);
        }

        if (array_key_exists('data', $body) === true)
        {
            $body = $body['data'];
        }

        return ApiResponse::json($body, $code);
    }

    protected function getRequestBody(\Illuminate\Http\Request $request)
    {
        if ($request->post() === null)
        {
            return [];
        }

        $queryStringArray = $this->getQueryStringAsArray($request->getQueryString());

        return array_diff_assoc($request->post(), $queryStringArray);
    }

    protected function getQueryStringAsArray(string $queryString = null): array
    {
        $queryStringArray = [];

        if ($queryString === null)
        {
            return $queryStringArray;
        }

        $queryStringChunks = explode('&', $queryString);

        foreach ($queryStringChunks as $queryChunk)
        {
            $queryChunk = urldecode($queryChunk);

            if (str_contains($queryChunk, '=') === true)
            {
                $queryStringArray[str_before($queryChunk, '=')] = str_after($queryChunk, '=');
            }
            else
            {
                $queryStringArray[$queryChunk] = '';
            }
        }

        return $queryStringArray;
    }
}
