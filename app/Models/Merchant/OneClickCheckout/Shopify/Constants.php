<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

class Constants
{
    const SHOPIFY_TEMP_RECEIPT = 'Order Pending';

    // common auth keys
    const API_KEY = 'api_key';
    const API_SECRET = 'api_secret';
    const OAUTH_TOKEN = 'oauth_token';
    const ACCESS_TOKEN = 'access_token';
    const REFRESH_TOKEN = 'refresh_token';

    const SHOPIFY = 'shopify';

    const GID_PRODUCT = 'gid://shopify/Product/';
    const GID_PRODUCT_VARIANT = 'gid://shopify/ProductVariant/';
    const GID_CHECKOUT = 'gid://shopify/Checkout/';
    const GID_ORDER = 'gid://shopify/Order/';
    const GID_DISCOUNT = 'gid://shopify/DiscountCodeNode/';
    const MY_SHOPIFY = '.myshopify.com';
    const SHOPIFY_ORDER_ID = 'shopify_order_id';
    const TAG_HOLD = 'hold';

    const EVENT_TYPE            = 'event_type';
    const EVENT_TIME            = 'event_time';
    const EVENT_SOURCE_URL      = 'event_source_url';
    const PRODUCTS              = 'products';
    const ID                    = 'id';
    const SKU                   = 'sku';
    const NAME                  = 'name';
    const QUANTITY              = 'quantity';
    const PRICE                 = 'price';
    const VARIANT               = 'variant';
    const VARIANT_TITLE         = 'variant_title';
    const POSITION              = 'position';
    const CHECKOUT              = 'checkout';
    const CHECKOUT_EVENT        = 'checkout_event';
    const PURCHASE              = 'purchase';
    const PURCHASE_EVENT        = 'purchase_event';
    const TITLE                 = 'title';
    const CUSTOMER_INFO         = 'customer_info';
    const EMAIL                 = 'email';
    const PHONE                 = 'phone';
    const CLIENT_ID             = 'client_id';
    const TOTAL_REVENUE         = 'total_revenue';
    const TOTAL_TAX             = 'total_tax';
    const AMOUNT                = 'amount';
    const TOTAL_SHIPPING        = 'total_shipping';
    const TRANSACTION_COUPON    = 'transaction_coupon';
    const TRANSACTION_ID        = 'transaction_id';
    const MERCHANT_ID           = 'merchant_id';
    const SHIPPING_FEE          = 'shipping_fee';
    const PROMOTIONS            = 'promotions';
    const GA_ID                 = 'ga_id';
    const USER_AGENT            = 'user_agent';
    const FB_ANALYTICS          = 'fb_analytics';
    const PROVIDER_TYPE_LIST         = 'provider_type_list';
    const GOOGLE_UNIVERSAL_ANALYTICS = 'google_universal_analytics';
    const SHOPIFY_CHECKOUT_ID        = 'shopify_checkout_id';
    const SHOPIFY_CHECKOUT_CURRENCY  = 'shopify_checkout_currency';
    const STOREFRONT_ID              = 'storefront_id';
    const NOTES                      = 'notes';
    const PARTIALLY_PAID_ORDER       = 'partially_paid_order';
    const EVENT_NAME                 = 'name';
    const EVENT_ID                   = 'id';
    const EVENT_TRIGGER_METHOD       = 'trigger_method';
    const TIME                       = 'time';
    const EVENT                      =  'event';
    const EVENT_CATEGORY             = 'category';
    const EVENT_PAGE_TITLE           = 'page_title';
    const CHECKOUT_ID                = 'checkout_id';
    const EVENT_TRIGGER_SOURCE       = 'event_trigger_source';
    const ORDER_ID                   = 'order_id';
    const CUSTOMER                   = 'customer';
    const ORDER                      = 'order';
    const VARIANT_ID                 = 'variant_id';
    const PRODUCT_ID                 = 'product_id';
    const PRODUCT_NAME               = 'product_name';
    const VARIANT_NAME               = 'variant_name';
    const SHOPIFY_ORDER_NAME         = 'name';
    const RZP_ORDER_ID               = 'rzp_order_id';
    const TOTAL_PRICE                = 'total_price';
    const CURRENCY                   = 'currency';
    const COUPON_CODE                = 'coupon_code';
    const LINE_ITEMS                 = 'line_items';
    const PLATFORM_ORDER_NAME        = 'platform_order_name';
    const PLATFORM_ORDER_ID          = 'platform_order_id';
    const SHIPPING_ADDRESS           =  'shipping_address';
    const ADDRESS                    = 'address';
    const CITY                       = 'city';
    const ZIPCODE                    = 'zipcode';
    const COUNTRY                    = 'country';
    const CONTACT                    = 'contact';
}
