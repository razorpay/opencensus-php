<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

class Constants
{
    const SHOPIFY_TEMP_RECEIPT = 'Order Pending';

    // common auth keys
    const API_KEY             = 'api_key';
    const API_SECRET          = 'api_secret';
    const OAUTH_TOKEN         = 'oauth_token';
    const ACCESS_TOKEN        = 'access_token';
    const REFRESH_TOKEN       = 'refresh_token';

    const GID_PRODUCT         = 'gid://shopify/Product/';
    const GID_PRODUCT_VARIANT = 'gid://shopify/ProductVariant/';
    const GID_CHECKOUT        = 'gid://shopify/Checkout/';
    const MY_SHOPIFY          = '.myshopify.com';
}
