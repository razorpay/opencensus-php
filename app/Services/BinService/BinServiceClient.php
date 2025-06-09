<?php

namespace App\Services\BinService;

use App\Constants\Constants;
use Request;
use App\Http\Headers;
use Requests;
use App\Trace\TraceCode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;


class BinServiceClient
{
    const X_RAZORPAY_TASKID = 'grpc-metadata-X-Task-Id';

    const X_NAMESPACE = 'grpc-metadata-x-namespace';

    protected $baseUrl;

    protected $baseUrlSec;

    protected $config;

    protected $trace;

    protected $app;

    protected $request;

    protected $mode;

    private $key;
    /**
     * @var string
     */
    private $secret;

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = app();
        }

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('app.bin_service');

        $this->baseUrl = $this->config['url'];

        $this->baseUrlSec = $this->config['sec_url'];

        $this->request = $app['request'];

        $this->key = $this->config['username'];
        $this->secret = $this->config['password'];
    }

    public function sendRequest($url, $method, $data = '', $namespace = 'RZP/IN/DEBIT', $action = null)
    {
        try
        {
            $url = $this->baseUrl . $url;

            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
            $headers[self::X_NAMESPACE] = $namespace;
            $headers['Authorization'] = 'Basic ' . base64_encode($this->key . ':' . $this->secret);

            if(!empty(Request::header(Headers::DEV_SERVE_USER))){
                $headers[Headers::DEV_SERVE_USER] = Request::header(Headers::DEV_SERVE_USER);
            }

            $request = [
                'url'       => $url,
                'method'    => $method,
                'headers'   => $headers,
                'content'   => $data
            ];

            $this->trace->info(TraceCode::BIN_SERVICE_REQUEST, [
                'url'       => $request['url'],
                'action'    => $action,
                'content'   => $request['content'],
                'namespace' => $headers[self::X_NAMESPACE]
            ]);

            $response = $this->sendBinServiceRequest($request);

            $this->checkErrors($response);

            return json_decode($response->body, true);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::BIN_SERVICE_ERROR,
                [
                    'error_message' => $e->getMessage(),
                ]
            );

            // Log the error and continue with the flow.
            return null;
        }
    }

    protected function sendBinServiceRequest($request)
    {
        $method = $request['method'];

        switch($method)
        {
            case Requests::GET:
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    []);
                break;
            case Requests::PUT:
            case Requests::PATCH:
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    json_encode($request['content']));
                break;
        }

        return $response;
    }

    protected function checkErrorsForGuzzleCall($response)
    {
        $responseBody = json_decode($response->getBody(), true);

        $this->trace->info(
            TraceCode::BIN_SERVICE_RESPONSE,
            [
                'response'    => $responseBody,
                'status_code' => $response->getStatusCode(),
            ]);
    }

    protected function checkErrors($response)
    {
        $responseBody = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::BIN_SERVICE_RESPONSE,
            [
                'response'    => $responseBody,
                'status_code' => $response->status_code,
            ]);
    }

    public function uploadBinFileAtBinService($input, $namespace = 'RZP/IN/DEBIT')
    {

        $url = Constants::BIN_SERVICE_UPLOAD_ROUTE;

        $client = new Client([
            'base_uri' => $this->baseUrlSec,
            'timeout' => 30,
            'verify' => false,
        ]);

        $auth = 'Basic ' . base64_encode($this->key . ':' . $this->secret);

        $contents = $input['file'];
        $fileName = $contents->getClientOriginalName();

        try {
            // Send a POST request to the specified URL
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Authorization' => $auth,
                    self::X_NAMESPACE => $namespace,
                    Headers::DEV_SERVE_USER => Request::header(Headers::DEV_SERVE_USER),
                ],
                'multipart' => [
                    [
                        'name'     => 'file_content',
                        'contents' => Utils::tryFopen($contents, 'r'),
                        'filename' => $fileName
                    ],
                    [
                        'name'     => 'file_name',
                        'contents' => 'uploads/'.$fileName
                    ]
                ]
            ]);

            $this->checkErrorsForGuzzleCall($response);
            return json_decode($response->getBody(), true);


        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->trace->error(
                TraceCode::BIN_SERVICE_GUZZLE_EXCEPTION,
                [
                    'message'         => $e->getMessage(),
                    'api_status_code' => $e->getCode(),
                ]);

            $this->trace->error(
                TraceCode::BIN_SERVICE_ERROR,
                [
                    'error_message' => $e->getMessage(),
                ]
            );

            throw new \Exception('Error while uploading file to bin service: ' . $e->getMessage());

        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::BIN_SERVICE_ERROR,
                [
                    'error_message' => $e->getMessage(),
                ]
            );

            throw new \Exception('Error while uploading file to bin service: ' . $e->getMessage());

        }
    }

}

