<?php

namespace RZP\Services\Dcs\Features;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\ServerErrorException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as APIFeaturesConstants;

class Constants
{
    const TimeoutFeeBreakupPageCheckout = 'timeout_fee_breakup_page_checkout';
    const SilentRefundLateAuthEnabled = 'silent_refund_late_auth_enabled';
    const PostpaidMerchantsDontSettleCustomerFees = 'postpaid_merchants_dont_settle_customer_fees';
    const RefundEnabled = 'refund_enabled';
    const DisableAutoRefund = 'disable_auto_refund';
    const EligibilityEnabled = 'eligibility_enabled';
    const EligibilityCheckDecline = 'eligibility_check_decline';
    const ShowEmailOnCheckout = 'show_email_on_checkout';
    const EmailOptionalOnCheckout = 'email_optional_oncheckout';
    const UpiNumberDisabled = 'upi_number_disabled';
    const UpiNumberInUpiSectionDisabled = 'upi_number_in_upi_section_disabled';
    const UpiNumberInPreferredSectionDisabled = 'upi_number_in_preferred_section_disabled';
    const UpiTurboDisabled = 'upi_turbo_disabled';
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
    const Disabled = 'disabled';
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
    Const AllowS2SApps = 'allow_s2s_apps';
    const OAuthCommunicationDisabled = 'oauth_communication_disabled';
    const SubMerchantQRImageContentEnabled = 'submerchant_qr_image_content_enabled';
    const SubMerchantOnBoardingV2Enabled  = 'submerchant_onboarding_v2_enabled';
    const SubMerchantOnBoardingEnabled = 'submerchant_onboarding_enabled';
    const WebsiteInternationalDisabled = 'website_international_disabled';
    const RetainSubMerchantName   = 'retain_sub_merchant_name';
    const SubMerchantCreate = 'sub_merchant_create';
    const Aggregator  = 'aggregator';
    const AdminLeadPartnerInviteEnabled = 'admin_lead_partner_invite_enabled';
    const SubmerchantInstantActivationViaV2ApiEnabled = 'submerchant_instant_activation_via_v2_api_enabled';
    const MerchantActivationByPartnerEnabled = 'merchant_activation_by_partner_enabled';
    const OverridingSubmerchantConfigEnabled = 'overriding_submerchant_config_enabled';
    const AdditionalFieldsHdfcOnboarding = 'additional_fields_hdfc_onboarding';
    const HideInstrumentRequest = 'hide_instrument_request';
    const CustomReportExtensions = 'custom_report_extensions';
    const QualityCheckIntimationEmail = 'quality_check_intimation_email';
    const PgLedgerReverseShadowEnabled = 'pg_ledger_reverse_shadow_enabled';
    const ShopifyPaymentsReport = 'shopify_payments_report';
    const NoDocOnboardingEnabled = 'no_doc_onboarding_enabled';
    const OnboardedViaV2ApiEnabled = 'onboarded_via_v2_api_enabled';
    const BlockSendingOnboardingSms = 'block_sending_onboarding_sms';
    const AssumeSubAccount = 'assume_sub_account';
    const AssumeMasterAccount = 'assume_master_account';
    const CloseQrOnDemand    = 'close_api_enabled';
    const CorporateCardsIsAllowedToApply = 'corporatecards:is_allowed_to_apply';
    const CashAdvanceIsAllowedToApply = 'cashadvance:is_allowed_to_apply';
    const ShowCustomDccDisclosures = 'show_custom_dcc_disclosures';
    const DynamicCurrencyConversionCybs = 'dynamic_currency_conversion_cybs';
    const OneCCAutomaticAccountCreation = 'one_cc_automatic_account_creation';
    const ValidateVpa = 'validate_vpa';
    const UseSavedVpa = 'use_saved_vpa';
    const GooglePayOmnichannel = 'google_pay_omnichannel';
    const DisableUpiIntent = 'disable_upi_intent';
    const EnableS2S = 'enable_s2s';
    const EnableP2P = 'enable_p2p';
    const EnableOTM = 'enable_otm';
    const AdminPasswordResetEnabled = 'admin_password_reset_enabled';
    const ESScheduledEnabled = 'scheduled_enabled';
    const ESScheduledEnabledWithLimitations = 'scheduled_enabled_with_limitations';
    const ESScheduledThreePmEnabled = 'three_pm_enabled';
    const ESOndemandEnabled = 'ondemand_enabled';
    const ESOndemandEnabledWithLimitations = 'ondemand_enabled_with_limitations';
    const ESOndemandShowOnDemandDeduction = 'show_ondemand_deduction';
    const ESOndemandUseSettlementOndemand = 'use_settlement_ondemand';
    const OneCCMultipleShipping = 'one_cc_multiple_shipping';
    const AcceptOnly3dsPayments = 'accept_only_3ds_payments';
    const EnableInternationalBankTransfer = 'enable_international_bank_transfer';
    const DisableNativeCurrency = 'disable_native_currency';
    const EnableSettlementForB2B = 'enable_settlement_for_b2b';
    const ImportFlowOpexReport = 'import_flow_opex_report';
    const SendDccCompliance = 'send_dcc_compliance';
    const OPGSPImportFlow = 'opgsp_import_flow';
    const AddressNameRequired = 'address_name_required';
    const AddressRequired = 'address_required';
    const MandatoryAvsCheck = 'mandatory_avs_check';
    const EnableB2BExport = 'enable_b2b_export';
    const IrctcReportEnabled = 'irctc_report_enabled';
    const QrImagePartnerName = 'show_partner_name_on_qr_image';
    const QrImageContent = 'get_qr_image_content';
    const QrCodes = 'qr_codes';
    const BharatQrV2 = 'bharat_qr_v2';
    const BharatQr = 'bharat_qr_on_customer_identifier';
    const QrCustomTxnName = 'qr_payment_confirmation_email_with_payer_name';
    const LOCCashOnCardEnabled = 'cash_on_card_enabled';
    const LOCCliOfferEnabled = 'cli_offer_enabled';
    const LOCAccessEnabled = 'cashadvance_access_enabled';
    const LOCLocFirstWithdrawal = 'loc_first_withdrawal';
    const LOCAutomatedLocEligible = 'automated_loc_eligible';
    const LOCEsign = 'loc_esign';
    const MarketplaceWithdrawalEsAmazon = 'withdrawal_es_amazon';
    const MarketplaceEsAmazon = 'es_amazon';
    const AddGatewayProviderToResponse = 'add_gateway_provider_to_response';
    const AddSettledByToResponse = 'add_settled_by_to_response';
    const ShowPaymentsDetailsOnOTPSubmit = 'show_payments_details_on_otp_submit';
    const AddLateAuthToResponse = 'add_late_auth_to_response';
    const EnableTpvForMerchant = 'enable_tpv_for_merchant';
    const MerchantPaymentCallbackUrlValidation = 'merchant_payment_callback_url_validation';
    const AggregatorAccessToSubmerchantReportEnabled = 'aggregator_access_to_submerchant_report_enabled';
    const OptimizerRazorpayVas = 'razorpay_vas';
    const LedgerJournalWrites = 'ledger_journal_writes';
    const LedgerReverseShadow = 'ledger_reverse_shadow';
    const LedgerJournalReads = 'ledger_journal_reads';
    const DALedgerJournalWrites = 'da_ledger_journal_writes';
    const DisplayRefundPendingStatus = 'display_refund_pending_status';
    const DisplayRefundPublicStatus = 'display_refund_public_status';
    const DisableCardRefunds = 'disable_card_refunds';
    const DisableInstantRefunds = 'disable_instant_refunds';
    const DisableRefunds = 'disable_refunds';
    const AllowRefundAgedPayments = 'allow_refund_aged_payments';
    const AllowVoidRefunds = 'allow_void_refunds';
    const AllowBankTransferRefundNonTpv = 'allow_bank_transfer_refund_non_tpv';
    const RefundArnWebhookVisibility = 'refund_arn_webhook_visibility';
    const RefundAttributesLateAuth = 'refund_attributes_late_auth';
    const CrossOrgLogin = 'cross_org_login';
    const OtpAutoReadAndSubmitDisabled = 'otp_auto_read_submit_disabled';
    const WalletPaytmAutoDebit = 'auto_debit';
    const EnableApprovalViaOAuth = 'enable_approval_via_oauth';
    const SkipKycVerification = 'skip_kyc_verification';

    // settlements service features
    const SettlementsServiceOnboarding = 'settlement_service_onboarded';
    const SettlementsServiceStopSMS = 'stop_settlement_sms';

    public static $validDcsKeys = [];
    /**
     * Stores the mapping of the features to their corresponding dcs keys
     */
    public static $featureToDCSKeyMapping = [
        self::RefundEnabled => "example/pg/merchant/refund/Features",
        self::DisableAutoRefund => "example/pg/merchant/refund/Features",
        self::EligibilityEnabled => "rzp/pg/merchant/affordability/EligibilityFeatures",
        self::EligibilityCheckDecline => "rzp/pg/merchant/affordability/EligibilityFeatures",
        self::ShowEmailOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::EmailOptionalOnCheckout => "rzp/pg/merchant/checkout/EmailFieldCustomizationFeatures",
        self::UpiNumberDisabled => "rzp/pg/merchant/checkout/Upi",
        self::UpiNumberInUpiSectionDisabled => "rzp/pg/merchant/checkout/Upi",
        self::UpiNumberInPreferredSectionDisabled => "rzp/pg/merchant/checkout/Upi",
        self::UpiTurboDisabled => "rzp/pg/merchant/checkout/Upi",
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
        self::DcsPaymentMailsDisabled => "rzp/pg/merchant/communication/PaymentFeatures",
        self::FreeCreditUnregDisabled => "rzp/pg/org/credits/Features",
        self::AsyncBalanceUpdateEnabled => "rzp/pg/merchant/ledger/Features",
        self::AsyncTransactionUpdateEnabled => "rzp/pg/merchant/ledger/Features",
        self::PgLedgerReverseShadowEnabled => "rzp/pg/merchant/ledger/Features",
        self::AutoRefundsDisabled => "rzp/pg/merchant/refunds/Features",
        self::Disabled => "rzp/pg/merchant/dashboard/TestMode",
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
        self::AllowS2SApps =>"rzp/platform/partner/auth/Features",
        self::OAuthCommunicationDisabled => "rzp/platform/partner/communication/Features",
        self::SubMerchantQRImageContentEnabled => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantOnBoardingV2Enabled  => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantOnBoardingEnabled => "rzp/platform/partner/onboarding/Features",
        self::WebsiteInternationalDisabled => "rzp/platform/partner/onboarding/Features",
        self::RetainSubMerchantName => "rzp/platform/partner/onboarding/Features",
        self::SubMerchantCreate     => "rzp/platform/partner/onboarding/Features",
        self::Aggregator     => "rzp/platform/partner/onboarding/Features",
        self::AdminLeadPartnerInviteEnabled => "rzp/platform/partner/onboarding/Features",
        self::SubmerchantInstantActivationViaV2ApiEnabled => "rzp/platform/partner/onboarding/Features",
        self::MerchantActivationByPartnerEnabled => "rzp/platform/partner/onboarding/Features",
        self::OverridingSubmerchantConfigEnabled => "rzp/platform/partner/configuration/Features",
        self::AdditionalFieldsHdfcOnboarding => "rzp/pg/org/dashboard/admin/Features",
        self::HideInstrumentRequest => "rzp/pg/org/dashboard/banking_program/UIControls",
        self::CustomReportExtensions => "rzp/pg/org/dashboard/banking_program/Reporting",
        self::QualityCheckIntimationEmail => "rzp/pg/org/communication/banking_program/MerchantCommunication",
        self::ShopifyPaymentsReport => "rzp/pg/merchant/report/Features",
        self::NoDocOnboardingEnabled => 'rzp/pg/merchant/onboarding/PartnershipFeatures',
        self::OnboardedViaV2ApiEnabled => 'rzp/pg/merchant/onboarding/PartnershipFeatures',
        self::BlockSendingOnboardingSms => 'rzp/pg/merchant/communication/PartnershipFeatures',
        self::AssumeSubAccount => 'rzp/x/merchant/payouts/SubAccountRoles',
        self::AssumeMasterAccount => 'rzp/x/merchant/payouts/SubAccountRoles',
        self::CloseQrOnDemand => 'rzp/pg/merchant/upi/qr/QrCode',
        self::CorporateCardsIsAllowedToApply => 'rzp/capital/merchant/onboarding/corporatecards/EligibilityFeatures',
        self::CashAdvanceIsAllowedToApply => 'rzp/capital/merchant/onboarding/cashadvance/EligibilityFeatures',
        self::OneCCAutomaticAccountCreation => 'rzp/pg/merchant/checkout/magic/Configuration',
        self::ValidateVpa => 'rzp/pg/merchant/upi/collect/Vpa',
        self::UseSavedVpa => 'rzp/pg/merchant/upi/collect/Vpa',
        self::GooglePayOmnichannel => 'rzp/pg/merchant/upi/collect/Omnichannel',
        self::DisableUpiIntent => 'rzp/pg/merchant/upi/intent/Intent',
        self::EnableS2S =>  'rzp/pg/merchant/upi/ServerToServer',
        self::EnableP2P => 'rzp/pg/merchant/upi/PeerToPeer',
        self::EnableOTM => 'rzp/pg/merchant/upi/Otm',
        self::PostpaidMerchantsDontSettleCustomerFees => 'rzp/pg/merchant/settlements/CustomerFeeFeatures',
        self::ShowCustomDccDisclosures => 'rzp/pg/org/checkout/banking_program/UiControls',
        self::DynamicCurrencyConversionCybs => 'rzp/pg/merchant/payments/banking_program/Cards',
        self::AdminPasswordResetEnabled => 'rzp/pg/org/banking/admin/Features',
        self::ESScheduledEnabled => 'rzp/pg/merchant/settlements/EarlySettlementScheduled',
        self::ESScheduledEnabledWithLimitations => 'rzp/pg/merchant/settlements/EarlySettlementScheduled',
        self::ESScheduledThreePmEnabled => 'rzp/pg/merchant/settlements/EarlySettlementScheduled',
        self::ESOndemandEnabled => 'rzp/pg/merchant/settlements/EarlySettlementOndemand',
        self::ESOndemandEnabledWithLimitations => 'rzp/pg/merchant/settlements/EarlySettlementOndemand',
        self::ESOndemandShowOnDemandDeduction => 'rzp/pg/merchant/settlements/EarlySettlementOndemand',
        self::ESOndemandUseSettlementOndemand => 'rzp/pg/merchant/settlements/EarlySettlementOndemand',
        self::OneCCMultipleShipping => 'rzp/pg/merchant/checkout/magic/Configuration',
        self::AcceptOnly3dsPayments => 'rzp/pg/merchant/cards/in_international/Features',
        self::EnableInternationalBankTransfer => 'rzp/pg/merchant/payments/in_international/BankToBankFeatures',
        self::DisableNativeCurrency => 'rzp/pg/merchant/payments/in_international/DCCFeatures',
        self::EnableSettlementForB2B => 'rzp/pg/merchant/settlements/BankToBankFeatures',
        self::ImportFlowOpexReport => 'rzp/pg/merchant/settlements/NiumFeatures',
        self::SendDccCompliance => 'rzp/pg/merchant/payments/in_international/DCCFeatures',
        self::OPGSPImportFlow => 'rzp/pg/merchant/settlements/OPGSPFeatures',
        self::AddressNameRequired => 'rzp/pg/merchant/payments/apm/CheckoutExtraFields',
        self::AddressRequired => 'rzp/pg/merchant/cards/in_international/Features',
        self::MandatoryAvsCheck => 'rzp/pg/merchant/cards/in_international/Features',
        self::EnableB2BExport => 'rzp/pg/merchant/payments/in_international/BankToBankFeatures',
        self::IrctcReportEnabled => "rzp/pg/merchant/report/Irctc",
        self::QrImagePartnerName => 'rzp/pg/merchant/upi/qr/QrCode',
        self::QrImageContent => 'rzp/pg/merchant/upi/qr/QrCode',
        self::QrCodes => 'rzp/pg/merchant/upi/qr/QrCode',
        self::BharatQrV2 => 'rzp/pg/merchant/upi/qr/QrCode',
        self::BharatQr => 'rzp/pg/merchant/upi/qr/QrCode',
        self::QrCustomTxnName => 'rzp/pg/merchant/upi/qr/QrCode',
        self::LOCCashOnCardEnabled => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::LOCCliOfferEnabled => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::LOCAccessEnabled => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::LOCLocFirstWithdrawal => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::LOCAutomatedLocEligible => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::LOCEsign => 'rzp/capital/merchant/cashadvance/LineOfCredit',
        self::MarketplaceWithdrawalEsAmazon => 'rzp/capital/merchant/cashadvance/Marketplace',
        self::MarketplaceEsAmazon => 'rzp/capital/merchant/cashadvance/Marketplace',
        self::AddGatewayProviderToResponse => 'rzp/pg/merchant/api/payments/ResponseConf',
        self::AddSettledByToResponse => 'rzp/pg/merchant/api/payments/ResponseConf',
        self::ShowPaymentsDetailsOnOTPSubmit => 'rzp/pg/merchant/api/payments/ResponseConf',
        self::AddLateAuthToResponse => 'rzp/pg/merchant/api/payments/ResponseConf',
        self::EnableTpvForMerchant => 'rzp/pg/merchant/order/Features',
        self::MerchantPaymentCallbackUrlValidation => 'rzp/pg/merchant/security/Features',
        self::AggregatorAccessToSubmerchantReportEnabled => 'rzp/platform/partner/reporting/Features',
        self::OptimizerRazorpayVas => 'rzp/pg/merchant/optimizer/OnboardingFeatures',
        self::LedgerJournalWrites => 'rzp/platform/merchant/ledger/LedgerRXIntegrationFeatures',
        self::LedgerReverseShadow => 'rzp/platform/merchant/ledger/LedgerRXIntegrationFeatures',
        self::LedgerJournalReads => 'rzp/platform/merchant/ledger/LedgerRXIntegrationFeatures',
        self::DALedgerJournalWrites => 'rzp/platform/merchant/ledger/LedgerRXIntegrationFeatures',
        self::DisplayRefundPendingStatus => 'rzp/pg/merchant/refunds/Display',
        self::DisplayRefundPublicStatus => 'rzp/pg/merchant/refunds/Display',
        self::DisableCardRefunds => 'rzp/pg/merchant/refunds/RefundCreation',
        self::DisableInstantRefunds => 'rzp/pg/merchant/refunds/RefundCreation',
        self::DisableRefunds => 'rzp/pg/merchant/refunds/RefundCreation',
        self::AllowRefundAgedPayments => 'rzp/pg/merchant/refunds/RefundCreation',
        self::AllowVoidRefunds => 'rzp/pg/merchant/refunds/RefundCreation',
        self::AllowBankTransferRefundNonTpv => 'rzp/pg/merchant/refunds/RefundCreation',
        self::RefundArnWebhookVisibility => 'rzp/pg/merchant/refunds/Webhook',
        self::RefundAttributesLateAuth => 'rzp/pg/org/refunds/Display',
        self::CrossOrgLogin => 'rzp/platform/merchant/login/CrossLoginFeatures',
        self::TimeoutFeeBreakupPageCheckout => 'rzp/pg/merchant/checkout/custom/Timeout',
        self::SilentRefundLateAuthEnabled => 'rzp/pg/merchant/payment_lifecycle/LateAuth',
        self::OtpAutoReadAndSubmitDisabled => 'rzp/pg/merchant/checkout/Otp',
        self::OptimizerRazorpayVas => 'rzp/pg/merchant/optimizer/OnboardingFeatures',
        self::WalletPaytmAutoDebit => 'rzp/pg/merchant/wallet/paytm/AutoDebit',
        self::EnableApprovalViaOAuth => 'rzp/x/merchant/payouts/Workflows',
        self::SkipKycVerification => 'rzp/pg/org/onboarding/banking_program/Config',
        self::SettlementsServiceOnboarding => 'rzp/pg/merchant/settlements/Onboarding',
        self::SettlementsServiceStopSMS => 'rzp/pg/merchant/settlements/Communication',
    ];

    public static function isValidDcsKeyAndName(string $key, string $name): bool
    {
        foreach (self::$featureToDCSKeyMapping as $featureName => $dcsKey) {
            if (($key === $dcsKey) && ($featureName == $name)) {
                return true;
            }

        }
        return false;
    }

    /**
     * Stores the mapping of the api feature name to their corresponding dcs feature names
     * This is required for migrating features.
     */
    public static array $apiFeatureNameToDCSFeatureName = [
        self::RefundEnabled                                                 => self::RefundEnabled,
        self::DisableAutoRefund                                             => self::DisableAutoRefund,
        self::EligibilityEnabled                                            => self::EligibilityEnabled,
        self::EligibilityCheckDecline                                       => self::EligibilityCheckDecline,
        self::ShowEmailOnCheckout                                           => self::ShowEmailOnCheckout,
        self::EmailOptionalOnCheckout                                       => self::EmailOptionalOnCheckout,
        self::CvvLessFlowDisabled                                           => self::CvvLessFlowDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_CHECKOUT                      => self::UpiNumberDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_ON_L0                         => self::UpiNumberInPreferredSectionDisabled,
        APIFeaturesConstants::DISABLE_UPI_NUM_ON_L1                         => self::UpiNumberInUpiSectionDisabled,
        self::UpiTurboDisabled                                              => self::UpiTurboDisabled,
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
        APIFeaturesConstants::PG_LEDGER_REVERSE_SHADOW                      => self::PgLedgerReverseShadowEnabled,
        APIFeaturesConstants::ASYNC_TXN_FILL_DETAILS                        => self::AsyncTransactionUpdateEnabled,
        APIFeaturesConstants::DISABLE_AUTO_REFUNDS                          => self::AutoRefundsDisabled,
        APIFeaturesConstants::PREVENT_TEST_MODE                             => self::Disabled,
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
        APIFeaturesConstants::ALLOW_S2S_APPS                                => self::AllowS2SApps,
        APIFeaturesConstants::SKIP_OAUTH_NOTIFICATION                       => self::OAuthCommunicationDisabled,
        APIFeaturesConstants::SUBM_QR_IMAGE_CONTENT                         => self::SubMerchantQRImageContentEnabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING_V2                     => self::SubMerchantOnBoardingV2Enabled,
        APIFeaturesConstants::SUBMERCHANT_ONBOARDING                        => self::SubMerchantOnBoardingEnabled,
        APIFeaturesConstants::SKIP_WEBSITE_INTERNAT                         => self::WebsiteInternationalDisabled,
        APIFeaturesConstants::RETAIN_SUB_MERCHANT_NAME                      => self::RetainSubMerchantName,
        APIFeaturesConstants::ORG_SUB_MERCHANT_CREATE                       => self::SubMerchantCreate,
        APIFeaturesConstants::AGGREGATOR                                    => self::Aggregator,
        APIFeaturesConstants::ADMIN_LEAD_PARTNER                            => self::AdminLeadPartnerInviteEnabled,
        APIFeaturesConstants::INSTANT_ACTIVATION_V2_API                     => self::SubmerchantInstantActivationViaV2ApiEnabled,
        APIFeaturesConstants::PARTNER_ACTIVATE_MERCHANT                     => self::MerchantActivationByPartnerEnabled,
        APIFeaturesConstants::OVERRIDE_SUB_CONFIG                           => self::OverridingSubmerchantConfigEnabled,
        APIFeaturesConstants::RAZORPAY_SECURE_MERCHANT                      => self::ShopifyPaymentsReport,
        APIFeaturesConstants::NO_DOC_ONBOARDING                             => self::NoDocOnboardingEnabled,
        APIFeaturesConstants::CREATE_SOURCE_V2                              => self::OnboardedViaV2ApiEnabled,
        APIFeaturesConstants::BLOCK_ONBOARDING_SMS                          => self::BlockSendingOnboardingSms,
        APIFeaturesConstants::ASSUME_SUB_ACCOUNT                            => self::AssumeSubAccount,
        APIFeaturesConstants::ASSUME_MASTER_ACCOUNT                         => self::AssumeMasterAccount,
        APIFeaturesConstants::CLOSE_QR_ON_DEMAND                            => self::CloseQrOnDemand,
        APIFeaturesConstants::CAPITAL_CARDS_ELIGIBLE                        => self::CorporateCardsIsAllowedToApply,
        APIFeaturesConstants::LOC                                           => self::CashAdvanceIsAllowedToApply,
        APIFeaturesConstants::SHOW_CUSTOM_DCC_DISCLOSURES                   => self::ShowCustomDccDisclosures,
        APIFeaturesConstants::DYNAMIC_CURRENCY_CONVERSION_CYBS              => self::DynamicCurrencyConversionCybs,
        APIFeaturesConstants::ONE_CC_SHOPIFY_ACC_CREATE                     => self::OneCCAutomaticAccountCreation,
        APIFeaturesConstants::HIDE_INSTRUMENT_REQUEST                       => self::HideInstrumentRequest,
        APIFeaturesConstants::CUSTOM_REPORT_EXTENSIONS                      => self::CustomReportExtensions,
        APIFeaturesConstants::QC_INTIMATION_EMAIL                           => self::QualityCheckIntimationEmail,
        APIFeaturesConstants::ENABLE_VPA_VALIDATE                           => self::ValidateVpa,
        APIFeaturesConstants::SAVE_VPA                                      => self::UseSavedVpa,
        APIFeaturesConstants::GOOGLE_PAY_OMNICHANNEL                        => self::GooglePayOmnichannel,
        APIFeaturesConstants::DISABLE_UPI_INTENT                            => self::DisableUpiIntent,
        APIFeaturesConstants::S2SUPI                                        => self::EnableS2S,
        APIFeaturesConstants::P2P_UPI                                       => self::EnableP2P,
        APIFeaturesConstants::UPI_OTM                                       => self::EnableOTM,
        APIFeaturesConstants::CUSTOMER_FEE_DONT_SETTLE                      => self::PostpaidMerchantsDontSettleCustomerFees,
        APIFeaturesConstants::ORG_ADMIN_PASSWORD_RESET                      => self::AdminPasswordResetEnabled,
        APIFeaturesConstants::ES_AUTOMATIC                                  => self::ESScheduledEnabled,
        APIFeaturesConstants::ES_AUTOMATIC_RESTRICTED                       => self::ESScheduledEnabledWithLimitations,
        APIFeaturesConstants::ES_AUTOMATIC_THREE_PM                         => self::ESScheduledThreePmEnabled,
        APIFeaturesConstants::ES_ON_DEMAND                                  => self::ESOndemandEnabled,
        APIFeaturesConstants::ES_ON_DEMAND_RESTRICTED                       => self::ESOndemandEnabledWithLimitations,
        APIFeaturesConstants::SHOW_ON_DEMAND_DEDUCTION                      => self::ESOndemandShowOnDemandDeduction,
        APIFeaturesConstants::USE_SETTLEMENT_ONDEMAND                       => self::ESOndemandUseSettlementOndemand,
        APIFeaturesConstants::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING              => self::OneCCMultipleShipping,
        APIFeaturesConstants::ACCEPT_ONLY_3DS_PAYMENTS                      => self::AcceptOnly3dsPayments,
        APIFeaturesConstants::ENABLE_INTL_BANK_TRANSFER                     => self::EnableInternationalBankTransfer,
        APIFeaturesConstants::DISABLE_NATIVE_CURRENCY                       => self::DisableNativeCurrency,
        APIFeaturesConstants::ENABLE_SETTLEMENT_FOR_B2B                     => self::EnableSettlementForB2B,
        APIFeaturesConstants::IMPORT_FLOW_OPEX_REPORT                       => self::ImportFlowOpexReport,
        APIFeaturesConstants::SEND_DCC_COMPLIANCE                           => self::SendDccCompliance,
        APIFeaturesConstants::OPGSP_IMPORT_FLOW                             => self::OPGSPImportFlow,
        APIFeaturesConstants::ADDRESS_NAME_REQUIRED                         => self::AddressNameRequired,
        APIFeaturesConstants::ADDRESS_REQUIRED                              => self::AddressRequired,
        APIFeaturesConstants::MANDATORY_AVS_CHECK                           => self::MandatoryAvsCheck,
        APIFeaturesConstants::ENABLE_B2B_EXPORT                             => self::EnableB2BExport,
        APIFeaturesConstants::IRCTC_REPORT                                  => self::IrctcReportEnabled,
        APIFeaturesConstants::QR_IMAGE_PARTNER_NAME                         => self::QrImagePartnerName,
        APIFeaturesConstants::QR_IMAGE_CONTENT                              => self::QrImageContent,
        APIFeaturesConstants::QR_CODES                                      => self::QrCodes,
        APIFeaturesConstants::BHARAT_QR_V2                                  => self::BharatQrV2,
        APIFeaturesConstants::BHARAT_QR                                     => self::BharatQr,
        APIFeaturesConstants::QR_CUSTOM_TXN_NAME                            => self::QrCustomTxnName,
        APIFeaturesConstants::CASH_ON_CARD                                  => self::LOCCashOnCardEnabled,
        APIFeaturesConstants::LOC_CLI_OFFER                                 => self::LOCCliOfferEnabled,
        APIFeaturesConstants::WITHDRAW_LOC                                  => self::LOCAccessEnabled,
        APIFeaturesConstants::LOC_FIRST_WITHDRAWAL                          => self::LOCLocFirstWithdrawal,
        APIFeaturesConstants::AUTOMATED_LOC_ELIGIBLE                        => self::LOCAutomatedLocEligible,
        APIFeaturesConstants::LOC_ESIGN                                     => self::LOCEsign,
        APIFeaturesConstants::WITHDRAWAL_ES_AMAZON                          => self::MarketplaceWithdrawalEsAmazon,
        APIFeaturesConstants::ALLOW_ES_AMAZON                               => self::MarketplaceEsAmazon,
        APIFeaturesConstants::EXPOSE_GATEWAY_PROVIDER                       => self::AddGatewayProviderToResponse,
        APIFeaturesConstants::EXPOSE_SETTLED_BY                             => self::AddSettledByToResponse,
        APIFeaturesConstants::OTP_SUBMIT_RESPONSE                           => self::ShowPaymentsDetailsOnOTPSubmit,
        APIFeaturesConstants::SEND_PAYMENT_LATE_AUTH                        => self::AddLateAuthToResponse,
        APIFeaturesConstants::TPV                                           => self::EnableTpvForMerchant,
        APIFeaturesConstants::CALLBACK_URL_VALIDATION                       => self::MerchantPaymentCallbackUrlValidation,
        APIFeaturesConstants::AGGREGATOR_REPORT                             => self::AggregatorAccessToSubmerchantReportEnabled,
        APIFeaturesConstants::OPTIMIZER_RAZORPAY_VAS                        => self::OptimizerRazorpayVas,
        APIFeaturesConstants::LEDGER_JOURNAL_WRITES                         => self::LedgerJournalWrites,
        APIFeaturesConstants::LEDGER_REVERSE_SHADOW                         => self::LedgerReverseShadow,
        APIFeaturesConstants::LEDGER_JOURNAL_READS                          => self::LedgerJournalReads,
        APIFeaturesConstants::DA_LEDGER_JOURNAL_WRITES                      => self::DALedgerJournalWrites,
        APIFeaturesConstants::REFUND_PENDING_STATUS                         => self::DisplayRefundPendingStatus,
        APIFeaturesConstants::SHOW_REFUND_PUBLIC_STATUS                     => self::DisplayRefundPublicStatus,
        APIFeaturesConstants::DISABLE_CARD_REFUNDS                          => self::DisableCardRefunds,
        APIFeaturesConstants::DISABLE_INSTANT_REFUNDS                       => self::DisableInstantRefunds,
        APIFeaturesConstants::DISABLE_REFUNDS                               => self::DisableRefunds,
        APIFeaturesConstants::REFUND_AGED_PAYMENTS                          => self::AllowRefundAgedPayments,
        APIFeaturesConstants::VOID_REFUNDS                                  => self::AllowVoidRefunds,
        APIFeaturesConstants::NON_TPV_BT_REFUND                             => self::AllowBankTransferRefundNonTpv,
        APIFeaturesConstants::REFUND_ARN_WEBHOOK                            => self::RefundArnWebhookVisibility,
        APIFeaturesConstants::SHOW_REFND_LATEAUTH_PARAM                     => self::RefundAttributesLateAuth,
        self::CrossOrgLogin                                                 => self::CrossOrgLogin,
        APIFeaturesConstants::FEE_PAGE_TIMEOUT_CUSTOM                       => self::TimeoutFeeBreakupPageCheckout,
        APIFeaturesConstants::SILENT_REFUND_LATE_AUTH                       => self::SilentRefundLateAuthEnabled,
        APIFeaturesConstants::DISABLE_OTP_AUTO_READ_AND_SUBMIT              => self::OtpAutoReadAndSubmitDisabled,
        APIFeaturesConstants::WALLET_PAYTM_AUTO_DEBIT                       => self::WalletPaytmAutoDebit,
        APIFeaturesConstants::ENABLE_APPROVAL_VIA_OAUTH                     => self::EnableApprovalViaOAuth,
        APIFeaturesConstants::SKIP_KYC_VERIFICATION                         => self::SkipKycVerification,
        APIFeaturesConstants::NEW_SETTLEMENT_SERVICE                        => self::SettlementsServiceOnboarding,
        APIFeaturesConstants::SETTLEMENTS_SMS_STOP                          => self::SettlementsServiceStopSMS,
    ];

    /**
     * Stores the mapping of the Merchant features to their corresponding handlers
     */
    public static array $dcsNewMerchantFeatures = [
        self::RefundEnabled => 'direct',
        self::DisableAutoRefund => 'direct',
        self::EligibilityEnabled => 'client',
        self::EligibilityCheckDecline => 'client',
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
        self::PgLedgerReverseShadowEnabled => 'direct',
        self::CvvLessFlowDisabled => 'direct',
        self::AssumeSubAccount => 'direct',
        self::AssumeMasterAccount => 'direct',
        self::PostpaidMerchantsDontSettleCustomerFees => 'direct',
        self::CloseQrOnDemand => 'direct',
        self::DynamicCurrencyConversionCybs => "direct",
        self::UpiTurboDisabled => 'direct',
        self::TimeoutFeeBreakupPageCheckout => 'direct',
        self::SilentRefundLateAuthEnabled => 'direct',
        self::OtpAutoReadAndSubmitDisabled => 'direct',
        self::EnableApprovalViaOAuth => 'direct',
    ];

    /**
     * Stores the mapping of the Prg features to their corresponding handlers
     */
    public static array $dcsNewOrgFeatures = [
        self::AdditionalFieldsHdfcOnboarding => 'direct',
        self::HideInstrumentRequest         => 'direct',
        self::QualityCheckIntimationEmail   => 'direct',
        self::ShowCustomDccDisclosures       => 'direct',
        self::AdminPasswordResetEnabled => 'direct',
        self::SkipKycVerification => 'direct',
    ];

    public static array $loadedReadEnabledFeatures = [];
    public static array $dcsReadEnabledFeatures = [
        "merchant" => [
            "eligibility_enabled"=> "client",
            "eligibility_check_decline" => "client",
            "auto_comm_inv_disabled"=> "direct",
            "affordability_widget_set"=> "client",
            "enable_customer_amount"=> "direct",
            "enbl_create_own_tmpl"=> "direct",
            "enable_merchant_expiry_pp"=> "direct",
            "enable_merchant_expiry_pl"=> "direct",
            "route_partnerships"=> "direct",
            "import_settlement"=> "direct",
            "disable_auto_refunds"=> "client",
            "async_txn_fill_details"=> "client",
            "async_balance_update"=> "client",
            "payment_mails_disabled"=> "client",
            "excess_order_amount"=> "client",
            "disable_amount_check"=> "client",
            "cart_api_amount_check"=> "client",
            "order_receipt_unique"=> "client",
            "postpaid_merchants_dont_settle_customer_fees"=> "direct",
            "dynamic_currency_conversion_cybs" => "direct",
            "irctc_report" => "direct",
            "cross_org_login" => "direct",
            "timeout_fee_breakup_page_checkout" => "direct",
            "silent_refund_late_auth_enabled" => "direct",
            "stop_settlement_sms" => "direct",
            "settlement_service_onboarded" => "direct",
        ],
        "org" => [
            "disable_free_credit_unreg"=> "client",
            "show_custom_dcc_disclosures" => "direct",
        ]
    ];

    public static function dcsReadEnabledFeaturesByEntityType(string $entityType = null,
                                                              bool $withDcsNames = false, bool $isTestCases = false, bool $isProduction = false): array
    {
        if (empty(self::$loadedReadEnabledFeatures) === true)
        {
            $adminService = new AdminService;
            $key = Utility::getRandomPrefix() . '_' . ConfigKey::DCS_READ_WHITELISTED_FEATURES;
            $dcsReadEnabledFeatures = $adminService->getConfigKey(
                ['key' => $key]);
            self::$loadedReadEnabledFeatures =  $dcsReadEnabledFeatures;
        }
        else
        {

            $dcsReadEnabledFeatures = self::$loadedReadEnabledFeatures;
        }

        $dcsReadEnabledMerchant = [];
        $dcsReadEnabledOrg = [];

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
            else
            {
                $dcsFeatureName = Utility::searchAndReturnDcsNameWithCorrespondingColonSeparator($dcsFeatureNameToAPIFeatureName, $featureName);

                $apiFeatureNames[$dcsFeatureNameToAPIFeatureName[$dcsFeatureName]] = $value;
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

    /**
     * @throws ServerErrorException
     */
    public static function apiFeatureNameFromDcsName($name, $dcsKey = ""): string
    {
        $dcsFeatureNameToAPIFeatureName = array_flip(self::$apiFeatureNameToDCSFeatureName);
        if (key_exists($name, $dcsFeatureNameToAPIFeatureName) === false)
        {
            $dcsName = Utility::searchAndReturnDcsNameWithCorrespondingColonSeparator($dcsFeatureNameToAPIFeatureName,$name,$dcsKey);

            if ((empty($dcsName) === false) and
                (key_exists($dcsName, $dcsFeatureNameToAPIFeatureName) === true))
            {
                return $dcsFeatureNameToAPIFeatureName[$dcsName];
            }
            throw new Exception\ServerErrorException(
                'Dcs feature name missing in $dcsFeatureNameToAPIFeatureName please check with dcs team',

                ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                "missing dcs feature name in the map");
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

    public static function isDcsReadEnabledFeature($featureName, $isDcsName = false, $dcsKey = "", $isProduction = false): bool
    {
        $dcsEnabledFeatures = self::dcsReadEnabledFeaturesByEntityType("", $isDcsName, false, $isProduction);
        return (key_exists($featureName, $dcsEnabledFeatures) === true) or
                (($isDcsName === true) && (empty(Utility::searchAndReturnDcsNameWithCorrespondingColonSeparator($dcsEnabledFeatures, $featureName, $dcsKey)) === false));
    }

}
