<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Metric;

class Analytics extends Base\Core
{
    const SHOPIFY_ANALYTICS_CACHE_KEY = 'shopify_1cc_analytics';
    const SHOPIFY_ANALYTICS_CACHE_KEY_TTL = 14 * 1440; // 14 days
    const MAGIC_ANALYTICS_CUSTOMER_INFO_CACHE_KEY = 'magic_analytics:customer_info:';
    const MAGIC_ANALYTICS_CUSTOMER_INFO_CACHE_KEY_TTL = 3 * 1440; // 3 days

    protected $cache;
    protected $monitoring;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Monitoring();
        $this->cache = $this->app['cache'];
    }

    public function setShopifyOrderInCache(array $shopifyOrder, array $rzpOrder, string $paymentMethod): void
    {
        $key = 'NA';
        $result = 'fail';
        $message = '';
        try
        {
            $shopifyOrder = $shopifyOrder['order'];
            $key = $this->getCacheKey($this->getOrderKey($shopifyOrder['order_status_url']));
            $this->cache->set(
                $key,
                $this->extractPayload($shopifyOrder, $rzpOrder, $paymentMethod),
                self::SHOPIFY_ANALYTICS_CACHE_KEY_TTL
            );
            $result = 'success';
        }
        catch (\Throwable $e)
        {
            $message = $e->getMessage();
        }
        finally
        {
            $dimensions = [
                'action' => 'set',
                'result' => $result,
            ];
            $this->monitoring->addTraceCount(
                Metric::SHOPIFY_1CC_ANALYTICS_COUNT,
                $dimensions
            );

            $dimensions['key'] = $key;
            if ($result === 'fail')
            {
                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_ANALYTICS,
                    array_merge($dimensions, ['message' => $message])
                );
            }
            else
            {
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_ANALYTICS,
                    $dimensions
                );
            }
        }
    }

    // getShopifyOrderFromCache returns the Shopify order from cache.
    // It is deleted once the key has been read.
    public function getShopifyOrderFromCache(string $path): array
    {
        $key = $this->getCacheKey($path);
        $value = $this->cache->get($key);
        if (empty($value) === false)
        {
            $this->cache->delete($key);
        }
        $result = empty($value) ? 'miss': 'hit';
        $this->pushGetOrderMetrics($key, $result);
        return $value ?? [];
    }

    protected function pushGetOrderMetrics(string $key, string $result): void
    {
        $dimensions = [
            'action' => 'get',
            'result' => $result,
        ];
        $this->monitoring->addTraceCount(
            Metric::SHOPIFY_1CC_ANALYTICS_COUNT,
            $dimensions
        );
        $dimensions['key'] = $key;
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ANALYTICS,
            $dimensions
        );
    }

    // getCacheKey returns the cache key for storing Shopify orders for analytics.
    protected function getCacheKey(string $orderStatusUrl): string
    {
        return self::SHOPIFY_ANALYTICS_CACHE_KEY . ':' . $orderStatusUrl;
    }

    // extractPayload returns the required fields for a Shopify order required by Google Analytics.
    protected function extractPayload(array $shopifyOrder, array $rzpOrder, string $paymentMethod): array
    {
        $customerDetails = [
            'shipping_address' => $shopifyOrder['shipping_address'],
            'billing_address'  => $shopifyOrder['billing_address'],
        ];

        // TODO: Understand the behaviour for merchants with optional email.
        if (array_key_exists('email', $shopifyOrder) === true)
        {
            $customerDetails['email'] = $shopifyOrder['email'];
        }
        if (array_key_exists('phone', $shopifyOrder) === true)
        {
            $customerDetails['contact'] = $shopifyOrder['phone'];
        }

        return [
            'total_amount'     => $rzpOrder['amount'],
            'promotions'       => $rzpOrder['promotions'] ?? [], // If reset API fails then a default value is not returned.
            'shipping_fee'     => $rzpOrder['shipping_fee'],
            'order_id'         => $shopifyOrder['name'],
            'total_tax'        => $shopifyOrder['total_tax'], // NOTE: Tax is currently not supported.
            'payment_method'   => $paymentMethod,
            'payment_currency' => 'INR', // NOTE: Hardcoding as INR until we get further clarification and testing.
            'customer_details' => $customerDetails,
            'shipping_country' => $customerDetails['shipping_address']['country'],
        ];
    }

    // Scenarios to cover based on merchant research.
    // https://test.myshopify.com/abc/orders/123/authenticate?key=456 to /abc/orders/123
    // https://test.com/abc/orders/123/authenticate?key=456 to /abc/orders/123
    // https://in.test.com/abc/orders/123/authenticate?key=456 to /abc/orders/123
    // https://test.in/abc/orders/123/authenticate?key=456 to /abc/orders/123
    protected function getOrderKey(string $orderStatusUrl): string
    {
        $parts = parse_url($orderStatusUrl);
        if (array_key_exists('path', $parts) === true)
        {
            return explode('/authenticate', $parts['path'])[0];
        }
        return '';
    }

    public function sendPurchaseEvent(array $shopifyOrder, array $rzpOrder, string $customerInfo, array $providerTypeList=[Constants::GOOGLE_UNIVERSAL_ANALYTICS])
    {
        $customerInfoObj = json_decode($customerInfo, true);
        $purchaseEventPayload = $this->constructPurchaseEventPayload($shopifyOrder['order'], $rzpOrder, $customerInfoObj, $providerTypeList);
        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::DEBUG_PURCHASE_PAYLOAD_DETAILS,
            $purchaseEventPayload
        );

        $this->app['magic_analytics_provider_service']->triggerEvent($purchaseEventPayload, [Constants::MERCHANT_ID => $merchantId]);
    }

    protected function constructPurchaseEventPayload(array $shopifyOrder, array $rzpOrder, array $customerInfo, array $providerTypeList): array
    {
        $products = $this->transformToProducts($shopifyOrder['line_items'], Constants::PURCHASE);

        $eventSourceUrl = '';
        if (array_key_exists('fb_analytics', $customerInfo))
        {
           $eventSourceUrl = $customerInfo['fb_analytics']['event_source_url'] ?? '';
        }

        $customerInfo = $this->constructCustomerInfo($shopifyOrder, $customerInfo);

        $promotions = "No Coupon Applied";
        if(isset($rzpOrder[Constants::PROMOTIONS]) && sizeof($rzpOrder[Constants::PROMOTIONS]) > 0 && isset($rzpOrder[Constants::PROMOTIONS][0]['code']))
        {
            $promotions = stringify($rzpOrder[Constants::PROMOTIONS][0]['code']);
        }


        $shopifyCheckoutId = '';
        if (isset($rzpOrder[Constants::NOTES]))
        {
            $shopifyCheckoutId = $rzpOrder[Constants::NOTES][Constants::STOREFRONT_ID] ?? '';
        }

        return [
            Constants::PROVIDER_TYPE_LIST => $providerTypeList,
            Constants::SHOPIFY_CHECKOUT_ID => $shopifyCheckoutId,
            Constants::EVENT_TYPE => Constants::PURCHASE,
            Constants::EVENT_TIME => round(microtime(true) * 1000),
            Constants::EVENT_SOURCE_URL  => $eventSourceUrl,
            Constants::CUSTOMER_INFO => $customerInfo,
            Constants::PURCHASE_EVENT => [
                Constants::PRODUCTS => $products,
                Constants::TOTAL_REVENUE => strval($rzpOrder[Constants::AMOUNT] / 100),
                Constants::TOTAL_TAX => $shopifyOrder[Constants::TOTAL_TAX],
                Constants::TOTAL_SHIPPING => strval($rzpOrder[Constants::SHIPPING_FEE] / 100),
                Constants::TRANSACTION_COUPON => $promotions,
                Constants::TRANSACTION_ID => $shopifyOrder[Constants::NAME],
            ]
        ];
    }

    protected function transformToProducts(array $items, string $eventType): array
    {
        $products = array();
        $position = 0;
        foreach ($items as $item)
        {
            $product = array();
            $position++;
            $id = $item[Constants::SKU];
            if (strlen($id) == 0 )
            {
                $id = strval($item[CONSTANTS::ID]);
            }
            $product[CONSTANTS::ID] = $id;
            if ($eventType == Constants::PURCHASE)
            {
                $product[Constants::NAME] = $item[Constants::NAME];
                $product[CONSTANTS::PRICE] = strval($item[CONSTANTS::PRICE]);
            }
            elseif ($eventType == Constants::CHECKOUT)
            {
                $product[CONSTANTS::NAME] = $item[CONSTANTS::TITLE];
                $product[CONSTANTS::PRICE] = strval($item[CONSTANTS::PRICE] / 100);
            }
            $product[CONSTANTS::VARIANT] = $item[Constants::VARIANT_TITLE];
            $product[CONSTANTS::QUANTITY] = strval($item[CONSTANTS::QUANTITY]);
            $product[CONSTANTS::POSITION] = strval($position);
            $products[] = $product;
        }
        return $products;
    }

    protected function constructCustomerInfo(array $shopifyOrder, array $customerInfo): array
    {
        $customerDetails = array();

        $customerDetails[CONSTANTS::CLIENT_ID] = $customerInfo[Constants::GA_ID];
        $customerDetails[CONSTANTS::USER_AGENT] = $customerInfo[Constants::USER_AGENT];

        if(isset($shopifyOrder[Constants::EMAIL]))
        {
            $customerDetails[Constants::EMAIL] = $shopifyOrder[Constants::EMAIL];
        }

        if(isset($shopifyOrder[Constants::PHONE]))
        {
            $customerDetails[Constants::PHONE] = $shopifyOrder[Constants::PHONE];
        }

        if(!empty($customerInfo[Constants::FB_ANALYTICS]))
        {
            $customerDetails[CONSTANTS::FB_ANALYTICS] = $customerInfo[Constants::FB_ANALYTICS];
        }

        return $customerDetails;
    }

    public function sendCheckoutEvent(array $cart, array $customerInfo)//, array $checkout, array $preferences, array $response)
    {
        $checkoutEventPayload = $this->constructCheckoutEventPayload($cart, $customerInfo);
        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::DEBUG_CHECKOUT_PAYLOAD_DETAILS,
            $checkoutEventPayload
        );

        $this->app['magic_analytics_provider_service']->triggerEvent($checkoutEventPayload, [Constants::MERCHANT_ID => $merchantId]);
    }

    protected function constructCheckoutEventPayload(array $cartDetails, array $customerInfo): array
    {
        $products = $this->transformToProducts($cartDetails['items'], Constants::CHECKOUT);

        $customerInfo = $this->constructCustomerInfo([], $customerInfo);

        return [
            CONSTANTS::EVENT_TYPE => CONSTANTS::CHECKOUT,
            CONSTANTS::EVENT_TIME => round(microtime(true) * 1000),
            CONSTANTS::CUSTOMER_INFO => $customerInfo,
            CONSTANTS::CHECKOUT_EVENT => [
                CONSTANTS::PRODUCTS => $products,
            ],
        ];
    }

    public function storeAnalyticsCustomerInfoInCache(string $rzpOrderId, string $customerInfo)
    {
        $this->cache->set(
            $this->getCacheKeyForAnalyticsCustomerInfo($rzpOrderId),
            $customerInfo,
            self::MAGIC_ANALYTICS_CUSTOMER_INFO_CACHE_KEY_TTL
        );
    }

    public function getAnalyticsCustomerInfoFromCache(string $rzpOrderId): string
    {
        $key = $this->getCacheKeyForAnalyticsCustomerInfo($rzpOrderId);
        $value = $this->cache->get($key);
        $result = empty($value) ? 'miss': 'hit';
        $this->pushGetAnalyticsCustomerInfoMetrics($key, $result);
        return $value ?? '';
    }

    protected function pushGetAnalyticsCustomerInfoMetrics(string $key, string $result): void
    {
        $dimensions = [
            'action' => 'get',
            'result' => $result,
        ];
        $this->monitoring->addTraceCount(
            Metric::MAGIC_ANALYTICS_GET_CUSTOMER_INFO_COUNT,
            $dimensions
        );
        $dimensions['key'] = $key;
        $this->trace->info(
            TraceCode::MAGIC_ANALYTICS_CUSTOMER_INFO,
            $dimensions
        );
    }

    protected function getCacheKeyForAnalyticsCustomerInfo(string $rzpOrderId): string
    {
        return self::MAGIC_ANALYTICS_CUSTOMER_INFO_CACHE_KEY . $rzpOrderId;
    }
}
