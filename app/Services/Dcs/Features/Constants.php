<?php

namespace RZP\Services\Dcs\Features;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
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
    const CheckoutSavedCardsFeatureDisabled = 'checkout_saved_cards_feature_disabled';
    const CheckoutPaymentRetryDisabled = 'checkout_payment_retry_disabled';
    const CheckoutInternationalizationDisabled = 'checkout_internationalization_disabled';
    const CheckoutGooglePayEnabled = 'checkout_google_pay_enabled';
    const CheckoutConfigEnabled = 'checkout_config_enabled';
    const RewardsOnMxDashboardEnabled = 'rewards_on_mx_dashboard_enabled';
    const CheckoutOrgLogoEnabled = 'checkout_org_logo_enabled';
    const CheckoutCollectCustomerAddressEnabled = 'checkout_collect_customer_address_enabled';
    const CheckoutTruecallerLoginDisabled = 'checkout_truecaller_login_disabled';
    const CheckoutTruecallerLoginOnContactScreenDisabled = 'checkout_truecaller_login_on_contact_screen_disabled';
    const CheckoutTruecallerLoginOnHomeScreenDisabled = 'checkout_truecaller_login_on_home_screen_disabled';
    const CheckoutTruecallerLoginOnMobileWebDisabled = 'checkout_truecaller_login_on_mobile_web_disabled';
    const CheckoutTruecallerLoginOnSdkDisabled = 'checkout_truecaller_login_on_sdk_disabled';
    const CheckoutTruecallerLoginOnAddCardScreenDisabled = 'checkout_truecaller_login_on_add_card_screen_disabled';
    const CheckoutTruecallerLoginOnSavedCardsScreenDisabled = 'checkout_truecaller_login_on_saved_cards_screen_disabled';
    const SubMerchantOnboardDocUploadingDisabled = 'submerchant_onboarding_doc_uploading_disabled';
    const KycHandledByPartner = 'kyc_handled_by_partner_enabled';
    const SubMerchantOnboardingCommunicationDisabled = 'submerchant_onboarding_communication_disabled';
    const RazorpayCommunicationToSubMerchantDisabled = 'razorpay_communication_to_submerchant_disabled';
    const AggregatorOAuthClientDisabled = 'aggregator_oauth_client_enabled';
    const OAuthCommunicationDisabled = 'oauth_communication_disabled';
    const SubMerchantQRImageContentEnabled = 'submerchant_qr_image_content_enabled';
    const SubMerchantOnBoardingV2Enabled  = 'submerchant_onboarding_v2_enabled';
    const SubMerchantOnBoardingEnabled = 'submerchant_onboarding_enabled';
    const WebsiteInternationalDisabled = 'website_international_disabled';
    const AdminLeadPartnerInviteEnabled = 'admin_lead_partner_invite_enabled';

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
        self::CheckoutSavedCardsFeatureDisabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutPaymentRetryDisabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutInternationalizationDisabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutGooglePayEnabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutConfigEnabled => "rzp/pg/merchant/checkout/Features",
        self::RewardsOnMxDashboardEnabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutOrgLogoEnabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutCollectCustomerAddressEnabled => "rzp/pg/merchant/checkout/Features",
        self::CheckoutTruecallerLoginDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnContactScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnHomeScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnMobileWebDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnSdkDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnAddCardScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::CheckoutTruecallerLoginOnSavedCardsScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::SubMerchantOnboardDocUploadingDisabled => "rzp/platform/partner/onboarding/Features",
        self::KycHandledByPartner => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantOnboardingCommunicationDisabled => "rzp/platform/partner/communication/Features",
        self::RazorpayCommunicationToSubMerchantDisabled => "rzp/platform/partner/communication/Features",
        self::AggregatorOAuthClientDisabled => "rzp/platform/partner/auth/Features",
        self::OAuthCommunicationDisabled => "rzp/platform/partner/communication/Features",
        self::SubMerchantQRImageContentEnabled => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantOnBoardingV2Enabled  => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantOnBoardingEnabled => "rzp/platform/partner/onboarding/Features",
        self::WebsiteInternationalDisabled => "rzp/platform/partner/onboarding/Features",
        self::AdminLeadPartnerInviteEnabled => "rzp/platform/partner/onboarding/Features",
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
        APIFeaturesConstants::NOFLASHCHECKOUT => self::CheckoutSavedCardsFeatureDisabled,
        APIFeaturesConstants::CHECKOUT_DISABLE_RETRY => self::CheckoutPaymentRetryDisabled,
        APIFeaturesConstants::CHECKOUT_DISABLE_I18N => self::CheckoutInternationalizationDisabled,
        APIFeaturesConstants::GOOGLE_PAY => self::CheckoutGooglePayEnabled,
        APIFeaturesConstants::PAYMENT_CONFIG_ENABLED => self::CheckoutConfigEnabled,
        APIFeaturesConstants::REWARD_MERCHANT_DASHBOARD => self::RewardsOnMxDashboardEnabled,
        APIFeaturesConstants::ORG_CUSTOM_CHECKOUT_LOGO => self::CheckoutOrgLogoEnabled,
        APIFeaturesConstants::CUSTOMER_ADDRESS => self::CheckoutCollectCustomerAddressEnabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN => self::CheckoutTruecallerLoginDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN => self::CheckoutTruecallerLoginOnContactScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_HOME_SCREEN => self::CheckoutTruecallerLoginOnHomeScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_MWEB => self::CheckoutTruecallerLoginOnMobileWebDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SDK => self::CheckoutTruecallerLoginOnSdkDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN => self::CheckoutTruecallerLoginOnAddCardScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN => self::CheckoutTruecallerLoginOnSavedCardsScreenDisabled,
        APIFeaturesConstants::SUBM_NO_DOC_ONBOARDING => self::SubMerchantOnboardDocUploadingDisabled,
        APIFeaturesConstants::KYC_HANDLED_BY_PARTNER => self::KycHandledByPartner,
        APIFeaturesConstants::SKIP_SUBM_ONBOARDING_COMM => self::SubMerchantOnboardingCommunicationDisabled,
        APIFeaturesConstants::NO_COMM_WITH_SUBMERCHANTS => self::RazorpayCommunicationToSubMerchantDisabled,
        APIFeaturesConstants::AGGREGATOR_OAUTH_CLIENT => self::AggregatorOAuthClientDisabled,
        APIFeaturesConstants::SKIP_OAUTH_NOTIFICATION => self::OAuthCommunicationDisabled,
        APIFeaturesConstants::SUBM_QR_IMAGE_CONTENT => self::SubMerchantQRImageContentEnabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING_V2 => self::SubMerchantOnBoardingV2Enabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING => self::SubMerchantOnBoardingEnabled,
        APIFeaturesConstants::SKIP_WEBSITE_INTERNAT => self::WebsiteInternationalDisabled,
        APIFeaturesConstants::ADMIN_LEAD_PARTNER => self::AdminLeadPartnerInviteEnabled,
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
        self::CheckoutSavedCardsFeatureDisabled => APIFeaturesConstants::NOFLASHCHECKOUT,
        self::CheckoutPaymentRetryDisabled => APIFeaturesConstants::CHECKOUT_DISABLE_RETRY,
        self::CheckoutInternationalizationDisabled => APIFeaturesConstants::CHECKOUT_DISABLE_I18N,
        self::CheckoutGooglePayEnabled => APIFeaturesConstants::GOOGLE_PAY,
        self::CheckoutConfigEnabled => APIFeaturesConstants::PAYMENT_CONFIG_ENABLED,
        self::RewardsOnMxDashboardEnabled => APIFeaturesConstants::REWARD_MERCHANT_DASHBOARD,
        self::CheckoutOrgLogoEnabled => APIFeaturesConstants::ORG_CUSTOM_CHECKOUT_LOGO,
        self::CheckoutCollectCustomerAddressEnabled => APIFeaturesConstants::CUSTOMER_ADDRESS,
        self::CheckoutTruecallerLoginDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN,
        self::CheckoutTruecallerLoginOnContactScreenDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN,
        self::CheckoutTruecallerLoginOnHomeScreenDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_HOME_SCREEN,
        self::CheckoutTruecallerLoginOnMobileWebDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_MWEB,
        self::CheckoutTruecallerLoginOnSdkDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SDK,
        self::CheckoutTruecallerLoginOnAddCardScreenDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN,
        self::CheckoutTruecallerLoginOnSavedCardsScreenDisabled => APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN,
        self::SubMerchantOnboardDocUploadingDisabled => APIFeaturesConstants::SUBM_NO_DOC_ONBOARDING,
        self::KycHandledByPartner => APIFeaturesConstants::KYC_HANDLED_BY_PARTNER,
        self::SubMerchantOnboardingCommunicationDisabled => APIFeaturesConstants::SKIP_SUBM_ONBOARDING_COMM,
        self::RazorpayCommunicationToSubMerchantDisabled => APIFeaturesConstants::NO_COMM_WITH_SUBMERCHANTS,
        self::AggregatorOAuthClientDisabled => APIFeaturesConstants::AGGREGATOR_OAUTH_CLIENT,
        self::OAuthCommunicationDisabled => APIFeaturesConstants::SKIP_OAUTH_NOTIFICATION,
        self::SubMerchantQRImageContentEnabled => APIFeaturesConstants::SUBM_QR_IMAGE_CONTENT,
        self::SubMerchantOnBoardingV2Enabled  => APIFeaturesConstants::SUBMERCHANT_ONBOARDING_V2,
        self::SubMerchantOnBoardingEnabled => APIFeaturesConstants::SUBMERCHANT_ONBOARDING,
        self::WebsiteInternationalDisabled => APIFeaturesConstants::SKIP_WEBSITE_INTERNAT,
        self::AdminLeadPartnerInviteEnabled => APIFeaturesConstants::ADMIN_LEAD_PARTNER,
    ];

    /**
     * Stores the mapping of the Merchant features to their corresponding handlers
     */
    public static $dcsNewMerchantFeatures = [
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
        self::AdminLeadPartnerInviteEnabled => 'direct',
    ];

    /**
     * Stores the mapping of the Prg features to their corresponding handlers
     */
    public static $dcsNewOrgFeatures = [];

    public static function dcsReadEnabledFeaturesByEntityType(string $entityType = null, bool $withDcsNames = false): array
    {

        $adminService = new AdminService;

        $dcsReadEnabledFeatures = $adminService->getConfigKey(['key' => ConfigKey::DCS_READ_WHITELISTED_FEATURES]);
        if ($dcsReadEnabledFeatures === null)
        {
            return [];
        }
        if (($entityType === Type::PARTNER) || ($entityType === Type::MERCHANT) )
        {
            $featureNames = $dcsReadEnabledFeatures[Type::MERCHANT]?:[];
        }
        elseif ($entityType === Type::ORG)
        {
            $featureNames =  $dcsReadEnabledFeatures[Type::ORG]?:[];
        }
        else
        {
            $featureNames = array_merge($dcsReadEnabledFeatures[Type::ORG]?:[], $dcsReadEnabledFeatures[Type::MERCHANT]?:[]);
        }

        if ($withDcsNames === true)
        {
            return self::getDcsFeatureNamesFromApiNames($featureNames);
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

    public static function getDcsFeatureNamesFromApiNames($featureNames) :array
    {
        $dcsFeatureNames = [];
        foreach ($featureNames as $featureName => $value)
        {
            if (key_exists($featureName, self::$apiFeatureNameToDCSFeatureName) === true)
            {
                $dcsFeatureNames[self::$apiFeatureNameToDCSFeatureName[$featureName]] = $value;
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
        return (key_exists($featureName, self::dcsReadEnabledFeaturesByEntityType("", $isDcsName)) === true);
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
