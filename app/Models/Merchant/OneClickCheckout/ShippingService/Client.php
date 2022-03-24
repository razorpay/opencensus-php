<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingService;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;


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

    public function sendRequest($path, $input, $method)
    {
        $url = $this->getBaseUrl() . $path;
        try
        {
            $this->app['trace']->info(TraceCode::SHIPPING_SERVICE_REQUEST, [
                'url'   => $url,
                'method' => $method,
            ]);

            $response = $this->makeRequest($url, $this->getHeaders(), $input, $method, $this->getOptions());

            if ($response->status_code != 200)
            {
                $this->app['trace']->info(TraceCode::SHIPPING_SERVICE_RESPONSE,
                    [
                        'status_code' => $response->status_code,
                        'response' => $response
                    ]);
            }

            if ($response->status_code >= 500)
            {
                throw new IntegrationException('shipping_service request failed with status code: ' . $response->status_code,
                    ErrorCode::SERVER_ERROR);
            }

            $parsedResponse = $this->parseAndReturnResponse($response);

            if ($response->status_code >= 400)
            {
                $description = $parsedResponse['msg'] ?? '';
                if (isset($parsedResponse['meta']) === true)
                {
                    if (isset($parsedResponse['meta']['code']) === true)
                    {
                        $description = $description.'. code: '. $parsedResponse['meta']['code'];
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
            $data = [
                'exception' => $exception->getMessage(),
                'url'       => $url,
            ];

            $this->app['trace']->error(TraceCode::SHIPPING_SERVICE_ERROR, $data);

            throw $exception;
        }
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        if (empty($content) === true)
        {
            $content = '{}';
        } else
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
        $configPath = 'applications.shipping_service';

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
            self::X_REQUEST_ID  => $this->app['request']->getTaskId(),
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
