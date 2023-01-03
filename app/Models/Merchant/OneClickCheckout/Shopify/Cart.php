<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Merchant\Metric;

class Cart extends Base\Core
{

    const CART_CACHE_KEY = 'shopify_1cc_cart';

    const CART_CACHE_KEY_TTL = 1 * 1440; // 1 day

    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();

        $this->cache = $this->app['cache'];
    }

    /**
     * fetch cart object details using cart id
     * @param string cartId
     * @return array
     */
    public function getCartData(string $cartId)
    {
        $client = $this->getShopifyClientByMerchant();

        try
        {
            $cartObject = $client->getCartById($cartId);

            $cartObject = json_decode($cartObject, true);

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_CART_FETCH,
                [
                    'type' => 'cart_fetch_success',
                    'cart' => $cartObject,
                ]
            );

            return $cartObject['cart'];
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_CART_API_ERROR,
                [
                    'type' => 'cart_fetch_api_failed',
                    'error' => $e->getMessage(),
                ]
            );

            $this->monitoring->addTraceCount(Metric::CART_FETCH_API_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_CART_API_ERROR]);
        }

        $merchantId = $this->merchant->getId();

        $cartObject = $this->getCartFromCache($merchantId, $cartId);

        if (empty($cartObject) === true)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_CACHE_CART_FETCH_FAIL,
                [
                    'type' => 'cart_fetch_from_cache_failed'
                ]
            );
        }

        return $cartObject ?? [];
    }

    public function setCartToCache(string $merchantId, $cartObject)
    {
        return $this->cache->set(
            $this->getCacheKeyForStoreCart($merchantId, $cartObject['token']),
            $cartObject,
            self::CART_CACHE_KEY_TTL
        );
    }

    public function getCartFromCache(string $merchantId, string $cartToken)
    {
        return $this->cache->get($this->getCacheKeyForStoreCart($merchantId, $cartToken));
    }

    protected function getCacheKeyForStoreCart(string $merchantId, string $cartToken)
    {
        return self::CART_CACHE_KEY . ':' . $merchantId . ':' .$cartToken;
    }

    protected function getShopifyClientByMerchant()
    {
        $creds = $this->getShopifyAuthByMerchant();
        if (empty($creds) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED);
        }
        return new Client($creds);
    }

    protected function getShopifyAuthByMerchant()
    {
        return (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());
    }

}
