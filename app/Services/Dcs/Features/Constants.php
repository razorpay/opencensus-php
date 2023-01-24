<?php

namespace RZP\Services\Dcs\Features;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Feature\Constants as APIFeaturesConstants;

class Constants
{
    const RefundEnabled = 'refund_enabled';
    const DisableAutoRefund = 'disable_auto_refund';
    const EligibilityEnabled = 'eligibility_enabled';
    const ShowEmailOnCheckout = 'show_email_on_checkout';
    const EmailOptionalOnCheckout = 'email_optional_oncheckout';
    const AutoCommissionInvoiceDisabled = 'auto_invoice_generation_disabled';
    const AffordabilityWidgetSet = 'affordability_widget_set';

    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $featureToDCSKeyMapping = [
        self::RefundEnabled => "example/pg/merchant/refund/Features",
        self::DisableAutoRefund => "example/pg/merchant/refund/Features",
        self::EligibilityEnabled => "rzp/pg/merchant/affordability/EligibilityFeatures",
        self::ShowEmailOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::EmailOptionalOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::AutoCommissionInvoiceDisabled => "rzp/platform/partner/commission/Features",
        self::AffordabilityWidgetSet => "rzp/pg/merchant/affordability/Widget",
    ];

    /**
     * Stores the mapping of the api feature name to their corresponding dcs feature names
     * This is required for migrating features.
     */
    public static $apiFeatureNameToDCSFeatureName = [
        self::RefundEnabled => self::RefundEnabled,
        self::DisableAutoRefund => self::DisableAutoRefund,
        self::EligibilityEnabled => self::EligibilityEnabled,
        self::ShowEmailOnCheckout => self::ShowEmailOnCheckout,
        self::EmailOptionalOnCheckout => self::EmailOptionalOnCheckout,
        APIFeaturesConstants::AUTO_COMM_INV_DISABLED => self::AutoCommissionInvoiceDisabled,
        self::AffordabilityWidgetSet => self::AffordabilityWidgetSet,
    ];

    /**
     * Stores the mapping of the dcs feature name to their corresponding api feature names
     * This is required for migrating features.
     */
    public static $dcsFeatureNameToAPIFeatureName = [
        self::RefundEnabled => self::RefundEnabled,
        self::DisableAutoRefund => self::DisableAutoRefund,
        self::EligibilityEnabled => self::EligibilityEnabled,
        self::ShowEmailOnCheckout => self::ShowEmailOnCheckout,
        self::EmailOptionalOnCheckout => self::EmailOptionalOnCheckout,
        self::AffordabilityWidgetSet => self::AffordabilityWidgetSet,
        self::AutoCommissionInvoiceDisabled => APIFeaturesConstants::AUTO_COMM_INV_DISABLED,
    ];

    /**
     * Stores the mapping of the features to their corresponding handlers
     */
    public static $dcsNewFeatures = [
        self::RefundEnabled => 'direct',
        self::DisableAutoRefund => 'direct',
        self::EligibilityEnabled => 'client',
        self::AutoCommissionInvoiceDisabled => 'direct',
        self::AffordabilityWidgetSet => 'client',
    ];

    public static function isShadowFeature($variant)
    {
        if (($variant === 'on_client_shadow')  || ($variant === 'on_direct_dcs_shadow'))
        {
            return true;
        }
         return false;
    }

    public static function dcsFeatureNameFromAPIName($name): string
    {
        if (key_exists($name, self::$apiFeatureNameToDCSFeatureName) === false) {
            $ex = new Exception\ServerErrorException('Dcs feature name missing in
            $apiFeatureNameToDCSFeatureName please check',
                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                "missing dcs feature name in the map");

            throw $ex;
        }

        return self::$apiFeatureNameToDCSFeatureName[$name];
    }

    public static function apiFeatureNameFromDcsName($name): string
    {
        if (key_exists($name, self::$apiFeatureNameToDCSFeatureName) === false) {
            $ex = new Exception\ServerErrorException('Dcs feature name missing in
            $dcsFeatureNameToAPIFeatureName please check with dcs team',
                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                "missing dcs feature name in the map");

            throw $ex;
        }

        return self::$dcsFeatureNameToAPIFeatureName[$name];
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
