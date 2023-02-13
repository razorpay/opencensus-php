<?php

namespace RZP\Services\Dcs\Features;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Feature\Constants as APIFeaturesConstants;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

class Constants
{
    const RefundEnabled = 'refund_enabled';
    const DisableAutoRefund = 'disable_auto_refund';
    const EligibilityEnabled = 'eligibility_enabled';
    const ShowEmailOnCheckout = 'show_email_on_checkout';
    const EmailOptionalOnCheckout = 'email_optional_oncheckout';
    const AutoCommissionInvoiceDisabled = 'auto_invoice_generation_disabled';
    const AffordabilityWidgetSet = 'affordability_widget_set';
    const EnableMerchantExpiryForPP = 'payment_page_no_expiry_enabled';
    const EnableMerchantExpiryForPL = 'payment_link_no_expiry_enabled';
    const EnableMerchantCreateOwnTemplate = 'payment_page_create_own_template_enabled';
    const EnableCustomerAmount = 'payment_page_customer_decide_amount_enabled';
    const ReceiptUniqueEnabled = 'receipt_unique_enabled';
    const CartAmountCheckEnabled = 'cart_amount_check_enabled';
	const AllowPaymentsOnPaidOrder = 'allow_payments_on_paid_order';
	const ExcessOrderAmountEnabled = 'excess_order_amount_enabled';
	const DcsPaymentMailsDisabled = 'payment_mails_disabled';
	const FreeCreditUnregDisabled = 'free_credit_unreg_disabled';
	const AsyncBalanceUpdateEnabled = 'async_balance_update_enabled';
	const AsyncTransactionUpdateEnabled = 'async_transaction_update_enabled';
	const AutoRefundsDisabled = 'auto_refunds_disabled';
    const EnableRoutePartnerships = 'route_for_partnerships_enabled';
	const ImportSettlement = 'import_settlement';

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
        self::EnableMerchantExpiryForPP => "rzp/nocode/merchant/paymentpage/Features",
        self::EnableMerchantExpiryForPL => "rzp/nocode/merchant/paymentlink/Features",
        self::EnableMerchantCreateOwnTemplate => "rzp/nocode/merchant/paymentpage/Features",
        self::EnableCustomerAmount => "rzp/nocode/merchant/paymentpage/Features",
        self::ReceiptUniqueEnabled => "rzp/pg/merchant/order/Features",
        self::CartAmountCheckEnabled => "rzp/pg/merchant/order/cart/Features",
        self::AllowPaymentsOnPaidOrder =>"rzp/pg/merchant/order/payments/Features",
        self::ExcessOrderAmountEnabled => "rzp/pg/merchant/order/payments/Features",
        self::DcsPaymentMailsDisabled => "rzp/pg/merchant/payments/communication/Features",
        self::FreeCreditUnregDisabled => "rzp/pg/org/payments/credits/Features",
        self::AsyncBalanceUpdateEnabled => "rzp/pg/merchant/payments/ledger/Features",
        self::AsyncTransactionUpdateEnabled => "rzp/pg/merchant/payments/ledger/Features",
        self::AutoRefundsDisabled => "rzp/pg/merchant/payments/refunds/Features",
        self::EnableRoutePartnerships => "rzp/platform/partner/route/Features",
        self::ImportSettlement => "rzp/pg/merchant/settlements/OPGSPFeatures",
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
        APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PL => self::EnableMerchantExpiryForPL,
        APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PP => self::EnableMerchantExpiryForPP,
        APIFeaturesConstants::ENABLE_CREATE_OWN_TEMPLATE => self::EnableMerchantCreateOwnTemplate,
        APIFeaturesConstants::ENABLE_CUSTOMER_AMOUNT => self::EnableCustomerAmount,
        APIFeaturesConstants::ORDER_RECEIPT_UNIQUE => self::ReceiptUniqueEnabled,
        APIFeaturesConstants::CART_API_AMOUNT_CHECK => self::CartAmountCheckEnabled,
        APIFeaturesConstants::DISABLE_AMOUNT_CHECK => self::AllowPaymentsOnPaidOrder,
        APIFeaturesConstants::EXCESS_ORDER_AMOUNT => self::ExcessOrderAmountEnabled,
        APIFeaturesConstants::PAYMENT_MAILS_DISABLED => self::DcsPaymentMailsDisabled,
        APIFeaturesConstants::DISABLE_FREE_CREDIT_UNREG => self::FreeCreditUnregDisabled,
        APIFeaturesConstants::ASYNC_BALANCE_UPDATE => self::AsyncBalanceUpdateEnabled,
        APIFeaturesConstants::ASYNC_TXN_FILL_DETAILS => self::AsyncTransactionUpdateEnabled,
        APIFeaturesConstants::DISABLE_AUTO_REFUNDS => self::AutoRefundsDisabled,
        APIFeaturesConstants::ROUTE_PARTNERSHIPS => self::EnableRoutePartnerships,
        self::ImportSettlement => self::ImportSettlement,
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
        self::ReceiptUniqueEnabled => APIFeaturesConstants::ORDER_RECEIPT_UNIQUE,
        self::CartAmountCheckEnabled => APIFeaturesConstants::CART_API_AMOUNT_CHECK,
        self::AllowPaymentsOnPaidOrder => APIFeaturesConstants::DISABLE_AMOUNT_CHECK,
        self::ExcessOrderAmountEnabled => APIFeaturesConstants::EXCESS_ORDER_AMOUNT,
        self::DcsPaymentMailsDisabled => APIFeaturesConstants::PAYMENT_MAILS_DISABLED,
        self::FreeCreditUnregDisabled => APIFeaturesConstants::DISABLE_FREE_CREDIT_UNREG,
        self::AsyncBalanceUpdateEnabled => APIFeaturesConstants::ASYNC_BALANCE_UPDATE,
        self::AsyncTransactionUpdateEnabled => APIFeaturesConstants::ASYNC_TXN_FILL_DETAILS,
        self::AutoRefundsDisabled => APIFeaturesConstants::DISABLE_AUTO_REFUNDS,
        self::AutoCommissionInvoiceDisabled => APIFeaturesConstants::AUTO_COMM_INV_DISABLED,
        self::EnableMerchantExpiryForPL => APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PL,
        self::EnableMerchantExpiryForPP => APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PP,
        self::EnableMerchantCreateOwnTemplate => APIFeaturesConstants::ENABLE_CREATE_OWN_TEMPLATE,
        self::EnableCustomerAmount => APIFeaturesConstants::ENABLE_CUSTOMER_AMOUNT,
        self::EnableRoutePartnerships => APIFeaturesConstants::ROUTE_PARTNERSHIPS,
        self::ImportSettlement => self::ImportSettlement,
    ];

    /**
     * Stores the mapping of the Merchant features to their corresponding handlers
     */
    public static $dcsNewFeatures = [
        self::RefundEnabled => 'direct',
        self::DisableAutoRefund => 'direct',
        self::EligibilityEnabled => 'client',
        self::AutoCommissionInvoiceDisabled => 'direct',
        self::AffordabilityWidgetSet => 'client',
        self::EnableMerchantExpiryForPL => 'direct',
        self::EnableMerchantExpiryForPP => 'direct',
        self::EnableMerchantCreateOwnTemplate => 'direct',
        self::EnableCustomerAmount => 'direct',
        self::EnableRoutePartnerships => 'direct',
        self::ImportSettlement => 'direct',
    ];

    /**
     * Stores the mapping of the Prg features to their corresponding handlers
     */
    public static $dcsNewOrgFeatures = [];

    public static function dcsReadEnabledFeaturesByEntityType(string $entityType = null, bool $withDcsNames = false): array
    {
        if (($entityType === Type::PARTNER) || ($entityType === Type::MERCHANT) )
        {
            $featureNames = self::$dcsNewFeatures;
        }
        elseif ($entityType === Type::ORG)
        {
            $featureNames = self::$dcsNewOrgFeatures;
        }
        else
        {
            $featureNames = array_merge(self::$dcsNewFeatures, self::$dcsNewOrgFeatures);
        }

        if ($withDcsNames === false)
        {
            return self::getAPIFeatureNamesFromDcsNames($featureNames);
        }

        return  $featureNames;
    }

    public static function getAPIFeatureNamesFromDcsNames(array $featureNames) :array
    {
        $apiFeatureNames = [];
        foreach ($featureNames as $featureName => $value)
        {
            if (key_exists($featureName, self::$dcsFeatureNameToAPIFeatureName) === true)
            {
                $apiFeatureNames[self::$dcsFeatureNameToAPIFeatureName[$featureName]] = $value;
            }
        }
        return $apiFeatureNames;
    }

    public static function getDcsFeatureNamesFromApiNames(array $featureNames) :array
    {
        $dcsFeatureNames = [];
        foreach ($featureNames as $featureName => $value)
        {
            if (key_exists($featureName, self::$dcsFeatureNameToAPIFeatureName) === true)
            {
                $apiFeatureNames[self::$apiFeatureNameToDCSFeatureName[$featureName]] = $value;
            }
        }
        return $dcsFeatureNames;
    }

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
        if (key_exists($name, self::$dcsFeatureNameToAPIFeatureName) === false) {
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

    public static function isDcsNewFeature($featureName, $isDcsName = false)
    {
        if ($isDcsName === true) {
            return (key_exists($featureName, DcsConstants::$dcsNewFeatures) === true ||
                key_exists($featureName, DcsConstants::$dcsNewOrgFeatures) === true);
        }

        if (key_exists($featureName, self::$apiFeatureNameToDCSFeatureName) === true)
        {
            return key_exists(self::$apiFeatureNameToDCSFeatureName[$featureName], DcsConstants::$dcsNewFeatures) ||
                key_exists(self::$apiFeatureNameToDCSFeatureName[$featureName], DcsConstants::$dcsNewOrgFeatures);
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
