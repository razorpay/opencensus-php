<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\OneClickCheckout\Constants;

class Validator extends Base\Validator
{
    const RZP_DOMAIN_URL_VALIDATION_MESSAGE = "Domain should not belong to razorpay.";
    const MERCHANT_CONFIG_KEY_NOT_ALLOWED   = 'No Such Merchant Config Key Allowed';

    protected static $nativeRules = [
        "shipping_info"                  => 'sometimes|url|custom:non_rzp_domain',
        "list_promotions"                => 'sometimes|url|custom:non_rzp_domain',
        "apply_promotion"                => 'sometimes|url|custom:non_rzp_domain',
        "cod_slabs"                      => 'sometimes|array',
        "shipping_slabs"                 => 'sometimes|array',
        "cod_intelligence"               => 'sometimes|boolean',
        'platform'                       => 'required|in:native,woocommerce,shopify,magento',
        "one_cc_auto_fetch_coupons"      => 'sometimes|boolean',
        "one_cc_international_shipping"  => 'sometimes|boolean',
        "one_cc_capture_billing_address" => 'sometimes|boolean',
        'one_cc_gift_card'               => 'sometimes|boolean',
        'one_cc_buy_gift_card'           => 'sometimes|boolean',
        'one_cc_multiple_gift_card'      => 'sometimes|boolean',
        'one_cc_gift_card_cod_restrict'  => 'sometimes|boolean',
        'one_cc_gift_card_restrict_coupon' => 'sometimes|boolean',
        "domain_url"                     => 'sometimes|url',
        "order_status_update"            => 'sometimes|url',
        "manual_control_cod_order"       => 'sometimes|boolean',
        "one_cc_capture_gstin"           => 'sometimes|boolean',
        "one_cc_capture_order_instructions"  => 'sometimes|boolean',
    ];

    protected static $shopifyRules = [
        'shop_id'                        => 'required|string',
        "cod_intelligence"               => 'sometimes|boolean',
        "one_click_checkout"             => 'sometimes|boolean',
        "one_cc_ga_analytics"            => 'sometimes|boolean',
        "one_cc_fb_analytics"            => 'sometimes|boolean',
        "one_cc_buy_now_button"          => 'sometimes|boolean',
        "one_cc_auto_fetch_coupons"      => 'sometimes|boolean',
        "one_cc_international_shipping"  => 'sometimes|boolean',
        "one_cc_capture_billing_address" => 'sometimes|boolean',
        'one_cc_gift_card'               => 'sometimes|boolean',
        'one_cc_buy_gift_card'           => 'sometimes|boolean',
        'one_cc_multiple_gift_card'      => 'sometimes|boolean',
        'one_cc_gift_card_cod_restrict'  => 'sometimes|boolean',
        'one_cc_gift_card_restrict_coupon' => 'sometimes|boolean',
        "domain_url"                     => 'sometimes|url',
        "manual_control_cod_order"       => 'sometimes|boolean',
        "one_cc_capture_gstin"           => 'sometimes|boolean',
        "one_cc_capture_order_instructions"   => 'sometimes|boolean',
        "cod_engine"                     => 'sometimes|boolean',
        "cod_engine_type"                => 'sometimes|string|in:slab_eligibility,slab_charges,location,product'
    ];

    protected static $shippingProviderRules = [
        'shipping_provider_id'     => 'required|string|size:14',
        'provider_type'            => 'required|string',
        'enable_cod'               => 'required|boolean',
        'cod_fee_rule'             => 'required_if:enable_cod,true',
        'shipping_fee_rule'        => 'required',
        'warehouse_pincode'        => 'sometimes|string|size:6',
        'merchant_id'              => 'required|string|size:14',
   ];

    protected static $gettingShopifyConfigRules = [
         'key_id'                        => 'required|string',
         'keys'                          => 'sometimes|string|custom:keys',
    ];

    protected static $get1ccAddressIngestionConfigRules = [
        'platform' => 'required|string|in:woocommerce',
        'keys' => 'sometimes|array',
        'keys.*' => 'distinct|string|in:one_click_checkout,one_cc_address_sync_off,job',
    ];

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateNonRzpDomain($attribute, $url)
    {
        if (DomainUtils::verifyNonRZPDomain($url) === false)
        {
            throw new BadRequestValidationFailureException(self::RZP_DOMAIN_URL_VALIDATION_MESSAGE);
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateKeys($attribute, $keys)
    {
        $allowedConfigs = Constants::CONFIG_FLAGS;

        $allowedAuthConfigKeys = Constants::SHOPIFY_AUTH;

        $keysRequested = explode(',', $keys);

        foreach ($keysRequested as $key)
        {
            if (in_array($key, $allowedConfigs) === false && in_array($key, $allowedAuthConfigKeys) === false)
            {
                throw new BadRequestValidationFailureException(self::MERCHANT_CONFIG_KEY_NOT_ALLOWED);
            }
        }
    }
}
