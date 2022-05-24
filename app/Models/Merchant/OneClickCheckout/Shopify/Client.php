<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Merchant\OneClickCheckout;
use GuzzleHttp\Client as HttpClient;

/**
 * handles all communication with shopify for 1cc
 */
class Client
{
    // current version supported
    const STOREFRONT_API_ENDPOINT     = '/api/2021-10/graphql.json';
    const ADMIN_GRAPHQL_API_ENDPOINT  = '/admin/api/2021-10/graphql.json';
    const ADMIN_REST_API_ENDPOINT     = '/admin/api/2021-10';
    const MY_SHOPIFY                  = '.myshopify.com';
    const POST                        = 'POST';
    const GET                         = 'GET';
    const PUT                         = 'PUT';
    const RETRIABLE_STATUS_CODES      = [429, 500];

    // credentials required from merchants
    protected $shopId;
    protected $apiKey;
    protected $apiSecret;
    protected $oaAuthToken;
    protected $storefrontAccessToken;

    public function __construct(array $config)
    {
        $this->shopId                = $config[OneClickCheckout\Constants::SHOP_ID];
        $this->apiKey                = $config[OneClickCheckout\Constants::API_KEY];
        $this->apiSecret             = $config[OneClickCheckout\Constants::API_SECRET];
        $this->oauthToken            = $config[OneClickCheckout\Constants::OAUTH_TOKEN];
        $this->storefrontAccessToken = $config[OneClickCheckout\Constants::STOREFRONT_ACCESS_TOKEN];
    }

    public function sendStorefrontRequest($body)
    {
        return $this->sendRequest(
            $body,
            OneClickCheckout\Constants::STOREFRONT,
            self::POST
        );
    }

    public function sendGraphqlRequest($body)
    {
        return $this->sendRequest(
            $body,
            OneClickCheckout\Constants::ADMIN_GRAPHQL,
            self::POST
        );
    }

    public function sendRestApiRequest($body, string $method, string $resource)
    {
        return $this->sendRequest(
            $body,
            OneClickCheckout\Constants::ADMIN_REST,
            $method,
            $resource
        );
    }

    protected function sendRequest($body = null, string $apiType, string $method, string $resource = '')
    {
        $this->setHeaders($apiType);

        $this->setUrl($apiType, $resource);

        if (isset($body) === false)
        {
            if (is_string($body))
            {
                $bodyString = $body;
            }
            else
            {
                $bodyString = json_encode($body);
            }
        }

        try
        {
            $response = (new HttpClient)->request($method, $this->endpoint, [
                'headers' => $this->headers,
                'body' => $body
            ]);

            // TODO: fix this and return headers as well for 429
            return $response->getBody()->getContents();
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

    protected function setHeaders(string $apiType)
    {
        $headers = [
          'Content-type' => 'application/json',
        ];

        if ($apiType === OneClickCheckout\Constants::STOREFRONT)
        {
            $headers['X-Shopify-Storefront-Access-Token'] = $this->storefrontAccessToken;
        }
        else
        {
            $headers['X-Shopify-Access-Token'] = $this->oauthToken;
        }

        $this->headers = $headers;
    }

    protected function setUrl(string $apiType, string $resource)
    {
        $domain = 'https://' . $this->shopId . self::MY_SHOPIFY;

        switch ($apiType)
        {
            case OneClickCheckout\Constants::STOREFRONT:
                $url = $domain . self::STOREFRONT_API_ENDPOINT;
                break;

            case OneClickCheckout\Constants::ADMIN_GRAPHQL:
                $url = $domain . self::ADMIN_GRAPHQL_API_ENDPOINT;
                break;

            case OneClickCheckout\Constants::ADMIN_REST:
                $url = $domain . self::ADMIN_REST_API_ENDPOINT . $resource;
                break;
        }
        $this->endpoint = $url;
    }

}
