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
    const UpiNumberDisabled = 'upi_number_disabled';
    const UpiNumberInUpiSectionDisabled = 'upi_number_in_upi_section_disabled';
    const UpiNumberInPreferredSectionDisabled = 'upi_number_in_preferred_section_disabled';
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
    const ManualSettlementForSubmerchants = 'manual_settlement_for_submerchants';
	  const ImportSettlement = 'import_settlement';
    const SavedCardsDisabled = 'saved_cards_disabled';
    const CvvLessFlowDisabled = 'cvv_less_flow_disabled';
    const PaymentRetryDisabled = 'payment_retry_disabled';
    const InternationalizationDisabled = 'internationalization_disabled';
    const GooglePayEnabled = 'google_pay_enabled';
    const ConfigEnabled = 'config_enabled';
    const RewardsOnMxDashboardEnabled = 'rewards_on_mx_dashboard_enabled';
    const OrgLogoEnabled = 'org_logo_enabled';
    const CollectCustomerAddressEnabled = 'collect_customer_address_enabled';
    const TruecallerLoginDisabled = 'truecaller_login_disabled';
    const TruecallerLoginOnContactScreenDisabled = 'truecaller_login_on_contact_screen_disabled';
    const TruecallerLoginOnHomeScreenDisabled = 'truecaller_login_on_home_screen_disabled';
    const TruecallerLoginOnMobileWebDisabled = 'truecaller_login_on_mobile_web_disabled';
    const TruecallerLoginOnSdkDisabled = 'truecaller_login_on_sdk_disabled';
    const TruecallerLoginOnAddCardScreenDisabled = 'truecaller_login_on_add_card_screen_disabled';
    const TruecallerLoginOnSavedCardsScreenDisabled = 'truecaller_login_on_saved_cards_screen_disabled';
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
    const SubmerchantInstantActivationViaV2ApiEnabled = 'submerchant_instant_activation_via_v2_api_enabled';
    const MerchantActivationByPartnerEnabled = 'merchant_activation_by_partner_enabled';
    const OverridingSubmerchantConfigEnabled = 'overriding_submerchant_config_enabled';
    const AdditionalFieldsHdfcOnboarding = 'additional_fields_hdfc_onboarding';
    const NoDocOnboardingEnabled = 'no_doc_onboarding_enabled';
    const OnboardedViaV2ApiEnabled = 'onboarded_via_v2_api_enabled';
    const BlockSendingOnboardingSms = 'block_sending_onboarding_sms';
    const AssumeSubAccount = 'assume_sub_account';
    const AssumeMasterAccount = 'assume_master_account';

    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $featureToDCSKeyMapping = [
        self::RefundEnabled => "example/pg/merchant/refund/Features",
        self::DisableAutoRefund => "example/pg/merchant/refund/Features",
        self::EligibilityEnabled => "rzp/pg/merchant/affordability/EligibilityFeatures",
        self::ShowEmailOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::EmailOptionalOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::UpiNumberDisabled => "rzp/pg/merchant/checkout/Upi",
        self::UpiNumberInUpiSectionDisabled => "rzp/pg/merchant/checkout/Upi",
        self::UpiNumberInPreferredSectionDisabled => "rzp/pg/merchant/checkout/Upi",
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
        self::ManualSettlementForSubmerchants => "rzp/pg/merchant/settlements/PartnershipsFeatures",
        self::ImportSettlement => "rzp/pg/merchant/settlements/OPGSPFeatures",
        self::SavedCardsDisabled => "rzp/pg/merchant/checkout/SavedCards",
        self::CvvLessFlowDisabled => "rzp/pg/merchant/checkout/SavedCards",
        self::PaymentRetryDisabled => "rzp/pg/merchant/checkout/PaymentCustomisation",
        self::InternationalizationDisabled => "rzp/pg/merchant/checkout/Internationalization",
        self::GooglePayEnabled => "rzp/pg/merchant/checkout/Upi",
        self::ConfigEnabled => "rzp/pg/merchant/checkout/CheckoutConfiguration",
        self::RewardsOnMxDashboardEnabled => "rzp/pg/merchant/checkout/Rewards",
        self::OrgLogoEnabled => "rzp/pg/org/checkout/CheckoutTheme",
        self::CollectCustomerAddressEnabled => "rzp/pg/merchant/checkout/AdditionalCustomerProperties",
        self::TruecallerLoginDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnContactScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnHomeScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnMobileWebDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnSdkDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnAddCardScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
        self::TruecallerLoginOnSavedCardsScreenDisabled => "rzp/pg/merchant/checkout/TruecallerCustomization",
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
        self::SubmerchantInstantActivationViaV2ApiEnabled => "rzp/platform/partner/onboarding/Features",
        self::MerchantActivationByPartnerEnabled => "rzp/platform/partner/onboarding/Features",
        self::OverridingSubmerchantConfigEnabled => "rzp/platform/partner/configuration/Features",
        self::AdditionalFieldsHdfcOnboarding => "rzp/pg/org/dashboard/admin/Features",
        self::NoDocOnboardingEnabled => 'rzp/pg/merchant/onboarding/PartnershipFeatures',
        self::OnboardedViaV2ApiEnabled => 'rzp/pg/merchant/onboarding/PartnershipFeatures',
        self::BlockSendingOnboardingSms => 'rzp/pg/merchant/communication/PartnershipFeatures',
        self::AssumeSubAccount => 'rzp/x/merchant/payouts/SubAccountRoles',
        self::AssumeMasterAccount => 'rzp/x/merchant/payouts/SubAccountRoles'
    ];

    /**
     * Stores the mapping of the api feature name to their corresponding dcs feature names
     * This is required for migrating features.
     */
    public static $apiFeatureNameToDCSFeatureName = [
        self::RefundEnabled                                                 => self::RefundEnabled,
        self::DisableAutoRefund                                             => self::DisableAutoRefund,
        self::EligibilityEnabled                                            => self::EligibilityEnabled,
        self::ShowEmailOnCheckout                                           => self::ShowEmailOnCheckout,
        self::EmailOptionalOnCheckout                                       => self::EmailOptionalOnCheckout,
        self::CvvLessFlowDisabled                                           => self::CvvLessFlowDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_CHECKOUT                      => self::UpiNumberDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_ON_L0                         => self::UpiNumberInPreferredSectionDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_ON_L1                         => self::UpiNumberInUpiSectionDisabled,
        APIFeaturesConstants::AUTO_COMM_INV_DISABLED                        => self::AutoCommissionInvoiceDisabled,
        self::AffordabilityWidgetSet                                        => self::AffordabilityWidgetSet,
        APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PL                     => self::EnableMerchantExpiryForPL,
        APIFeaturesConstants::ENABLE_MERCHANT_EXPIRY_PP                     => self::EnableMerchantExpiryForPP,
        APIFeaturesConstants::ENABLE_CREATE_OWN_TEMPLATE                    => self::EnableMerchantCreateOwnTemplate,
        APIFeaturesConstants::ENABLE_CUSTOMER_AMOUNT                        => self::EnableCustomerAmount,
        APIFeaturesConstants::ORDER_RECEIPT_UNIQUE                          => self::ReceiptUniqueEnabled,
        APIFeaturesConstants::CART_API_AMOUNT_CHECK                         => self::CartAmountCheckEnabled,
        APIFeaturesConstants::DISABLE_AMOUNT_CHECK                          => self::AllowPaymentsOnPaidOrder,
        APIFeaturesConstants::EXCESS_ORDER_AMOUNT                           => self::ExcessOrderAmountEnabled,
        APIFeaturesConstants::PAYMENT_MAILS_DISABLED                        => self::DcsPaymentMailsDisabled,
        APIFeaturesConstants::DISABLE_FREE_CREDIT_UNREG                     => self::FreeCreditUnregDisabled,
        APIFeaturesConstants::ASYNC_BALANCE_UPDATE                          => self::AsyncBalanceUpdateEnabled,
        APIFeaturesConstants::ASYNC_TXN_FILL_DETAILS                        => self::AsyncTransactionUpdateEnabled,
        APIFeaturesConstants::DISABLE_AUTO_REFUNDS                          => self::AutoRefundsDisabled,
        APIFeaturesConstants::ROUTE_PARTNERSHIPS                            => self::EnableRoutePartnerships,
        APIFeaturesConstants::SUBM_MANUAL_SETTLEMENT                        => self::ManualSettlementForSubmerchants,
        APIFeaturesConstants::ADDITIONAL_ONBOARDING                         => self::AdditionalFieldsHdfcOnboarding,
        self::ImportSettlement                                              => self::ImportSettlement,
        APIFeaturesConstants::NOFLASHCHECKOUT                               => self::SavedCardsDisabled,
        APIFeaturesConstants::CHECKOUT_DISABLE_RETRY                        => self::PaymentRetryDisabled,
        APIFeaturesConstants::CHECKOUT_DISABLE_I18N                         => self::InternationalizationDisabled,
        APIFeaturesConstants::GOOGLE_PAY                                    => self::GooglePayEnabled,
        APIFeaturesConstants::PAYMENT_CONFIG_ENABLED                        => self::ConfigEnabled,
        APIFeaturesConstants::REWARD_MERCHANT_DASHBOARD                     => self::RewardsOnMxDashboardEnabled,
        APIFeaturesConstants::ORG_CUSTOM_CHECKOUT_LOGO                      => self::OrgLogoEnabled,
        APIFeaturesConstants::CUSTOMER_ADDRESS                              => self::CollectCustomerAddressEnabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN                      => self::TruecallerLoginDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN       => self::TruecallerLoginOnContactScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_HOME_SCREEN          => self::TruecallerLoginOnHomeScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_MWEB                 => self::TruecallerLoginOnMobileWebDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SDK                  => self::TruecallerLoginOnSdkDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN  => self::TruecallerLoginOnAddCardScreenDisabled,
        APIFeaturesConstants::DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN   => self::TruecallerLoginOnSavedCardsScreenDisabled,
        APIFeaturesConstants::SUBM_NO_DOC_ONBOARDING                        => self::SubMerchantOnboardDocUploadingDisabled,
        APIFeaturesConstants::KYC_HANDLED_BY_PARTNER                        => self::KycHandledByPartner,
        APIFeaturesConstants::SKIP_SUBM_ONBOARDING_COMM                     => self::SubMerchantOnboardingCommunicationDisabled,
        APIFeaturesConstants::NO_COMM_WITH_SUBMERCHANTS                     => self::RazorpayCommunicationToSubMerchantDisabled,
        APIFeaturesConstants::AGGREGATOR_OAUTH_CLIENT                       => self::AggregatorOAuthClientDisabled,
        APIFeaturesConstants::SKIP_OAUTH_NOTIFICATION                       => self::OAuthCommunicationDisabled,
        APIFeaturesConstants::SUBM_QR_IMAGE_CONTENT                         => self::SubMerchantQRImageContentEnabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING_V2                     => self::SubMerchantOnBoardingV2Enabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING                        => self::SubMerchantOnBoardingEnabled,
        APIFeaturesConstants::SKIP_WEBSITE_INTERNAT                         => self::WebsiteInternationalDisabled,
        APIFeaturesConstants::ADMIN_LEAD_PARTNER                            => self::AdminLeadPartnerInviteEnabled,
        APIFeaturesConstants::INSTANT_ACTIVATION_V2_API                     => self::SubmerchantInstantActivationViaV2ApiEnabled,
        APIFeaturesConstants::PARTNER_ACTIVATE_MERCHANT                     => self::MerchantActivationByPartnerEnabled,
        APIFeaturesConstants::OVERRIDE_SUB_CONFIG                           => self::OverridingSubmerchantConfigEnabled,
        APIFeaturesConstants::NO_DOC_ONBOARDING                             => self::NoDocOnboardingEnabled,
        APIFeaturesConstants::CREATE_SOURCE_V2                              => self::OnboardedViaV2ApiEnabled,
        APIFeaturesConstants::BLOCK_ONBOARDING_SMS                          => self::BlockSendingOnboardingSms,
        APIFeaturesConstants::ASSUME_SUB_ACCOUNT                            => self::AssumeSubAccount,
        APIFeaturesConstants::ASSUME_MASTER_ACCOUNT                         => self::AssumeMasterAccount,
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
        self::ManualSettlementForSubmerchants => 'direct',
        self::ImportSettlement => 'direct',
        self::AdminLeadPartnerInviteEnabled => 'direct',
        self::CvvLessFlowDisabled => 'direct',
        self::AssumeSubAccount => 'direct',
        self::AssumeMasterAccount => 'direct',
    ];

    /**
     * Stores the mapping of the Prg features to their corresponding handlers
     */
    public static $dcsNewOrgFeatures = [
        self::AdditionalFieldsHdfcOnboarding => 'direct',
    ];

    public static function dcsReadEnabledFeaturesByEntityType(string $entityType = null,
                                                              bool $withDcsNames = false): array
    {

        $adminService = new AdminService;
        $dcsReadEnabledOrg = [];
        $dcsReadEnabledMerchant = [];
        $dcsReadEnabledFeatures = $adminService->getConfigKey(['key' => ConfigKey::DCS_READ_WHITELISTED_FEATURES]);
        if (key_exists(Type::ORG, $dcsReadEnabledFeatures) === true)
        {
            $dcsReadEnabledOrg = $dcsReadEnabledFeatures[Type::ORG];
        }

        if (key_exists(Type::MERCHANT, $dcsReadEnabledFeatures) === true)
        {
            $dcsReadEnabledMerchant = $dcsReadEnabledFeatures[Type::MERCHANT];
        }

        if ($dcsReadEnabledFeatures === null)
        {
            return [];
        }
        if (($entityType === Type::PARTNER) || ($entityType === Type::MERCHANT))
        {
            $featureNames = $dcsReadEnabledMerchant;
        }
        elseif ($entityType === Type::ORG)
        {
            $featureNames = $dcsReadEnabledOrg;
        }
        else
        {
            $featureNames = array_merge($dcsReadEnabledMerchant, $dcsReadEnabledOrg);
        }

        if ($withDcsNames === true)
        {
            return self::getDcsFeatureNamesFromApiNames($featureNames);
        }

        return  $featureNames;
    }

    public static function getAPIFeatureNamesFromDcsNames(array $featureNames): array
    {
        $apiFeatureNames = [];
        foreach ($featureNames as $featureName => $value)
        {
            $dcsFeatureNameToAPIFeatureName = array_flip(self::$apiFeatureNameToDCSFeatureName);
            if (key_exists($featureName, $dcsFeatureNameToAPIFeatureName) === true)
            {
                $apiFeatureNames[$dcsFeatureNameToAPIFeatureName[$featureName]] = $value;
            }
        }
        return $apiFeatureNames;
    }

    public static function getDcsFeatureNamesFromApiNames($featureNames): array
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

    public static function dcsFeatureNameFromAPIName($name): string
    {
        if (key_exists($name, self::$apiFeatureNameToDCSFeatureName) === false) {
            $ex = new Exception\ServerErrorException(
                'Dcs feature name missing in $apiFeatureNameToDCSFeatureName please check',
                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                "missing dcs feature name in the map");

            throw $ex;
        }

        return self::$apiFeatureNameToDCSFeatureName[$name];
    }

    public static function apiFeatureNameFromDcsName($name): string
    {
        $dcsFeatureNameToAPIFeatureName = array_flip(self::$apiFeatureNameToDCSFeatureName);
        if (key_exists($name, $dcsFeatureNameToAPIFeatureName) === false) {
            $ex = new Exception\ServerErrorException(
                'Dcs feature name missing in $dcsFeatureNameToAPIFeatureName please check with dcs team',
                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                "missing dcs feature name in the map");

            throw $ex;
        }

        return $dcsFeatureNameToAPIFeatureName[$name];
    }

    public static function isShadowFeature($variant): bool
    {
        if (($variant === 'on_client_shadow')  || ($variant === 'on_direct_dcs_shadow'))
        {
            return true;
        }
        return false;
    }

    public static function isReverseShadowFeature($variant): bool
    {
        if (($variant === 'on_client_rs') || ($variant === 'on_direct_dcs_rs'))
        {
            return true;
        }
        return false;
    }

    public static function isNewFeature($variant): bool
    {
        if (($variant === 'on_client_new') || ($variant === 'on_direct_dcs_new'))
        {
            return true;
        }
        return false;
    }

    public static function isDcsReadEnabledFeature($featureName, $isDcsName = false): bool
    {
        return (key_exists($featureName, self::dcsReadEnabledFeaturesByEntityType("", $isDcsName)) === true);
    }

}
