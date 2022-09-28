<?php

namespace RZP\Models\Merchant\OneClickCheckout\Woocommerce;

use GuzzleHttp\Client as HttpClient;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant\OneClickCheckout;

class Client
{
    // current version supported
    const ADMIN_REST_API_ENDPOINT     = '/wp-json/wc/v3';
    const POST                        = 'POST';
    const GET                         = 'GET';
    const PUT                         = 'PUT';
    const RETRIABLE_STATUS_CODES      = [429, 500];

    // credentials required from merchants
    protected $apiKey;
    protected $apiSecret;

    public function __construct(array $config)
    {
        $this->apiKey                = $config[OneClickCheckout\Constants::API_KEY];
        $this->apiSecret             = $config[OneClickCheckout\Constants::API_SECRET];
    }

    public function sendRequest($body = null, string $method, string $resource)
    {
        $this->setHeaders();

        $this->endpoint = $resource;

        try
        {
            $response = (new HttpClient)->request($method, $this->endpoint, [
                'headers' => $this->headers,
                'body' => $body
            ]);

            return $response;
        }
        catch (Throwable $exception)
        {
            throw new Exception\ServerErrorException(
                $exception->getMessage(),
                $exception->getCode(),
                null,
                $exception
            );
        }
    }

    protected function setHeaders()
    {
        $authorizationToken = 'Basic '. base64_encode("$this->apiKey:$this->apiSecret");
        $headers = [
            'Content-type' => 'application/json',
            'Authorization' => $authorizationToken
        ];

        $this->headers = $headers;
    }

}
