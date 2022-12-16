<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify\AddressIngestion;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use RZP\Models\Merchant\OneClickCheckout\Constants as Constants;
use RZP\Trace\TraceCode;

class Client extends \RZP\Models\Merchant\OneClickCheckout\Shopify\Client
{
    const CUSTOMERS_API_RESOURCE = "/customers.json";
    const SHOPIFY_RATE_LIMIT_HEADER = "X-Shopify-Shop-Api-Call-Limit";

    const HEADERS = "headers";
    const BODY = "body";
    const STATUS_CODE = "status_code";
    const REASON = "reason";

    public function getCustomerAddresses($input): array
    {
        return $this->makeRestApiRequest(
            $input,
            self::GET,
            self::CUSTOMERS_API_RESOURCE
        );
    }


    public function makeRestApiRequest($body, string $method, string $resource): array
    {
        return $this->request(
            $body,
            Constants::ADMIN_REST,
            $method,
            $resource
        );
    }

    protected function parseShopifyResponse($response): array
    {
        $rateLimit = "0/40";
        if (isset($response[self::HEADERS]) && isset($response[self::HEADERS][self::SHOPIFY_RATE_LIMIT_HEADER])
            && count($response[self::HEADERS][self::SHOPIFY_RATE_LIMIT_HEADER]) == 1 )
        {
            $rateLimit = $response[self::HEADERS][self::SHOPIFY_RATE_LIMIT_HEADER][0];
        }
        return [
            self::HEADERS => [self::SHOPIFY_RATE_LIMIT_HEADER => $rateLimit],
            self::BODY => $response[self::BODY],
            self::STATUS_CODE => $response[self::STATUS_CODE],
            self::REASON => $response[self::REASON],
        ];
    }

    protected function request($body = null, string $apiType, string $method, string $resource = ''): array
    {
        $this->setHeaders($apiType);

        $this->setUrl($apiType, $resource);

        $data = [
            'headers' => $this->headers
        ];

        if ($method != self::GET)
        {
            $data['body'] = $body;
        }
        else
        {
            $data['query'] = $body;
        }

        try
        {
            $response = (new HttpClient)->request($method, $this->endpoint, $data);

            return $this->parseShopifyResponse($this->parseResponse($response));
        }
        catch (GuzzleRequestException $e)
        {
            $errResponse = $e->getResponse();
            $rawContents = $errResponse->getBody()->getContents();
            $this->trace->error(TraceCode::SHOPIFY_FETCH_CUSTOMER_FAILED,
                [
                    'status_code'  => $errResponse->getStatusCode(),
                    'headers'      => $errResponse->getHeaders(),
                    'raw_contents' => $rawContents,
                    'body'         => json_decode($rawContents, true),
                    'protocol'     => $errResponse->getProtocolVersion(),
                    'reason'       => $errResponse->getReasonPhrase(),
                ]);

            return $this->parseShopifyResponse($this->parseResponse($errResponse));
        }
    }
}
