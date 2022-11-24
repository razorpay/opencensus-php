<?php

namespace RZP\Http\Controllers;

use GuzzleHttp\Client;
use Request;
use ApiResponse;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests as RzpRequest;

class FinancialDataServiceController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    protected function handleAny($path = null)
    {
        $request = Request::instance();

        $url = 'v1/' . $path;

        $method = $request->method();
        $body   = $request->all();

        if ($method === self::GET)
        {
            $url  .= '?' . http_build_query($body, '', '&');
            $body = [];
        }

        if ($path === 'statements/banking/xml/parse_statement')
        {
            return $this->xmlParse($url, $body, $method);
        }

        $this->trace->info(TraceCode::FINANCIAL_DATA_SERVICE_PROXY_REQUEST, [
            'request' => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $method, $body);

        return $response;
    }

    protected function xmlParse(string $url, array $body, string $method)
    {
        $request = Request::instance();

        $this->trace->info(TraceCode::FINANCIAL_DATA_SERVICE_PROXY_REQUEST, [
            'request' => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $multipartBody = [
            [
                'contents' => file_get_contents($request->file('file')),
                'filename' => $request->file('file')->getClientOriginalName(),
                'name'     => 'file',
                'headers'  => [
                    'Content-Type' => 'application/xml',
                ],
            ],
            [
                'contents' => $body['entity_type'],
                'name'     => 'entity_type'
            ],
            [
                'contents' => $body['entity_id'],
                'name'     => 'entity_id'
            ]
        ];

        $response = $this->sendMultiPartRequestAndParseResponse($url, $method, $multipartBody);

        return $response;
    }

    protected function handlePerfiosWebhook()
    {
        $request = Request::instance();

        $url    = 'v1/statements/banking/perfios/update_status';
        $method = $request->method();
        $body   = $request->all();

        if ($method === self::GET)
        {
            $url  .= '?' . http_build_query($body, '', '&');
            $body = [];
        }

        $this->trace->info(TraceCode::FINANCIAL_DATA_SERVICE_PROXY_REQUEST, [
            'request' => $url,
            'method'  => $method,
            'body'    => $body,
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
        $config = config('applications.financial_data_service');

        $baseUrl = $config['url'];

        $username = $config['username'];

        $password = $config['secret'];

        $timeout = $config['timeout'];

        $headers = [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Service-ID'      => $username,
        ];

        $auth = [$username, $password];

        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => $auth,
        ];

        try
        {
            $response = RzpRequest::request(
                $baseUrl . $url,
                $headers,
                empty($body) ? [] : json_encode($body),
                $method,
                $defaultOptions
            );
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::SERVER_ERROR_FINANCIAL_DATA_SERVICE_TIMEOUT :
                ErrorCode::SERVER_ERROR_FINANCIAL_DATA_SERVICE_FAILURE;

            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e
            );
        }

        return $this->parseResponse($response);
    }

    protected function sendMultiPartRequestAndParseResponse(
        string $url,
        string $method,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config = config('applications.financial_data_service');

        $baseUrl = $config['url'];

        $username = $config['username'];

        $password = $config['secret'];

        $timeout = $config['timeout'];

        $headers = [
            'Accept'            => 'application/json',
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Service-ID'      => $username,
        ];

        $auth = [$username, $password];

        try
        {
            $client   = new Client(['base_uri' => $baseUrl, 'connect_timeout' => $timeout]);
            $response = $client->request('POST', $url, [
                'auth'      => $auth,
                'multipart' => $body,
            ]);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::SERVER_ERROR_FINANCIAL_DATA_SERVICE_TIMEOUT :
                ErrorCode::SERVER_ERROR_FINANCIAL_DATA_SERVICE_FAILURE;

            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e
            );
        }

        return json_decode($response->getBody(), true);
    }

    protected function hasRequestTimedOut(\WpOrg\Requests\Exception $e): bool
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

        if ($body['status'] === 'failure')
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
}
