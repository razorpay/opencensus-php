<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Service;
use Illuminate\Http\Request;
use RZP\Http\Response\Response;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Http\Request\Requests;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\OneClickCheckout\Constants;

class Client
{
    protected $app;
    protected $auth;
    protected $trace;
    protected $merchant;

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
        $this->auth = $this->app['basicauth'];
        $this->merchant = $this->auth->getMerchant();
        $this->trace = $this->app['trace'];
    }

    /**
     * @throws IntegrationException
     * @throws BadRequestException
     */
    public function sendRequest($path, $input, $method, $headers = [])
    {
        $url = $this->getBaseUrl() . $path;
        try
        {
            $this->app['trace']->info(TraceCode::MAGIC_CHECKOUT_SERVICE_REQUEST, [
                'url'   => $url,
                'method' => $method,
            ]);

            $headers = array_merge($headers, $this->getHeaders());

            $response = $this->makeRequest($url, $headers, $input, $method, $this->getOptions());

            if ($response->status_code != 200)
            {
                $this->app['trace']->info(TraceCode::MAGIC_CHECKOUT_SERVICE_RESPONSE,
                    [
                        'status_code' => $response->status_code,
                        'response' => $response
                    ]);
            }

            if ($response->status_code === 503)
            {
                throw new ServerErrorException(
                    "magic checkout request failed with 503",
                    ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                );
            }

            if ($response->status_code >= 500)
            {
                throw new IntegrationException('magic-checkout-service request failed with status code: ' . $response->status_code,
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
            $data = [
                'exception' => $exception->getMessage(),
                'url'       => $url,
            ];

            $this->app['trace']->error(TraceCode::MAGIC_CHECKOUT_SERVICE_ERROR, $data);

            throw $exception;
        }
    }

    // In case of `GET` requests the $content passed to `Requests::request(...)` must be an
    // array as it is converted to URL params.
    // For other requests it should be a stringified JSON. As PHP treats objects as arrays we need to use
    // `JSON_FORCE_OBJECT` flag to ensure we always pass `{}` instead of `[]` in the body.
    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        if ($method !== "GET" and $method !== "DELETE")
        {
            if (empty($content) === true)
            {
                $content = json_encode([], JSON_FORCE_OBJECT);
            }
            else
            {
                $content = json_encode($content);
            }
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
        $configPath = 'applications.magic_checkout_service.api';

        $this->config = $this->app['config']->get($configPath);
    }

    protected function getBaseUrl()
    {
        return $this->config['url'];
    }

    protected function getHeaders()
    {
        $headers = [
            self::CONTENT_TYPE  => 'application/json',
            self::AUTHORIZATION => $this->getAuthorizationHeader(),
            self::X_REQUEST_ID  => $this->app['request']->getTaskId(),
        ];
        if (empty($this->merchant) === false)
        {
            $headers[Constants::X_Merchant_Id] = $this->merchant->getId();
        }
        return $headers;
    }

    protected function getAuthorizationHeader(): string
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
