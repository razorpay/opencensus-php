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
}
