<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const RZP_DOMAIN_URL_VALIDATION_MESSAGE = "Domain should not belong to razorpay.";
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
        "domain_url"                     => 'sometimes|url',
        "order_status_update"            => 'sometimes|url',
        "manual_control_cod_order"       => 'sometimes|boolean'
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
        "domain_url"                     => 'sometimes|url',
        "manual_control_cod_order"       => 'sometimes|boolean'
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
}
