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
    const INVALID_PREPAY_COD_CONFIGS        = "Invalid prepay cod configs request";
    const INVALID_DATA_TYPE                 = "Invalid data type";
    const INVALID_DISCOUNT_CONFIGS          = "Invalid discount configs";


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
        'one_cc_prepay_cod_conversion'       => 'sometimes|array',
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
        "cod_engine_type"                => 'sometimes|string|in:slab_eligibility,slab_charges,location,product',
        'one_cc_prepay_cod_conversion'   => 'sometimes|array',
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

    protected static $gettingShopifyConfigByKeyIdRules = [
        'key_id' => 'required|string',
        'keys'   => 'sometimes|string|custom:keys',
    ];

    protected static $gettingShopifyConfigByShopIdRules = [
        'shop_id' => 'required|string',
        'mode'    => 'sometimes|string|in:live,test',
        'keys'    => 'sometimes|string|custom:keys',
    ];

    protected static $gettingShopifyConfigByMerchantIdRules = [
        'merchant_id' => 'required|string|size:14',
        'mode'        => 'sometimes|string|in:live,test',
        'keys'        => 'sometimes|string|custom:keys',
    ];

    protected static $gettingWoocommerceConfigRules = [
        'merchant_id'     => 'required|string|size:14',
        'keys'            => 'sometimes|string|custom:woocommerce_keys',
        'mode'            => 'sometimes|string|in:live,test',
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

    protected static $prepayCodConversionRules = [
        Constants::ENABLED       => 'required|boolean',
        Constants::CONFIGS       => 'required_if:enabled,true|array|custom:prepay_configs',
        Constants::CONFIGS.'.'.Constants::DISCOUNT          => 'required_with:configs|array',
        Constants::CONFIGS.'.'.Constants::RISK_CATEGORY     => 'required_with:configs|array|in:high,medium,low',
        Constants::CONFIGS.'.'.Constants::COMMUNICATION     => 'required_with:configs|array',
        Constants::CONFIGS.'.'.Constants::DISCOUNT.'.'.Constants::TYPE                  => 'required_with:configs.discount|string|in:flat,zero,percentage',
        Constants::CONFIGS.'.'.Constants::DISCOUNT.'.'.Constants::MAX_DISCOUNT          => 'required_with:configs.discount|integer',
        Constants::CONFIGS.'.'.Constants::DISCOUNT.'.'.Constants::DISCOUNT_PERCENTAGE   => 'required_with:configs.discount|integer|min:0|max:99',
        Constants::CONFIGS.'.'.Constants::DISCOUNT.'.'.Constants::MINIMUM_ORDER_VALUE   => 'required_with:configs.discount|integer',
        Constants::CONFIGS.'.'.Constants::COMMUNICATION.'.'.Constants::EXPIRE_SECONDS   => 'required_with:configs.communication|integer',
        Constants::CONFIGS.'.'.Constants::COMMUNICATION.'.'.Constants::METHODS          => 'required_with:configs.communication|array|in:whatsapp,checkout'
    ];

    /**
     * @throws BadRequestValidationFailureException
     * @throws \RZP\Exception\ExtraFieldsException
     */
    public function validatePrepayConfigs($attribute ,array $input)
    {
        $this->validatePrepayKeys($input);

        $discount = $input[Constants::DISCOUNT];

        $discountException= new BadRequestValidationFailureException(
            self::INVALID_PREPAY_COD_CONFIGS.':'.self::INVALID_DISCOUNT_CONFIGS,
            Constants::DISCOUNT,
            $discount);


        if (!is_int($discount[Constants::MINIMUM_ORDER_VALUE]) ||
            !is_int($discount[Constants::MAX_DISCOUNT]) ||
            !is_int($discount[Constants::DISCOUNT_PERCENTAGE]) ||
        !is_int($input[Constants::COMMUNICATION][Constants::EXPIRE_SECONDS]))
        {
            throw new BadRequestValidationFailureException(
                self::INVALID_PREPAY_COD_CONFIGS .':'. self::INVALID_DATA_TYPE,
                Constants::CONFIGS);
        }

        switch ($discount[Constants::TYPE])
        {
            case Constants::PERCENTAGE:
                if ($discount[Constants::MINIMUM_ORDER_VALUE] === 0 ||
                    $discount[Constants::DISCOUNT_PERCENTAGE] === 0)
                {
                    throw $discountException;
                }
                break;
            case Constants::FLAT:
                if ( $discount[Constants::MINIMUM_ORDER_VALUE] <= $discount[Constants::MAX_DISCOUNT] )
                {
                    throw $discountException;
                }
                break;
        }
    }

    /**
     * @throws \RZP\Exception\ExtraFieldsException
     */
    protected function validatePrepayKeys($input)
    {
        $invalidKeys = array_keys(array_diff_key($input, [
                Constants::DISCOUNT      => '',
                Constants::COMMUNICATION => '',
                Constants::RISK_CATEGORY => '']
        ));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }

        $discount = $input[Constants::DISCOUNT];
        $invalidKeys = array_keys(array_diff_key($discount, [
                Constants::TYPE => '',
                Constants::MAX_DISCOUNT => '',
                Constants::DISCOUNT_PERCENTAGE => '',
                Constants::MINIMUM_ORDER_VALUE => '']
        ));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }

        $communication = $input[Constants::COMMUNICATION];
        $invalidKeys = array_keys(array_diff_key($communication,
            [Constants::EXPIRE_SECONDS => '', Constants::METHODS=> '' ]));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateWoocommerceKeys($attribute, $keys)
    {
        $allowedAuthConfigKeys = Constants::WOOCOMMERCE_AUTH;

        $allowedConfigs = [Constants::DOMAIN_URL];

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
