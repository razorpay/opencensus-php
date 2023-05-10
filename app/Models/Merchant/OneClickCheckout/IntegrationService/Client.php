<?php

namespace RZP\Models\Merchant\OneClickCheckout\IntegrationService;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;
use ApiResponse;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    protected $app;

    const CONTENT_TYPE                     = 'Content-Type';
    const AUTHORIZATION                    = 'Authorization';
    const X_REQUEST_ID                     = 'X-Request-Id';
    const TIMEOUT                          = 'timeout';

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;

        $this->setConfig();
    }

    public function sendRequest($path, $method, $input = [], $headers = [])
    {
        $url = $this->getBaseUrl() . $path;
        try
        {
            $this->app['trace']->info(TraceCode::INTEGRATION_SERVICE_REQUEST, [
                'url'   => $url,
                'method' => $method,
            ]);

            $requestHeaders = $this->getHeaders();
            if (sizeof($headers) > 0 )
            {
                $requestHeaders = array_merge($this->getHeaders(), $headers);
            }
            $response = $this->makeRequest($url, $requestHeaders, $input, $method, $this->getOptions());

            if ($response->status_code != 200)
            {
                $this->app['trace']->info(TraceCode::INTEGRATION_SERVICE_RESPONSE,
                    [
                        'status_code' => $response->status_code,
                        'response' => $response
                    ]);
            }

            $parsedResponse = $this->parseAndReturnResponse($response);

            if ($response->status_code === 404 && $parsedResponse['Code'] === "BAD_REQUEST_RECORD_NOT_FOUND")
            {
                return ApiResponse::json([$parsedResponse['Code']], 404);
            }

            if ($response->status_code > 400)
            {
                throw new IntegrationException('integration service request failed with status code: ' . $response->status_code,
                    ErrorCode::SERVER_ERROR);
            }

            if ($response->status_code == 400)
            {
                $description = $parsedResponse['Description'] ?? '';
                if (isset($parsedResponse['Metadata']) === true)
                {
                    if (isset($parsedResponse['Metadata']['code']) === true)
                    {
                        $description = $description.'. code: '. $parsedResponse['Metadata']['code'];
                    }
                }

                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    $parsedResponse,
                    $description);
            }

            return $parsedResponse;

        }
        catch (\Exception $exception)
        {
            $fixedPath = explode("?", $path)[0];
            $data = [
                'exception' => $exception->getMessage(),
                'path'       => $fixedPath,
            ];

            $this->app['trace']->count(TraceCode::INTEGRATION_SERVICE_ERROR, $data);

            $this->app['trace']->error(TraceCode::INTEGRATION_SERVICE_ERROR, $data);

            throw $exception;
        }
    }

    public function makeMultipartRequest(
        string $path,
        $input,
        array $headers = [])
    {
        $url = $this->getBaseUrl() . $path;
        try
        {
            $this->app['trace']->info(TraceCode::INTEGRATION_SERVICE_REQUEST, [
                'type' => 'raw',
                'url'   => $url,
                'method' => 'POST',
            ]);

            $requestHeaders = array_merge([
                self::AUTHORIZATION => $this->getAuthorizationHeader(),
                self::X_REQUEST_ID  => $this->app['request']->getId(),
            ], $headers);
            $client = new \GuzzleHttp\Client();

            $response = $client->request(
                'POST',
                $url,
                [
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $input,
                            'filename' => 'file.csv',
                        ],
                    ],
                    'timeout' => $this->config['timeout'],
                    'headers' => $requestHeaders,
                    'http_errors' => false,
                ]
            );

            if ($response->getStatusCode() != 200)
            {
                $this->app['trace']->info(TraceCode::INTEGRATION_SERVICE_RESPONSE,
                    [
                        'status_code' => $response->getStatusCode(),
                        'response' => $response->getBody(),
                    ]);
            }

            return $response;

        }
        catch (GuzzleException $e)
        {
            $fixedPath = explode("?", $path)[0];
            $data = [
                'exception' => $e->getMessage(),
                'path'       => $fixedPath,
            ];

            $this->app['trace']->count(TraceCode::INTEGRATION_SERVICE_ERROR, $data);

            $this->app['trace']->error(TraceCode::INTEGRATION_SERVICE_ERROR, $data);

            throw $e;
        }
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        if (empty($content) === true)
        {
            $content = array();
        }
        else
        {
            $content = json_encode($content);
        }

        return Requests::request($url, $headers, $content, $method, $options);
    }


    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function setConfig()
    {
        $configPath = 'applications.consumer_app.api';

        $this->config = $this->app['config']->get($configPath);
    }

    protected function getBaseUrl()
    {
        return $this->config['url'];
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE  => 'application/json',
            self::AUTHORIZATION => $this->getAuthorizationHeader(),
            self::X_REQUEST_ID  => $this->app['request']->getId(),
        ];
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->config['username']. ':' . $this->config['secret']);
    }

    protected function getOptions()
    {
        return [
            self::TIMEOUT => $this->config['timeout'],
        ];
    }
}
