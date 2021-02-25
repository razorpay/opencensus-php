<?php

namespace RZP\Services;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\IntegrationException;

class CareServiceClient
{
    protected $app;

    protected $config;

    const CONTENT_TYPE      = 'Content-Type';
    const AUTHORIZATION     = 'Authorization';
    const X_REQUEST_ID      = 'X-Request-Id';


    public function __construct($app)
    {
        $this->app = $app;

        $this->setConfig();
    }

    public function dashboardProxyRequest($path, $input)
    {
        $input = $this->addMerchantDetails($input);

        return $this->sendRequestAndProcessResponse($path, Requests::POST, $input);
    }

    protected function sendRequestAndProcessResponse($path, $method, $content)
    {
        $this->app['trace']->info(TraceCode::CARE_SERVICE_REQUEST, [
            'path'       => $path,
            'method'     => $method,
        ]);

        $response = $this->sendRequest($path, $method, $content);

        $this->app['trace']->info(TraceCode::CARE_SERVICE_RESPONSE, [
            'status_code'     => $response->status_code,
        ]);

        return $this->processResponse($response);
    }

    public function sendRequest($path, $method, $content, $headers = [], $options = [])
    {
        $url = $this->getBaseUrl() . $path;

        $headers = array_merge($headers, $this->getHeaders());

        $content = json_encode($content);

        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function processResponse(\Requests_Response $response): array
    {
        if ($response->status_code >= 500)
        {
            throw new IntegrationException('care_service integration exception',
            ErrorCode::SERVER_ERROR);
        }

        $parsedResponse = $this->parseResponse($response);

        if ($response->status_code >= 400)
        {
            $description = $parsedResponse['msg'] ?? '';

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
            null,
            $parsedResponse,
            $description);
        }

        return $parsedResponse;
    }

    protected function setConfig()
    {
        $configPath = 'applications.care';

        $this->config = $this->app['config']->get($configPath);
    }

    protected function getBaseUrl()
    {
        return $this->config['host'];
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE      => 'application/json',
            self::AUTHORIZATION     => $this->getAuthorizationHeader(),
            self::X_REQUEST_ID      => $this->app['request']->getTaskId(),
        ];
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode('api:' . $this->config['password']);
    }

    protected function parseResponse(\Requests_Response $response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function addMerchantDetails($input)
    {
        $input['merchant'] = [
            'id'    => $this->app['basicauth']->getMerchantId(),
        ];

        return $input;
    }
}
