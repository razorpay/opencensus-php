<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicAddressService;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

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
            $this->app['trace']->info(TraceCode::MAGIC_ADDRESS_SERVICE_REQUEST, [
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
                $this->app['trace']->info(TraceCode::MAGIC_ADDRESS_SERVICE_RESPONSE,
                    [
                        'status_code' => $response->status_code,
                        'response' => $response
                    ]);
            }

            if ($response->status_code >= 500)
            {
                throw new IntegrationException('magic address service request failed with status code: ' . $response->status_code,
                    ErrorCode::SERVER_ERROR);
            }

            $parsedResponse = $this->parseAndReturnResponse($response);

            if ($response->status_code >= 400)
            {
                $description = $parsedResponse['error']['description'] ?? '';

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

            $this->app['trace']->error(TraceCode::INTEGRATION_SERVICE_ERROR, $data);

            throw $exception;
        }
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        if (empty($content) === true)
        {
            $content = array();
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
        $configPath = 'applications.address_service.api';

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
