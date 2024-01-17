<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\OneClickCheckout\Config\Service;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\OneClickCheckout\Webhooks;
use RZP\Models\Merchant\OneClickCheckout\RtoRecommendation;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

class OneClickCheckoutController extends Controller
{

    /**
     * Creates and returns the Rzp order_id for a Shopify checkout
     * For APIs from merchant website, the content type is "text/plain"
     */
    public function shopifyCreateCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $userAgent = $headers['x-user-agent'][0] ?? $headers['user-agent'][0] ?? null;
        $contentType = $headers['content-type'][0];
        $bodyJSON = $contentType === 'text/plain' ? $this->parseToJSONIfApplicable($rawContents, $contentType) : Request::all();
        try
        {
            // todo: this will be done in FE for now we are doing it to unblock BE
            $ga = $bodyJSON['ga_id'];
            $parsedGa = explode(".", $ga);
            $parsedGaId = array_slice($parsedGa, -2);
            $gaId = join(".", $parsedGaId);
            $customerInfo = ['user_agent' => $userAgent, 'ga_id' => $gaId];
            $result = (new Shopify\Service)->shopifyCreateCheckout($bodyJSON, $customerInfo);
            $response = ApiResponse::json($result, 200);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
        catch (\Throwable $e)
        {
            $response = $this->handleError($e);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
    }


    /**
     * Creates and returns the Rzp order_id for a given Shopify checkout object.
     * To be called from an internal service such as magic-club/checkout etc.
     * Although the call itself is made by an internal service, the incoming headers will contain the
     * customer session headers.
     *
     * @return mixed
     * @throws BaseException
     */
    public function createOrderAndGetPreferences()
    {
        $input = Request::all();
        $headers = Request::header();
        try
        {
            $userAgent = $headers['x-user-agent'][0] ?? $headers['user-agent'][0] ?? null;
            // todo: this will be done in FE for now we are doing it to unblock BE

            $ga = $input['ga_id'] ?? '';
            $parsedGa = explode(".", $ga);
            $parsedGaId = array_slice($parsedGa, -2);
            $gaId = join(".", $parsedGaId);

            $fbAnalytics = $input['fb_analytics'] ?? array();

            $customerInfo = ['user_agent' => $userAgent, 'ga_id' => $gaId, 'fb_analytics' => $fbAnalytics];
            $result = (new Shopify\Service)->createOrderAndGetPreferences($input, $customerInfo);
            return ApiResponse::json($result, 200);
        }
        catch (\Throwable $e)
        {
            return $this->handleError($e);
        }
    }

    /**
     * This is a deliberate replica of createOrderAndGetPreferences()
     * This will be called from magic-checkout-service & will be used for decomposing
     * of create order & get preferences logic from API Monolith to MCS.
     *
     * @return mixed
     * @throws BaseException
     */
    public function createOrderAndGetPreferencesForMCS()
    {
        $input = Request::all();
        $headers = Request::header();
        try
        {
            $userAgent = $headers['x-user-agent'][0] ?? $headers['user-agent'][0] ?? null;
            // todo: this will be done in FE for now we are doing it to unblock BE

            $ga = $input['ga_id'] ?? '';
            $parsedGa = explode(".", $ga);
            $parsedGaId = array_slice($parsedGa, -2);
            $gaId = join(".", $parsedGaId);

            $fbAnalytics = $input['fb_analytics'] ?? array();

            $customerInfo = ['user_agent' => $userAgent, 'ga_id' => $gaId, 'fb_analytics' => $fbAnalytics];
            $result = (new Shopify\Service())->createOrderAndGetPreferencesForMCS($input, $customerInfo);

            return ApiResponse::json($result, 200);
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    public function getCheckoutOptions()
    {
        $input = Request::all();
        try
        {
            $result = (new Shopify\Service)->getCheckoutOptions($input);
            $response = ApiResponse::json($result, 200);
            $this->addCorsHeaders($response, 'GET, OPTIONS');
            return $response;
        }
        catch (\Throwable $e)
        {
            $response = $this->handleError($e);
            $this->addCorsHeaders($response, 'GET, OPTIONS');
            return $response;
        }
    }

    public function shopifyCompleteCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        $response = [];
        try
        {
            $result = (new Shopify\Service)->completeCheckoutWithLock($bodyJSON);
            $response = ApiResponse::json($result, 200);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
        }
        catch (\Throwable $e)
        {
            $response = $this->handleError($e);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }

        try
        {
            (new Shopify\Service)->checkPrepayCODFlow($bodyJSON);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::ONE_CC_PREPAY_COD_CREATE_PL_FAILED,
                [
                    'order_id'    =>  $bodyJSON['razorpay_order_id'],
                    'merchant_id' => $bodyJSON['merchant_id'],
                    'error'       => $e->getMessage(),
                ]);
        }
        return $response;

    }

    public function shopifyOAuthRedirect()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->shopifyOAuthRedirect($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyUpdateCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        try
        {
            $result = (new Shopify\Service)->updateCheckout($bodyJSON);
            $response = ApiResponse::json($result, 200);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
        catch (\Throwable $e)
        {
            $response = $this->handleError($e);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
    }

    public function shopifyUpdateCheckoutUrl()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        try
        {
            $result = (new Shopify\Service)->updateCheckoutUrl($bodyJSON);
            $response = ApiResponse::json($result, 200);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
        catch (\Throwable $e)
        {
            $response = $this->handleError($e);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
    }

    public function processWebhook(string $platform)
    {
        $input = Request::all();
        $rawContents = Request::getContent();
        $headers = Request::header();

        (new Webhooks\Service)->handle([
            'raw_contents' => $rawContents,
            'headers'      => $headers,
            'platform'     => $platform,
            'input'        => $input,
        ]);

        return ApiResponse::json([], 204);
    }

    public function getOrderAnalytics()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $contentType = $headers['content-type'][0];
        $bodyJSON = $contentType === 'text/plain' ? $this->parseToJSONIfApplicable($rawContents, $contentType) : Request::all();
        $result = (new Shopify\Service)->getOrderAnalytics($bodyJSON);

        if ($result === [])
        {
            $response = ApiResponse::json(['error' => 'Order not found.'], 422);
            $this->addCorsHeaders($response, 'POST, OPTIONS');
            return $response;
        }
        $response = ApiResponse::json($result, 200);
        $this->addCorsHeaders($response, 'POST, OPTIONS');
        return $response;
    }

    public function allowCors(string $methods = '')
    {
        $methods = $methods === '' ? 'OPTIONS' : $methods;

        $response = ApiResponse::json([], 200);

        $this->addCorsHeaders($response, $methods);

        return $response;
    }

    protected function addCorsHeaders($response, string $methods): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $response->headers->set('Access-Control-Allow-Methods', $methods);
    }

    /**
     * Simple requests will send payload as a string so we need to parse it
     * @param array|string $body - Raw contents from the request
     * @param string $contentType - contentType from the header
     * @return array $body - Body as an array
     */
    protected function parseToJSONIfApplicable($body, string $contentType): array
    {
        $result = 'skip';
        if (gettype($body) === 'string')
        {
            $result = 'success';
            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE)
            {
                $result = 'fail';
            }
        }
        $this->trace->info(
          TraceCode::SHOPIFY_1CC_PARSE_REQUEST_BODY_RESULT,
          [
            'type'         => 'parse_request_body',
            'input'        => $body,
            'result'       => $result,
            'content_type' => $contentType,
          ]);
        return $body;
    }

    // $e can be exception or throwable so we do not add a strong type.
    protected function handleError($e)
    {
        if (($e instanceof BaseException) === false)
        {
            throw $e;
        }
        switch ($e->getError()->getInternalErrorCode())
        {
            case ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_INVALID_VARIANTS_RECEIVED:
            case ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED:
            case ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_ACCESS_DENIED:
                $data = $e->getError()->toPublicArray(true);
                return ApiResponse::json($data, 422);

            case ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_THROTTLED:
                $data = $e->getError()->toPublicArray(true);
                return ApiResponse::json($data, 429);

            case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
            case ErrorCode::SERVER_ERROR_SHOPIFY_SERVICE_FAILURE:
                $data = $e->getError()->toPublicArray(true);
                return ApiResponse::json($data, 503);

            case ErrorCode::BAD_REQUEST_ERROR:
            case ErrorCode::BAD_REQUEST_INVALID_ID:
            case ErrorCode::BAD_REQUEST_VALIDATION_FAILED:
                $data = $e->getError()->toPublicArray(true);
                return ApiResponse::json($data, 400);

            default:
              throw $e;
        }
    }

    public function adminWhitelistCoupons(string $merchantId)
    {
        $data = Request::all();
        [$res, $status] = (new Service())->adminWhitelistCoupons($merchantId, $data);
        return ApiResponse::json($res, $status);
    }

    public function getMethodsAndOffersForMerchant()
    {
        $input = Request::all();
        $result = (new Service())->getMethodsAndOffersForMerchant($input);
        $response = ApiResponse::json($result, 200);
        $this->addCorsHeaders($response, "GET");
        return $response;
    }

    public function fetchShopifyMetaFields(string $merchantId)
    {
        $input = Request::all();

        $response = (new Shopify\Service)->fetchShopifyMetaFields($merchantId, $input);

        return ApiResponse::json($response, 200);
    }

    public function updateShopifyMetaFields(string $merchantId)
    {
        $input = Request::all();

        $response = (new Shopify\Service)->updateShopifyMetaFields($input, $merchantId);

        return ApiResponse::json($response['data'], $response['status_code']);
    }

    public function fetchShopifyThemes(string $merchantId)
    {
        $input = Request::all();

        $response = (new Shopify\Service)->fetchShopifyThemes($merchantId, $input);

        return ApiResponse::json($response, 200);
    }

    public function insertShopifySnippet(string $merchantId)
    {
        $input = Request::all();

        $response = (new Shopify\Service)->insertShopifySnippet($merchantId, $input);

        return ApiResponse::json($response, 200);
    }

    public function renderMagicSnippet(string $merchantId)
    {
        $input = Request::all();

        $response = (new Shopify\Service)->renderMagicSnippet($merchantId, $input);

        return ApiResponse::json($response, 200);
    }

    // Merchant dashboard converts URL param of `GET` req into Request:all()
    public function handleMerchantDashboardReq()
    {
        $input = [
          'method' => Request::getMethod(),
          'body'   => Request::all(),
          'path'   => Request::path(),
          'header' => $this->fetchMerchantDashboardHeaders(),
        ];
        if (Request::hasFile('file'))
        {
            $input['file'] = Request::file('file');
        }
        $resp = (new MagicCheckoutService\Service)->handleMerchantDashboardReq($input);
        return ApiResponse::json($resp, 200);
    }

    // Generic controller function for admin dashboard requests routed to magic-checkout-service
    public function handleAdminDashboardThemeAutomationReq()
    {
        $input = [
            'method' => Request::getMethod(),
            'body'   => Request::all(),
            'path'   => Request::path(),
        ];
        $resp = (new MagicCheckoutService\Service)->handleAdminDashboardThemeAutomationReq($input);
        return ApiResponse::json($resp, 200);
    }

    /*
     * Use this function to fetch any headers from merchant dashbaord
     * and pass onto magic checkout service.
     * */
    protected function fetchMerchantDashboardHeaders(): array
    {
        $headers = Request::header();

        $headersArr = $this->fetchCookiesFromHeaders($headers);

        return $headersArr;
    }

    protected function fetchCookiesFromHeaders($headers): array
    {
        if (empty($headers['cookie']) === true || count($headers['cookie']) === 0)
        {
            return [];
        }

        $parsedCookies = array();

        foreach ($headers['cookie'] as $cookie)
        {
            $items = explode('; ', $cookie);
            foreach($items as $item)
            {
                // a delimiter of 2 has been added to parse the header key and value only.
                // It might be the case the value also contains =, thus we need to avoid exploding that part of value
                [$key, $val] = explode('=', $item, 2);
                $parsedCookies[$key] = $val;
            }
        }

        $cookies = [];

        if (!empty($parsedCookies['magic_analytics_oauth_csrf']))
        {
            $cookies = ['magic_analytics_oauth_csrf' => $parsedCookies['magic_analytics_oauth_csrf']];
        }

        return $cookies;
    }

    public function handleMerchantDashboardFileDownloadReq()
    {
        $input = [
            'method' => Request::getMethod(),
            'body'   => Request::all(),
            'path'   => Request::path(),
            'header' => $this->fetchMerchantDashboardHeaders(),
        ];
        $data = (new MagicCheckoutService\Service)->handleMerchantDashboardFileDownloadReq($input);

        return response()->streamDownload(function () use ($data) {
            echo $data->getBody();
        }, 'allowlist_zipcode.csv');
    }

}
