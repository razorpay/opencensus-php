<?php

namespace RZP\Models\Feature;

use RZP\Models\Merchant\Detail as MerchantDetail;

class Constants
{
    const ENTITY_IDS                      = 'entity_ids';
    const NAMES                           = 'names';
    const DUMMY                           = 'dummy';
    const WEBHOOKS                        = 'webhooks';
    const AGGREGATOR                      = 'aggregator';
    const TERMINAL_ONBOARDING             = 'terminal_onboarding';
    const TOKENS                          = 'tokens';
    const S2SWALLET                       = 's2swallet';
    const S2SUPI                          = 's2supi';
    const S2SAEPS                         = 's2saeps';
    const SETL_REPORT                     = 'setl_report';
    const NOFLASHCHECKOUT                 = 'noflashcheckout';
    const RECURRING                       = 'recurring';
    const S2S                             = 's2s';
    const S2S_JSON                        = 's2s_json';
    const INVOICE                         = 'invoice';
    const NOZEROPRICING                   = 'nozeropricing';
    const REVERSE                         = 'reverse';
    const BROKING_REPORT                  = 'broking_report';
    const DSP_REPORT                      = 'dsp_report';
    const RPP_REPORT                      = 'rpp_report';
    const AGGREGATOR_REPORT               = 'aggregator_report';
    const PAYMENT_EMAIL_FETCH             = 'payment_email_fetch';
    const CREATED_FLOW                    = 'created_flow';
    const PAYOUT                          = 'payout';
    const OPENWALLET                      = 'openwallet';
    const MARKETPLACE                     = 'marketplace';
    const EMAIL_OPTIONAL                  = 'email_optional';
    const CONTACT_OPTIONAL                = 'contact_optional';
    const SUBSCRIPTIONS                   = 'subscriptions';
    const ZOHO                            = 'zoho';
    const EXPOSE_DOWNTIMES                = 'expose_downtimes';
    const PAYMENT_FAILURE_EMAIL           = 'payment_failure_email';
    const VIRTUAL_ACCOUNTS                = 'virtual_accounts';
    const BANK_TRANSFER_ON_CHECKOUT       = 'bank_transfer_on_checkout';
    const FUND_ACCOUNT_VALIDATIONS        = 'fund_account_validations';
    const INVOICE_PARTIAL_PAYMENTS        = 'invoice_partial_payments';
    const HIDE_DOWNTIMES                  = 'hide_downtimes';
    const OLD_CREDITS_FLOW                = 'old_credits_flow';
    const CHARGE_AT_WILL                  = 'charge_at_will';
    const SETTLEMENT_24X7                 = 'settlement_24x7';
    const EMI_MERCHANT_SUBVENTION         = 'emi_merchant_subvention';
    const FSS_RISK_UDF                    = 'fss_risk_udf';
    const RULE_FILTER                     = 'rule_filter';
    const TPV                             = 'tpv';
    const IRCTC_REPORT                    = 'irctc_report';
    const DISABLE_MAESTRO                 = 'disable_maestro';
    const DISABLE_RUPAY                   = 'disable_rupay';
    const BLOCK_INTERNATIONAL_RECURRING   = 'block_intl_recurring';
    const BHARAT_QR                       = 'bharat_qr';
    const MOBIKWIK_OFFERS                 = 'mobikwik_offers';
    const ALLOW_DC_RECURRING              = 'allow_dc_recurring';
    const ALLOW_ALL_DC_RECURRING          = 'allow_all_dc_recurring';
    const SKIP_HOLD_FUNDS_ON_PAYOUT       = 'skip_hold_funds_on_payout';
    const REPORT_V2                       = 'report_v2';
    const CORPORATE_BANKS                 = 'corporate_banks';
    const MAGIC                           = 'magic';
    const NEW_ANALYTICS                   = 'new_analytics';
    const DAILY_SETTLEMENT                = 'daily_settlement';
    const DISABLE_UPI_INTENT              = 'disable_upi_intent';
    const DIRECT_DEBIT                    = 'direct_debit';
    const ALLOW_S2S_APPS                  = 'allow_s2s_apps';
    const UPI_PLUS                        = 'upi_plus';
    const FSS_IPAY                        = 'fss_ipay';
    const EXPOSE_CARD_EXPIRY              = 'expose_card_expiry';
    const EXPOSE_CARD_IIN                 = 'expose_card_iin';
    const S2S_OPTIONAL_DATA               = 's2s_optional_data';
    const VOID_REFUNDS                    = 'void_refunds';
    const PARTNER                         = 'partner';
    const OTPELF                          = 'otpelf';
    const PAYMENT_NOBRANDING              = 'payment_nobranding';
    const ENABLE_VPA_VALIDATE             = 'enable_vpa_validate';
    const ALLOW_SUBMERCHANT_WITHOUT_EMAIL = 'allow_sub_without_email';
    const HDFC_DEBIT_SI                   = 'hdfc_debit_si';
    const AXIS_EXPRESS_PAY                = 'axis_express_pay';
    const BANK_TRANSFER_REFUND            = 'bank_transfer_refund';
    const CARD_TRANSFER_REFUND            = 'card_transfer_refund';
    const LOG_RESPONSE                    = 'log_response';
    const EXCESS_ORDER_AMOUNT             = 'excess_order_amount';
    const DISABLE_AMOUNT_CHECK            = 'disable_amount_check';
    const SUBSCRIPTION_V2                 = 'subscription_v2';
    const SUBSCRIPTION_AUTH_V2            = 'subscription_auth_v2';
    const EXPOSE_ARN_PAYMENT              = 'expose_arn_payment';
    const EXPOSE_ARN_REFUND               = 'expose_arn_refund';
    const OFFERS                          = 'offers';
    const OTP_AUTH_DEFAULT                = 'otp_auth_default';
    const CAPTURE_QUEUE                   = 'capture_queue';
    const TRANSACTION_V2                  = 'transaction_v2';
    const ES_ON_DEMAND                    = 'es_on_demand';
    const ES_AUTOMATIC                    = 'es_automatic';
    const HEADLESS_DISABLE                = 'headless_disable';
    const BIN_ISSUER_VALIDATOR            = 'bin_issuer_validator';
    const FIRST_DATA_S2S_FLOW             = 'first_data_s2s_flow';
    const OFFER_PRIVATE_AUTH              = 'offer_private_auth';
    const GOOGLE_PAY                      = 'google_pay';
    const EMANDATE_MRN                    = 'emandate_mrn';
    const DIWALI_PROMOTIONAL_PLAN         = 'diwali_promotional_plan';
    const CUSTOMER_ADDRESS                = 'customer_address';
    const IRCTC_METHODS                   = 'irctc_methods';
    const SKIP_CVV                        = 'skip_cvv';
    const BLOCK_SETTLEMENTS               = 'block_settlements';
    const TEST_MODE_SETTLEMENT            = 'test_mode_settlement';
    const SKIP_INTERNATIONAL_AUTH         = 'skip_international_auth';
    const ES_AUTOMATIC_THREE_PM           = 'es_automatic_three_pm';
    const IIN_LISTING                     = 'iin_listing';
    const CALLBACK_URL_VALIDATION         = 'callback_url_validation';
    const REPORTING_GENRERIC_NOTES        = 'report_notes_to_column';
    const S2S_OTP_JSON                    = 's2s_otp_json';
    const ALLOW_REVERSALS_FROM_LA         = 'allow_reversals_from_la';
    const ADHOC_SETTLEMENT                = 'adhoc_settlement';
    const SUB_TERMINAL_OPTIMIZE           = 'sub_terminal_optimize';
    const SHOW_REFUND_PUBLIC_STATUS       = 'show_refund_public_status';
    const OVERRIDE_SUB_CONFIG             = 'override_sub_config';
    const DOWNTIME_ROUTING                = 'downtime_routing';
    const PAYOUT_TO_CARDS                 = 'payout_to_cards';
    const PAYMENT_ONHOLD                  = 'payment_onhold';
    const GOOGLE_PAY_OMNICHANNEL          = 'google_pay_omnichannel';
    const VIJAYA_MERCHANT                 = 'vijaya_merchant';
    const HIDE_VA_PAYER_BANK_DETAIL       = 'hide_va_payer_bank_detail';
    const ASYNC_BALANCE_UPDATE            = 'async_balance_update';
    const PHONEPE_INTENT                  = 'phonepe_intent';
    const ISSUE_MPANS                     = 'issue_mpans';
    const BLOCK_DEBIT_2K                  = 'block_debit_2k';
    const WALLET_AUTO_DEBIT               = 'wallet_auto_debit';
    const USE_MSWIPE_TERMINALS            = 'use_mswipe_terminals';
    const EXPOSE_GATEWAY_PROVIDER         = 'expose_gateway_provider';
    const EXPOSE_FA_VALIDATION_UTR        = 'expose_fa_validation_utr';
    const VALIDATE_MERCHANT_DOMAIN        = 'validate_merchant_domain';
    const GOOGLE_PAY_CARDS                = 'google_pay_cards';

    const ENACH_INTERMEDIATE              = 'enach_intermediate';
    const SAVE_VPA                        = 'save_vpa';

    const PARTNER_ACTIVATE_MERCHANT       = 'partner_activate_merchant';

    const OFFLINE_PAYMENTS                = 'offline_payments';

    /**
     * If applied on partner merchant then all sub merchant settlement will be settled to partner
     * this will be further aggregated and settled to partner merchant
     */
    const AGGREGATE_SETTLEMENT            = 'aggregate_settlement';

    /**
     * When adding submerchant, whether to set international activation flow to greylist
     * irrespective of merchant category and subcategory
     */
    const FORCE_GREYLIST_INTERNAT         = 'force_greylist_internat';

    /**
     * Skipping website and allowing international activation.
     */
    const SKIP_WEBSITE_INTERNAT         = 'skip_website_internat';

    /**
     * Flag to decide whether to show D2c credit score campaign announcement on merchant dashboard.
     */
    const SHOW_CREDIT_SCORE               = 'show_credit_score';

    /**
     * When creating submerchant, if kyc is handled by partner, we proceed to directly activate the merchant, when
     * the submerchant is created. Else the submerchant will follow the usual kyc process
     */
    const KYC_HANDLED_BY_PARTNER          = 'kyc_handled_by_partner';

    /**
     * Only partners having this feature will be able to onboard the submerchants using the account apis
     */
    const SUBMERCHANT_ONBOARDING          = 'submerchant_onboarding';

    /**
     * Flag to decide whether razorpay can send communication mails to partner's submerchants
     */
    const NO_COMM_WITH_SUBMERCHANTS       = 'no_comm_with_submerchants';

    /**
     * Feature flag to enable to create new customer if contact and email both are null,
     * this functionality will be there by default for new merchants , flag need to be enabled if
     * needed for older merchants
     */
    const CUST_CONTACT_EMAIL_NULL         = 'cust_contact_email_null';

  /**
     * This will control if the bank details will be returned in the fetch token response.
     * Bank details will contain beneficiary_name, account_number, ifsc and account_type
     */
    const TOKEN_BANK_DETAILS              = 'token_bank_details';

    /**
     * Skips uniqueness checks on the `receipt` attribute on invoice and payment links
     */
    const INVOICE_NO_RECEIPT_UNIQUE       = 'invoice_no_receipt_unique';

    /**
     * Disables auto-capture of payments made on payment pages
     * (used for auto refunds on demo payment pages)
     */
    const PAYMENT_PAGES_NO_CAPTURE        = 'payment_pages_no_capture';

    /**
     * For Payment links:
     * With partial payment enabled, allows the merchant to define a min amount
     * to be paid for the first payment.
     */
    const PL_FIRST_MIN_AMOUNT             = 'pl_first_min_amount';

    const PL_HIDE_ISSUED_TO               = 'pl_hide_issued_to';

    // Orders
    const ORDER_ID_MANDATORY              = 'order_id_mandatory';
    const ORDER_RECEIPT_UNIQUE            = 'order_receipt_unique';

    /**
     * Commission invoice will be generated only for those partners
     * having this feature flag
     */
    const GENERATE_PARTNER_INVOICE        = 'generate_partner_invoice';

    /**
     * Feature flag to decide whether commission payout should happen
     * manually via ops or automatically
     */
    const AUTOMATED_COMM_PAYOUT           = 'automated_comm_payout';

    // Payment authentication
    const ATM_PIN_AUTH                    = 'atm_pin_auth';
    const IVR                             = 'ivr';

    // Pre-Auth Shield Integration
    const PRE_AUTH_SHIELD_INTG          = 'pre_auth_shield_intg';

    const EDIT_METHODS                  = 'edit_methods';

    /**
     * If set, disables all refund operations on the merchant's account
     */
    const DISABLE_REFUNDS               = 'disable_refunds';

    /**
     * If set, disables all refund operations on the merchant's card payments
     */
    const DISABLE_CARD_REFUNDS          = 'disable_card_refunds';

    /**
     * Makes `receipt` a mandatory field for invoice creation
     */
    const INVOICE_RECEIPT_MANDATORY     = 'invoice_receipt_mandatory';

    /**
     * Do no send email on expiring/expired
     */
    const INVOICE_NO_EXPIRY_EMAIL       = 'invoice_no_expiry_email';

    /**
     * For RBL we have added this feature so that we can mandate expire by for their invoices.
     */
    const INVOICE_EXPIRE_BY_REQD        = 'invoice_expire_by_reqd';

    /**
     * Enables workflow feature on Payout for Business Banking (RazorpayX)
     */
    const PAYOUT_WORKFLOWS              = 'payout_workflows';

    /**
     * Skips workflow for API requests for creating payouts for Business Banking (RazorpayX)
     */
    const SKIP_WORKFLOWS_FOR_API        = 'skip_workflow_for_api';

    /**
     * Aggregator Partner + OAuth Client access
     */
    const AGGREGATOR_OAUTH_CLIENT       = 'aggregator_oauth_client';

    // Different actions for feature activation flow
    const CREATE           = 'create';
    const UPDATE           = 'update';

    // Feature base email block
    const SELF_KYC_DISABLED      = 'self_kyc_disabled';
    const PAYMENT_MAILS_DISABLED = 'payment_mails_disabled';
    const DISPUTE_MAILS_DISABLED = 'dispute_mails_disabled';

    const BLOCK_PL_PAY_POST_EXPIRY = 'block_pl_pay_post_expiry';

    const BLOCK_OFFER_CREATION     = 'block_offer_creation';

    /*
     * this is used for displaying the parent payment id for linked accounts in Route
     */
    const DISPLAY_LA_PARENT_PAYMENT_ID = 'display_parent_payment_id';

    const REDIRECTION_ONHOLD       = 'redirection_onhold';

    const ERROR_METADATA_RESPONSE  = 'error_metadata_response';

    /*
     * This flag will be used to enable x_pro on a merchant. Once enabled the merchant can
     * decide to upgrade his account to x_pro. This feature gives flexibility initially
     * to have a controlled roll out of x_pro might be removed going forward.
     */
    const X_PRO_INVITE  = 'x_pro_invite';

    /*
     * This flag will be used to skip some merchants from hitachi automatic onboarding
     */
    const SKIP_HITACHI_AUTO_ONBOARD  = 'skip_hitachi_auto_onboard';

    public static $recurringFeatures = [
        self::CHARGE_AT_WILL,
        self::SUBSCRIPTIONS,
    ];

    public static $debitRecurringFeatures = [
        self::ALLOW_ALL_DC_RECURRING,
        self::ALLOW_DC_RECURRING
    ];

    const CHECKOUT_FEATURES = [
        self::GOOGLE_PAY,
        self::CUSTOMER_ADDRESS,
        self::IRCTC_METHODS,
        self::GOOGLE_PAY_OMNICHANNEL,
        self::PHONEPE_INTENT,
        self::SAVE_VPA,
    ];

    // TODO: Use this instead of allFeatures once in final code change pr
    /**
     * This map defines the feature's value when it is added through an incoming request (add/remove feature request).
     *
     * The feature will be added only when the incoming request has the particular feature's value set to
     * the value defined in this map. Else, it gets removed.
     *
     * Ex: The feature 'dummy' gets added when the request has 'dummy' => true and gets removed when 'dummy' => false.
     *
     * @var array
     */
    public static $featureValueMap = [
        self::DUMMY                           => true,
        self::WEBHOOKS                        => true,
        self::AGGREGATOR                      => true,
        self::TOKENS                          => true,
        self::S2SWALLET                       => true,
        self::S2SUPI                          => true,
        self::S2SAEPS                         => true,
        self::NOFLASHCHECKOUT                 => true,
        self::RECURRING                       => true,
        self::S2S                             => true,
        self::INVOICE                         => true,
        self::NOZEROPRICING                   => false,
        self::REVERSE                         => true,
        self::BROKING_REPORT                  => true,
        self::DSP_REPORT                      => true,
        self::RPP_REPORT                      => true,
        self::AGGREGATOR_REPORT               => true,
        self::PAYMENT_EMAIL_FETCH             => true,
        self::CREATED_FLOW                    => true,
        self::PAYOUT                          => true,
        self::OPENWALLET                      => true,
        self::MARKETPLACE                     => true,
        self::EMAIL_OPTIONAL                  => true,
        self::CONTACT_OPTIONAL                => true,
        self::SUBSCRIPTIONS                   => true,
        self::ZOHO                            => true,
        self::EXPOSE_DOWNTIMES                => true,
        self::PAYMENT_FAILURE_EMAIL           => true,
        self::VIRTUAL_ACCOUNTS                => true,
        self::BANK_TRANSFER_ON_CHECKOUT       => true,
        self::INVOICE_PARTIAL_PAYMENTS        => true,
        self::HIDE_DOWNTIMES                  => true,
        self::OLD_CREDITS_FLOW                => true,
        self::CHARGE_AT_WILL                  => true,
        self::SETTLEMENT_24X7                 => true,
        self::EMI_MERCHANT_SUBVENTION         => true,
        self::FSS_RISK_UDF                    => true,
        self::RULE_FILTER                     => true,
        self::TPV                             => true,
        self::IRCTC_REPORT                    => true,
        self::DISABLE_MAESTRO                 => true,
        self::DISABLE_RUPAY                   => true,
        self::BLOCK_INTERNATIONAL_RECURRING   => false,
        self::BHARAT_QR                       => true,
        self::MOBIKWIK_OFFERS                 => true,
        self::ALLOW_DC_RECURRING              => true,
        self::ALLOW_ALL_DC_RECURRING          => true,
        self::SKIP_HOLD_FUNDS_ON_PAYOUT       => true,
        self::REPORT_V2                       => true,
        self::CORPORATE_BANKS                 => true,
        self::ORDER_ID_MANDATORY              => true,
        self::ORDER_RECEIPT_UNIQUE            => true,
        self::MAGIC                           => true,
        self::NEW_ANALYTICS                   => true,
        self::DAILY_SETTLEMENT                => true,
        self::DISABLE_UPI_INTENT              => true,
        self::ATM_PIN_AUTH                    => true,
        self::ALLOW_S2S_APPS                  => true,
        self::UPI_PLUS                        => true,
        self::FSS_IPAY                        => true,
        self::DIRECT_DEBIT                    => true,
        self::EXPOSE_CARD_EXPIRY              => true,
        self::EXPOSE_CARD_IIN                 => true,
        self::S2S_OPTIONAL_DATA               => true,
        self::VOID_REFUNDS                    => true,
        self::PARTNER                         => true,
        self::PAYMENT_NOBRANDING              => true,
        self::OTPELF                          => true,
        self::ENABLE_VPA_VALIDATE             => true,
        self::ALLOW_SUBMERCHANT_WITHOUT_EMAIL => true,
        self::HDFC_DEBIT_SI                   => true,
        self::AXIS_EXPRESS_PAY                => true,
        self::PRE_AUTH_SHIELD_INTG            => true,
        self::BANK_TRANSFER_REFUND            => true,
        self::CARD_TRANSFER_REFUND            => true,
        self::LOG_RESPONSE                    => true,
        self::EXCESS_ORDER_AMOUNT             => true,
        self::DISABLE_AMOUNT_CHECK            => true,
        self::SUBSCRIPTION_V2                 => true,
        self::SUBSCRIPTION_AUTH_V2            => true,
        self::EXPOSE_ARN_PAYMENT              => true,
        self::EXPOSE_ARN_REFUND               => true,
        self::OFFERS                          => true,
        self::OTP_AUTH_DEFAULT                => true,
        self::EDIT_METHODS                    => true,
        self::CAPTURE_QUEUE                   => true,
        self::TRANSACTION_V2                  => true,
        self::ES_ON_DEMAND                    => true,
        self::ES_AUTOMATIC                    => true,
        self::HEADLESS_DISABLE                => true,
        self::FIRST_DATA_S2S_FLOW             => true,
        self::BIN_ISSUER_VALIDATOR            => true,
        self::OFFER_PRIVATE_AUTH              => true,
        self::GOOGLE_PAY                      => true,
        self::EMANDATE_MRN                    => true,
        self::DIWALI_PROMOTIONAL_PLAN         => true,
        self::CUSTOMER_ADDRESS                => true,
        self::IRCTC_METHODS                   => true,
        self::SKIP_CVV                        => true,
        self::BLOCK_SETTLEMENTS               => true,
        self::SKIP_INTERNATIONAL_AUTH         => true,
        self::INVOICE_NO_RECEIPT_UNIQUE       => true,
        self::PAYMENT_PAGES_NO_CAPTURE        => true,
        self::ES_AUTOMATIC_THREE_PM           => true,
        self::IIN_LISTING                     => true,
        self::CALLBACK_URL_VALIDATION         => true,
        self::PL_FIRST_MIN_AMOUNT             => true,
        self::PL_HIDE_ISSUED_TO               => true,
        self::REPORTING_GENRERIC_NOTES        => true,
        self::IVR                             => true,
        self::S2S_OTP_JSON                    => true,
        self::S2S_JSON                        => true,
        self::FUND_ACCOUNT_VALIDATIONS        => true,
        self::DISABLE_REFUNDS                 => true,
        self::DISABLE_CARD_REFUNDS            => true,
        self::TOKEN_BANK_DETAILS              => true,
        self::INVOICE_RECEIPT_MANDATORY       => true,
        self::INVOICE_NO_EXPIRY_EMAIL         => true,
        self::INVOICE_EXPIRE_BY_REQD          => true,
        self::SELF_KYC_DISABLED               => true,
        self::PAYMENT_MAILS_DISABLED          => true,
        self::DISPUTE_MAILS_DISABLED          => true,
        self::ALLOW_REVERSALS_FROM_LA         => true,
        self::BLOCK_PL_PAY_POST_EXPIRY        => true,
        self::ADHOC_SETTLEMENT                => true,
        self::SUB_TERMINAL_OPTIMIZE           => true,
        self::SHOW_REFUND_PUBLIC_STATUS       => true,
        self::OVERRIDE_SUB_CONFIG             => true,
        self::DOWNTIME_ROUTING                => true,
        self::PAYOUT_TO_CARDS                 => true,
        self::PAYMENT_ONHOLD                  => true,
        self::X_PRO_INVITE                    => true,
        self::GOOGLE_PAY_OMNICHANNEL          => true,
        self::TERMINAL_ONBOARDING             => true,
        self::TEST_MODE_SETTLEMENT            => true,
        self::VIJAYA_MERCHANT                 => true,
        self::PAYOUT_WORKFLOWS                => true,
        self::ASYNC_BALANCE_UPDATE            => true,
        self::ISSUE_MPANS                     => true,
        self::CUST_CONTACT_EMAIL_NULL         => true,
        self::PHONEPE_INTENT                  => true,
        self::BLOCK_DEBIT_2K                  => true,
        self::USE_MSWIPE_TERMINALS            => true,
        self::WALLET_AUTO_DEBIT               => true,
        self::EXPOSE_GATEWAY_PROVIDER         => true,
        self::KYC_HANDLED_BY_PARTNER          => true,
        self::NO_COMM_WITH_SUBMERCHANTS       => true,
        self::SUBMERCHANT_ONBOARDING          => true,
        self::EXPOSE_FA_VALIDATION_UTR        => true,
        self::VALIDATE_MERCHANT_DOMAIN        => true,
        self::PARTNER_ACTIVATE_MERCHANT       => true,
        self::GOOGLE_PAY_CARDS                => true,
        self::FORCE_GREYLIST_INTERNAT         => true,
        self::SKIP_WEBSITE_INTERNAT           => true,
        self::SHOW_CREDIT_SCORE               => true,
        self::AGGREGATOR_OAUTH_CLIENT         => true,
        self::AGGREGATE_SETTLEMENT            => true,
        self::BLOCK_OFFER_CREATION            => true,
        self::ENACH_INTERMEDIATE              => true,
        self::SAVE_VPA                        => true,
        self::GENERATE_PARTNER_INVOICE        => true,
        self::AUTOMATED_COMM_PAYOUT           => true,
        self::OFFLINE_PAYMENTS                => true,
        self::ERROR_METADATA_RESPONSE         => true,
        self::SKIP_HITACHI_AUTO_ONBOARD       => true,
        self::SKIP_WORKFLOWS_FOR_API          => true,
        self::DISPLAY_LA_PARENT_PAYMENT_ID    => true,
        self::REDIRECTION_ONHOLD              => true,
    ];

    // Entity type constants
    const ACCOUNT                       = 'account';
    const MERCHANT                      = 'merchant';
    const APPLICATION                   = 'application';

    // Keys used in the feature on-boarding workflow
    const STATUS                        = 'status';
    const PRODUCT                       = 'product';
    const FEATURES                      = 'features';
    const ONBOARDING                    = 'onboarding';
    const ONBOARDING_SUBMISSIONS_FETCH  = 'onboarding_submissions_fetch';
    const ONBOARDING_SUBMISSIONS_UPSERT = 'onboarding_submissions_upsert';

    // Keys used to define the question names in the on-boarding process
    const BUSINESS_MODEL           = 'business_model';
    const EXPECTED_MONTHLY_REVENUE = 'expected_monthly_revenue';
    const SETTLING_TO              = 'settling_to';
    const VENDOR_AGREEMENT         = 'vendor_agreement';
    const SAMPLE_PLANS             = 'sample_plans';
    const USE_CASE                 = 'use_case';
    const WEBSITE_DETAILS          = 'website_details';

    // Keys that will describe the above-mentioned questions
    const ID                  = 'id';
    const QUESTION            = 'question';
    const DESCRIPTION         = 'description';
    const RESPONSE_TYPE       = 'response_type';
    const AVAILABLE_RESPONSES = 'available_responses';
    const MANDATORY           = 'mandatory';

    const ONBOARDING_STATUSES = [
        MerchantDetail\Entity::PENDING,
        MerchantDetail\Entity::REJECTED,
        MerchantDetail\Entity::APPROVED,
    ];

    /**
     * Features that are exposed to the merchant on the dashboard
     *
     * @var array
     */
    public static $visibleFeaturesMap = [
        self::NOFLASHCHECKOUT  => [
            'feature'       => self::NOFLASHCHECKOUT,
            'display_name'  => 'No Flash Checkout',
            'documentation' => '',
        ],
        self::MARKETPLACE      => [
            'feature'       => self::MARKETPLACE,
            'display_name'  => 'Route',
            'documentation' => 'route',
        ],
        self::SUBSCRIPTIONS    => [
            'feature'       => self::SUBSCRIPTIONS,
            'display_name'  => 'Subscriptions',
            'documentation' => 'subscriptions',
        ],
        self::VIRTUAL_ACCOUNTS => [
            'feature'       => self::VIRTUAL_ACCOUNTS,
            'display_name'  => 'Smart Collect',
            'documentation' => 'smart-collect',
        ],
        self::PAYOUT    => [
            'feature'       => self::PAYOUT,
            'display_name'  => 'Payouts',
            'documentation' => 'payouts',
        ],
        self::REPORT_V2 => [
            'feature'       => self::REPORT_V2,
            'display_name'  => 'Report V2',
            'documentation' => '',
        ],
        self::ES_ON_DEMAND              => [
            'feature'       => self::ES_ON_DEMAND,
            'display_name'  => 'On demand Payout',
            'documentation' => '',
        ],
        self::ES_AUTOMATIC              => [
            'feature'       => self::ES_AUTOMATIC,
            'display_name'  => 'Es Automatic',
            'documentation' => '',
        ],
        self::PL_FIRST_MIN_AMOUNT       => [
            'feature'       => self::PL_FIRST_MIN_AMOUNT,
            'display_name'  => 'Partial payments: minimum first amount',
            'documentation' => '',
        ],
        self::DISABLE_REFUNDS           => [
            'feature'       => self::DISABLE_REFUNDS,
            'display_name'  => 'Disable Refund Operations',
            'documentation' => '',
        ],
        self::DISABLE_CARD_REFUNDS      => [
            'feature'       => self::DISABLE_CARD_REFUNDS,
            'display_name'  => 'Disable Refunds on Card Payments',
            'documentation' => '',
        ],
        self::INVOICE_RECEIPT_MANDATORY => [
            'feature'       => self::INVOICE_RECEIPT_MANDATORY,
            'display_name'  => 'Mandatory invoice receipt field',
            'documentation' => '',
        ],
        self::INVOICE_EXPIRE_BY_REQD    => [
            'feature'       => self::INVOICE_EXPIRE_BY_REQD,
            'display_name'  => 'Mandatory invoice expire_by field',
            'documentation' => '',
        ],
        self::ALLOW_REVERSALS_FROM_LA    => [
            'feature'       => self::ALLOW_REVERSALS_FROM_LA,
            'display_name'  => 'Allow Refunds From Linked Accounts',
            'documentation' => '',
        ],
        self::DISPLAY_LA_PARENT_PAYMENT_ID    => [
            'feature'       => self::DISPLAY_LA_PARENT_PAYMENT_ID,
            'display_name'  => 'display parent paymentId for transfers',
            'documentation' => '',
        ],
        self::PAYOUT_TO_CARDS           => [
            'feature'       => self::PAYOUT_TO_CARDS,
            'display_name'  => 'Payout to cards',
            'documentation' => '',
        ],
        self::X_PRO_INVITE              => [
            'feature'       => self::X_PRO_INVITE,
            'display_name'  => 'Razorpay X Pro Invite',
            'documentation' => '',
        ],
        self::PAYOUT_WORKFLOWS          => [
            'feature'       => self::PAYOUT_WORKFLOWS,
            'display_name'  => 'Razorpay X - Workflows',
            'documentation' => '',
        ],
        self::SHOW_CREDIT_SCORE         => [
            'feature'       => self::SHOW_CREDIT_SCORE,
            'display_name'  => 'D2C Credit score campaign',
            'documentation' => '',
        ],
        self::SKIP_WORKFLOWS_FOR_API         => [
            'feature'       => self::SKIP_WORKFLOWS_FOR_API,
            'display_name'  => 'Razorpay X - Skip workflows for API requests',
            'documentation' => '',
        ],
    ];

    /**
     * Features that merchants can enable/disable
     * Must be defined in the visibleFeaturesMap
     * The features defined here which are a part of the PRODUCT_FEATURES array,
     * will not be editable by the merchant in the live mode.
     *
     * @var array
     */
    public static $merchantEditableFeatures = [
        self::NOFLASHCHECKOUT,
        self::MARKETPLACE,
        self::SUBSCRIPTIONS,
        self::VIRTUAL_ACCOUNTS,
        self::ES_AUTOMATIC,
        self::SHOW_CREDIT_SCORE,
        self::SKIP_WORKFLOWS_FOR_API
    ];

    /*
     * PRODUCT_FEATURES should be a subset of the visible features.
     * If any of these features are enabled on live mode, the user will be notified through an email.
     * Product features can be enabled/disabled on test mode by the merchant, but not on the live mode.
     */
    const PRODUCT_FEATURES = [
        self::MARKETPLACE,
        self::SUBSCRIPTIONS,
        self::VIRTUAL_ACCOUNTS
    ];

    /**
     * Note: If the RESPONSE_TYPE is file, then,
     * a corresponding entry should be made in the class 'Models/Filestore/Type'
     */

    /**
     * Stores the details for each question irrespective of the feature that it belongs to.
     * TODO: These constants can be moved into separate constants file for questions
     */
    public static $questionMap = [
        self::USE_CASE => [
            self::QUESTION            => 'What is your use case?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SETTLING_TO => [
            self::QUESTION            => 'Who are you settling to?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'radio',
            self::AVAILABLE_RESPONSES => [
                'Third party businesses',
                'Own bank accounts',
                'Individuals'
            ],
            self::MANDATORY           => true
        ],

        self::VENDOR_AGREEMENT => [
            self::QUESTION            => 'Please upload a copy of a signed agreement with the third party',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'file',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => false
        ],

        self::BUSINESS_MODEL => [
            self::QUESTION            => 'What is your business model and requirement?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SAMPLE_PLANS => [
            self::QUESTION            => 'Sample plans',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::WEBSITE_DETAILS => [
            self::QUESTION            => 'Is website live? If yes, link to the page with more details',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'text',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::EXPECTED_MONTHLY_REVENUE => [
            self::QUESTION            => 'Expected monthly revenue using this feature?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'number',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ]
    ];

    /**
     * Stores the mapping of the features to their corresponding questions
     */
    public static $featureQuestionsMap = [
        self::MARKETPLACE => [
            self::USE_CASE,
            self::SETTLING_TO,
            self::VENDOR_AGREEMENT
        ],

        self::SUBSCRIPTIONS => [
            self::BUSINESS_MODEL,
            self::SAMPLE_PLANS,
            self::WEBSITE_DETAILS
        ],

        self::VIRTUAL_ACCOUNTS => [
            self::USE_CASE,
            self::EXPECTED_MONTHLY_REVENUE
        ]
    ];

    /**
     * Returns a nested structure of the questions for the feature param passed along
     * with all the details for each question
     *
     * @param string $featureName
     *
     * @return array
     */
    public static function getFeatureQuestions(string $featureName): array
    {
        $response = [];

        if (key_exists($featureName, self::$featureQuestionsMap))
        {
            $questions = self::$featureQuestionsMap[$featureName];

            foreach ($questions as $question)
            {
                $response[$question] = self::$questionMap[$question];
            }
        }

        return $response;
    }

    public static function getFeatureValue($featureName): bool
    {
        return self::$featureValueMap[$featureName];
    }
}
