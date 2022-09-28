<?php

namespace RZP\Models\Merchant\OneClickCheckout\Native;

use GuzzleHttp\Client as HttpClient;
use RZP\Exception;
use RZP\Error\ErrorCode;
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
    protected $username;
    protected $password;

    public function __construct(array $config)
    {
        $this->username             = $config[OneClickCheckout\Constants::USERNAME];
        $this->password             = $config[OneClickCheckout\Constants::PASSWORD];
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
        catch (Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR,
                null,
                $e
            );
        }
    }

    protected function setHeaders()
    {
        $authorizationToken = 'Basic '. base64_encode("$this->username:$this->password");
        $headers = [
            'Content-type' => 'application/json',
            'Authorization' => $authorizationToken
        ];

        $this->headers = $headers;
    }

}
