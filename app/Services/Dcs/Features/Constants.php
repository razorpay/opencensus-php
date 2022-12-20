<?php

namespace RZP\Services\Dcs\Features;

use Example\Pg\Merchant\Refund\Features as RefundFeatures;
use Rzp\Pg\Merchant\Affordability\EligibilityFeatures;

use Razorpay\Dcs\Kv\V1\Model\V1Key;

class Constants
{
    const RefundEnabled = 'refund_enabled';
    const DisableAutoRefund = 'disable_auto_refund';
    const EligibilityEnabled = 'eligibility_enabled';
    const ShowEmailOnCheckout = 'show_email_on_checkout';
    const EmailOptionalOnCheckout = 'email_optional_oncheckout';

    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $featureToDCSKeyMapping = [
        self::RefundEnabled => "example/pg/merchant/refund/Features",
        self::DisableAutoRefund => "example/pg/merchant/refund/Features",
        self::EligibilityEnabled => "rzp/pg/merchant/affordability/EligibilityFeatures",
        self::ShowEmailOnCheckout => "rzp/pg/merchant/checkout/features/email/v1/EmailFieldCustomizationFeatures",
        self::EmailOptionalOnCheckout => "rzp/pg/merchant/checkout/features/email/v1/EmailFieldCustomizationFeatures",
    ];

    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $dcsNewFeatures = [
        self::EligibilityEnabled,
        self::EmailOptionalOnCheckout,
        self::ShowEmailOnCheckout,
    ];

    public static function isShadowFeature($variant)
    {
        if (($variant === 'on_client_shadow')  || ($variant === 'on_direct_dcs_shadow'))
        {
            return true;
        }
         return false;
    }

    public static function isReverseShadowFeature($variant)
    {
        if (($variant === 'on_client_rs') || ($variant === 'on_direct_dcs_rs'))
        {
            return true;
        }
        return false;
    }

    public static function isNewFeature($variant)
    {
        if (($variant === 'on_client_new') || ($variant === 'on_direct_dcs_new'))
        {
            return true;
        }
        return false;
    }
}
