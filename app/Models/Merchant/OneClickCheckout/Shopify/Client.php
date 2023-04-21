<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * handles all communication with shopify for 1cc
 */
class Client
{
    // current version supported
    const STOREFRONT_API_ENDPOINT     = '/api/2022-10/graphql.json';
    const ADMIN_GRAPHQL_API_ENDPOINT  = '/admin/api/2022-10/graphql.json';
    const ADMIN_REST_API_ENDPOINT     = '/admin/api/2022-10';
    const MY_SHOPIFY                  = '.myshopify.com';
    const POST                        = 'POST';
    const GET                         = 'GET';
    const PUT                         = 'PUT';
    const MAX_ATTEMPTS                = 4;

    protected $app;
    protected $trace;
    protected $monitoring;

    // credentials required from merchants
    protected $shopId;
    protected $apiKey;
    protected $apiSecret;
    protected $oaAuthToken;
    protected $storefrontAccessToken;

    protected $endpoint;
    protected $headers;

    public function __construct(array $config)
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->monitoring = new Monitoring();

        $this->shopId                = $config[OneClickCheckout\Constants::SHOP_ID];
        $this->apiKey                = $config[OneClickCheckout\Constants::API_KEY];
        $this->apiSecret             = $config[OneClickCheckout\Constants::API_SECRET];
        $this->oauthToken            = $config[OneClickCheckout\Constants::OAUTH_TOKEN];
        $this->storefrontAccessToken = $config[OneClickCheckout\Constants::STOREFRONT_ACCESS_TOKEN];
    }

    public function getOAuthToken(){
        return $this->oauthToken;
    }

    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param string merchantOrderId - Shopify order ID
     * returns "transactions": [] and 200 even if order id is invalid
     */
    public function getTransactionsByOrder(string $merchantOrderId)
    {
        $resource = '/orders/' . strval($merchantOrderId) . '/transactions.json';
        return $this->sendRestApiRequest(
            null,
            self::GET,
            $resource);
    }

    public function getStoreFrontAccessToken()
    {
        return $this->storefrontAccessToken;
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

    /**
     * fetch cart object using
     * cart id
     */
    public function getCartById(string $cartId)
    {
        $body = $this->sendRestApiRequest(
            [],
            self::GET,
            '/carts/' . strval($cartId) . '.json'
        );
        return $body;
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

        $data = [
            'headers' => $this->headers
        ];

        if ($method != self::GET)
        {
            $data['body'] = $body;
        }

        $responseArr;
        $lastStatusCode = '';
        $attempts = 0;
        while ($attempts < self::MAX_ATTEMPTS)
        {
            $attempts++;
            try
            {
                $response = (new HttpClient)->request($method, $this->endpoint, $data);

                $responseArr = $this->parseResponse($response);
                $lastStatusCode = $responseArr['status_code'];
                $delay = $this->getBackoffIfRetriableRequest($responseArr, $apiType, $attempts);
                if ($delay === -1)
                {
                    return $responseArr['raw_contents'];
                }
                usleep($delay);
                continue;
            }
            catch (GuzzleRequestException $e)
            {
                $errResponse = $e->getResponse();
                $responseArr = $this->parseResponse($errResponse);
                $lastStatusCode = $responseArr['status_code'];
                // In case of auth failures, Shopify does not return a body.
                if ($responseArr['status_code'] === 401 || $responseArr['status_code'] === 403)
                {
                    $type = $responseArr['status_code'] === 401 ? 'unauthorized' : 'forbidden';
                    $this->trace->error(
                        TraceCode::SHOPIFY_1CC_API_ACCESS_DENIED,
                        [
                           'type'      => $type,
                           'api_type'  => $apiType,
                           'response'  => $responseArr,
                        ]);
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_ACCESS_DENIED);
                }

                $delay = $this->getBackoffIfRetriableRequest($responseArr, $apiType, $attempts);
                if ($delay === -1)
                {
                    throw $e;
                }
                usleep($delay);
                continue;
            }
        }
        $this->trace->error(
            TraceCode::SHOPIFY_1CC_API_RETRY_EXCEEDED_LIMIT,
            [
               'type'           => 'retry_exceeded',
               'api_type'       => $apiType,
               'attempt_number' => $attempts,
               'status_code'    => $lastStatusCode,
            ]);
        $this->monitoring->addTraceCount(
            Metric::SHOPIFY_1CC_API_RATE_LIMIT,
            [
                'error_type'  => 'retry_exceeded',
                'api_type'    => $apiType,
                'status_code' => $lastStatusCode,
            ]
        );

        if ($lastStatusCode >= 500)
        {
            throw new Exception\ServerErrorException("Error connecting with Shopify", ErrorCode::SERVER_ERROR_SHOPIFY_SERVICE_FAILURE);
        }
        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_THROTTLED);
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

    // NOTE: Unable to find documentation for rate limit headers for graphql APIs
    protected function logRateLimit(array $response, string $apiType): void
    {
        $body = $response['body'];
        $headers = $response['headers'];

        switch ($apiType)
        {
            case OneClickCheckout\Constants::STOREFRONT:
                break;

            case OneClickCheckout\Constants::ADMIN_GRAPHQL:
                $cost = $body['extensions']['cost'] ?? [];
                // TODO: Trim the trace log once issue is identified.
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_RATE_LIMIT,
                    [
                        'api_type'   => $apiType,
                        'rate_limit' => $this->getConsumptionCost($cost),
                        'headers'    => $headers,
                    ]);
                break;

            case OneClickCheckout\Constants::ADMIN_REST:
                // TODO: Trim the trace log once issue is identified.
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_RATE_LIMIT,
                    [
                        'api_type'   => $apiType,
                        'rate_limit' => $headers['X-Shopify-Shop-Api-Call-Limit'] ?? 'Throttled',
                        'headers'    => $headers,
                    ]);
                break;
        }
    }

    protected function getBackoffIfRetriableRequest(array $response, string $apiType, int $attemptNumber): int
    {
        $this->logRateLimit($response, $apiType);

        $shouldRetry = $this->shouldRetry($response, $apiType);
        if ($shouldRetry === false)
        {
            return -1;
        }

        $delay = $this->getDelay($apiType, $attemptNumber, $response);
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_API_RETRY,
            [
               'type'           => 'retry_triggered',
               'api_type'       => $apiType,
               'status_code'    => $response['status_code'],
               'backoff_millis' => $delay/1000,
               'attempt_number' => $attemptNumber,
            ]);
        $this->monitoring->addTraceCount(
            Metric::SHOPIFY_1CC_API_RATE_LIMIT,
            [
                'error_type'  => 'retry_triggered',
                'api_type'    => $apiType,
                'status_code' => $response['status_code'],
            ]
        );
        return $delay;
    }

    protected function parseResponse($response): array
    {
        // getContents is a stream, so calling it again will return null
        $rawContents = $response->getBody()->getContents();
        return [
            'status_code'  => $response->getStatusCode(),
            'headers'      => $response->getHeaders(),
            'raw_contents' => $rawContents,
            'body'         => json_decode($rawContents, true),
            'protocol'     => $response->getProtocolVersion(),
            'reason'       => $response->getReasonPhrase(),
        ];
    }

    // delay in microseconds
    protected function getDelay(string $apiType, int $attempt, array $response): int
    {
        return (500 + $attempt * 300) * 1000;
    }

    protected function shouldRetry(array $response, string $apiType): bool
    {
        $isStatusCodeRetriable = $response['status_code'] === 429;
        if ($apiType === OneClickCheckout\Constants::ADMIN_REST)
        {
            return $isStatusCodeRetriable;
        }

        // graphql requests always return 200 even if it gets throttled so we check the response body
        return ($response['body']['errors'][0]['message'] ?? '') === 'Throttled' || $isStatusCodeRetriable;
    }

    protected function getConsumptionCost(array $cost): string
    {
        // TODO: Trim the trace log once issue is identified.
        $estimatedCostPending = $cost['requestedQueryCost'] - $cost['throttleStatus']['currentlyAvailable'];
        return strval(abs($estimatedCostPending));
    }
}
