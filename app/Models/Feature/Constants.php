<?php

namespace RZP\Models\Feature;

use RZP\Models\Merchant\Detail as MerchantDetail;

class Constants
{

    //M2M referral feature metadata
    const M2M_REFERRAL                          = 'm2m_referral';

    //M2M referral environment keys
    const M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED           = "M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED";

    const ENTITY_IDS                      = 'entity_ids';
    const ENTITY_TYPE                     = 'entity_type';
    const NAMES                           = 'names';
    const DUMMY                           = 'dummy';
    const WEBHOOKS                        = 'webhooks';
    const AGGREGATOR                      = 'aggregator';
    const TERMINAL_ONBOARDING             = 'terminal_onboarding';
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
    const PAYOUTS_BATCH                   = 'payouts_batch';
    const OPENWALLET                      = 'openwallet';
    const MARKETPLACE                     = 'marketplace';
    const EMAIL_OPTIONAL                  = 'email_optional';
    const CONTACT_OPTIONAL                = 'contact_optional';
    const SUBSCRIPTIONS                   = 'subscriptions';
    const ZOHO                            = 'zoho';
    const EXPOSE_DOWNTIMES                = 'expose_downtimes';
    const PAYMENT_FAILURE_EMAIL           = 'payment_failure_email';
    const VIRTUAL_ACCOUNTS                = 'virtual_accounts';
    const QR_CODES                        = 'qr_codes';
    const QR_IMAGE_CONTENT                = 'qr_image_content';
    const QR_IMAGE_PARTNER_NAME           = 'qr_image_partner_name';
    const VIRTUAL_ACCOUNTS_BANKING        = 'virtual_accounts_banking';
    const BANK_TRANSFER_ON_CHECKOUT       = 'bank_transfer_on_checkout';
    const CHECKOUT_VA_WITH_CUSTOMER       = 'checkout_va_with_customer';
    const INVOICE_PARTIAL_PAYMENTS        = 'invoice_partial_payments';
    const HIDE_DOWNTIMES                  = 'hide_downtimes';
    const OLD_CREDITS_FLOW                = 'old_credits_flow';
    const DISABLE_FREE_CREDIT_REG         = 'disable_free_credit_reg';
    const DISABLE_FREE_CREDIT_UNREG       = 'disable_free_credit_unreg';
    const EXPOSE_EXTRA_ATTRIBUTES         = 'expose_extra_attributes';
    const SHOW_LATE_AUTH_ATTRIBUTES       = 'show_late_auth_attributes';
    const SHOW_REFND_LATEAUTH_PARAM       = 'show_refnd_lateauth_param';
    const CHARGE_AT_WILL                  = 'charge_at_will';
    const EMI_MERCHANT_SUBVENTION         = 'emi_merchant_subvention';
    const FSS_RISK_UDF                    = 'fss_risk_udf';
    const RULE_FILTER                     = 'rule_filter';
    const TPV                             = 'tpv';
    const IRCTC_REPORT                    = 'irctc_report';
    const DISABLE_MAESTRO                 = 'disable_maestro';
    const DISABLE_RUPAY                   = 'disable_rupay';
    const BLOCK_INTERNATIONAL_RECURRING   = 'block_intl_recurring';
    const BHARAT_QR                       = 'bharat_qr';
    const BHARAT_QR_V2                    = 'bharat_qr_v2';
    const MOBIKWIK_OFFERS                 = 'mobikwik_offers';
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
    const NON_TPV_BT_REFUND               = 'non_tpv_bt_refund';
    const DISABLE_INSTANT_REFUNDS         = 'disable_instant_refunds';
    const REFUND_AGED_PAYMENTS            = 'refund_aged_payments';
    const LOG_RESPONSE                    = 'log_response';
    const EXCESS_ORDER_AMOUNT             = 'excess_order_amount';
    const DISABLE_AMOUNT_CHECK            = 'disable_amount_check';
    const SUBSCRIPTION_V2                 = 'subscription_v2';
    const SUBSCRIPTION_AUTH_V2            = 'subscription_auth_v2';
    const EXPOSE_ARN_REFUND               = 'expose_arn_refund';
    const OFFERS                          = 'offers';
    const OTP_AUTH_DEFAULT                = 'otp_auth_default';
    const CAPTURE_QUEUE                   = 'capture_queue';
    const ASYNC_CAPTURE                   = 'async_capture';
    const TRANSACTION_V2                  = 'transaction_v2';
    const ES_ON_DEMAND                    = 'es_on_demand';
    const ES_ON_DEMAND_RESTRICTED         = 'es_on_demand_restricted';
    const BLOCK_ES_ON_DEMAND              = 'block_es_on_demand';
    const UPDATED_IMPS_ONDEMAND           = 'updated_imps_ondemand';
    const ES_AUTOMATIC                    = 'es_automatic';
    const ES_AUTOMATIC_RESTRICTED         = 'es_automatic_restricted';
    const HEADLESS_DISABLE                = 'headless_disable';
    const BEPG_DISABLE                    = 'bepg_disable';
    const BIN_ISSUER_VALIDATOR            = 'bin_issuer_validator';
    const FIRST_DATA_S2S_FLOW             = 'first_data_s2s_flow';
    const OFFER_PRIVATE_AUTH              = 'offer_private_auth';
    const GOOGLE_PAY                      = 'google_pay';
    const EMANDATE_MRN                    = 'emandate_mrn';
    const DIWALI_PROMOTIONAL_PLAN         = 'diwali_promotional_plan';
    const CARD_FINGERPRINTS               = 'card_fingerprints';
    const CUSTOMER_ADDRESS                = 'customer_address';
    const IRCTC_METHODS                   = 'irctc_methods';
    const SKIP_CVV                        = 'skip_cvv';
    const BLOCK_SETTLEMENTS               = 'block_settlements';
    const RECURRING_AUTO                  = 'recurring_auto';
    const TEST_MODE_SETTLEMENT            = 'test_mode_settlement';
    const SKIP_INTERNATIONAL_AUTH         = 'skip_international_auth';
    const ES_AUTOMATIC_THREE_PM           = 'es_automatic_three_pm';
    const IIN_LISTING                     = 'iin_listing';
    const BIN_API                         = 'bin_api';
    const CALLBACK_URL_VALIDATION         = 'callback_url_validation';
    const REPORTING_GENRERIC_NOTES        = 'report_notes_to_column';
    const S2S_OTP_JSON                    = 's2s_otp_json';
    const ALLOW_REVERSALS_FROM_LA         = 'allow_reversals_from_la';
    const ADHOC_SETTLEMENT                = 'adhoc_settlement';
    const NEW_SETTLEMENT_SERVICE          = 'new_settlement_service';
    const SUB_TERMINAL_OPTIMIZE           = 'sub_terminal_optimize';
    const SHOW_REFUND_PUBLIC_STATUS       = 'show_refund_public_status';
    const REFUND_PENDING_STATUS           = 'refund_pending_status';
    const OVERRIDE_SUB_CONFIG             = 'override_sub_config';
    const DOWNTIME_ROUTING                = 'downtime_routing';
    const PAYOUT_TO_CARDS                 = 'payout_to_cards';
    const PAYMENT_ONHOLD                  = 'payment_onhold';
    const GOOGLE_PAY_OMNICHANNEL          = 'google_pay_omnichannel';
    const VAS_MERCHANT                    = 'vas_merchant';
    const VIJAYA_MERCHANT                 = 'vijaya_merchant';
    const HIDE_VA_PAYER_BANK_DETAIL       = 'hide_va_payer_bank_detail';
    const ASYNC_BALANCE_UPDATE            = 'async_balance_update';
    const ASYNC_TXN_FILL_DETAILS          = 'async_txn_fill_details';
    const PHONEPE_INTENT                  = 'phonepe_intent';
    const ISSUE_MPANS                     = 'issue_mpans';
    const BLOCK_DEBIT_2K                  = 'block_debit_2k';
    const WALLET_AUTO_DEBIT               = 'wallet_auto_debit';
    const USE_MSWIPE_TERMINALS            = 'use_mswipe_terminals';
    const EXPOSE_GATEWAY_PROVIDER         = 'expose_gateway_provider';
    const EXPOSE_FA_VALIDATION_UTR        = 'expose_fa_validation_utr';
    const EXPOSE_SETTLED_BY               = 'expose_settled_by';
    const GOOGLE_PAY_CARDS                = 'google_pay_cards';
    const GPAY                            = 'gpay';
    const PAYPAL_CC                       = 'paypal_cc';
    const SOURCED_BY_WALNUT369            = 'sourced_by_walnut369';
    const RAZORPAY_WALLET                 = 'razorpay_wallet';
    const SR_SENSITIVE_BUCKET_1           = 'sr_sensitive_bucket_1';
    const SR_SENSITIVE_BUCKET_2           = 'sr_sensitive_bucket_2';
    const SR_SENSITIVE_BUCKET_3           = 'sr_sensitive_bucket_3';
    const SR_SENSITIVE_BUCKET_4           = 'sr_sensitive_bucket_4';

    const FEATURE                         = 'feature';
    const DISPLAY_NAME                    = 'display_name';
    const CONTACT_NAME                    = 'contact_name';
    const DOCUMENTATION                   = 'documentation';
    const FEATURE_NAMES                   = 'feature_names';

    const ENACH_INTERMEDIATE              = 'enach_intermediate';
    const SAVE_VPA                        = 'save_vpa';
    const UPI_OTM                         = 'upi_otm';

    const PARTNER_ACTIVATE_MERCHANT       = 'partner_activate_merchant';

    const OFFLINE_PAYMENTS                = 'offline_payments';

    const FTS_REQUEST_NOTES               = 'fts_request_notes';

    const DISABLE_NATIVE_CURRENCY         = 'disable_native_currency';

    const DCC_ON_OTHER_LIBRARY            = 'dcc_on_other_library';

    const DIRECT_SETTLEMENT               = 'direct_settlement';

    const AVS                             = 'avs';

    const COVID                           = 'covid';
    const SR_SENSITIVE                    = 'sr_sensitive';
    const RAAS                            = 'raas';
    const OPTIMIZER_SMART_ROUTER          = 'optimizer_smart_router';
    const SKIP_NOTES_MERGING              = 'skip_notes_merging';
    const ENABLE_SINGLE_RECON             = 'enable_single_recon';

    // Ledger constants
    const IDEMPOTENCY_KEY                   = 'idempotency_key';
    const MERCHANT_ID                       = 'merchant_id';
    const MODE                              = 'mode';
    const PG_GATEWAY_ONBOARD                = 'pg_gateway_onboard';
    const SUCCESS                           = 'success';
    const FAILURE                           = 'failure';
    const MERCHANT_FEATURE_ALREADY_ENABLED  = 'merchant feature already enabled';

    const PAYMENT_STATUS_AGGREGATE        = 'payment_status_aggregate';
    const VISA_SAFE_CLICK                 = 'vsc_authorization';

    const P2P_UPI                         = 'p2p_upi';

    const CAPITAL_CARDS_COLLECTIONS       = 'capital_cards_collections';
    /**
    Prevents user to switch to test mode from live mode
     */
    const PREVENT_TEST_MODE               = 'prevent_test_mode';

    /**
     * Disables retry option in checkout
     */
    const CHECKOUT_DISABLE_RETRY          = 'checkout_disable_retry';

    /**
     * Disables vernacular in checkout
     */
    const CHECKOUT_DISABLE_I18N     = 'checkout_disable_i18n';

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
     * When creating submerchant in bulk, merchant name is synced with business name which is same for all submerchants
     * of a partner. To avoid syncing business_name with merchant_name, this feature flag is used.
     */
    const RETAIN_SUB_MERCHANT_NAME    = 'retain_sub_merchant_name';

    /**
     * This feature will enable
     * partners to onboard sub-merchants without uploading their verification documents.
     */
    const SUBM_NO_DOC_ONBOARDING    = 'subm_no_doc_onboarding';

    //this will be assigned to submerchant if partner has no_doc_onboarding enabled.
    const NO_DOC_ONBOARDING = 'no_doc_onboarding';

    // to get nach form direct download url
    const NACH_FORM_DIRECT_DOWNLOAD = 'nach_form_direct_download';

    // ignore customer contact details check for CAW Auth Link
    const CAW_IGNORE_CUSTOMER_CHECK = 'caw_ignore_customer_check';

    // feature flag for the CAW and Subscriptions UPI for FrontEnd
    const CAW_UPI                         = 'caw_upi';
    const SUBSCRIPTION_UPI                = 'subscription_upi';

    // feature flag for the AXIS recurring charge
    const CAW_RECURRING_CHARGE_AXIS = 'caw_recurring_charge_axis';

    /**
     * skip summary page for card mandate recurring initial payment
     */
    const CARD_MANDATE_SKIP_PAGE = 'card_mandate_skip_page';

    /**
     * Only partners having this feature will be able to onboard the submerchants using the account apis
     */
    const SUBMERCHANT_ONBOARDING          = 'submerchant_onboarding';


    /**
     * This feature is assigned for the partners who are doing submerchant product onboarding via V2 onboarding APIs
     */
    const SUBMERCHANT_ONBOARDING_V2 = 'submerchant_onboarding_v2';

    /**
     * This feature will be assinged to all submerchants onboarded via V2 onboarding APIs
     */
    const CREATE_SOURCE_V2 = 'create_source_v2';

    /**
     * Flag to decide whether razorpay can send communication mails to partner's submerchants
     */
    const NO_COMM_WITH_SUBMERCHANTS       = 'no_comm_with_submerchants';


    /**
     * Feature flag to skip approval workflow to access submerchant Kyc.
     */
    const PARTNER_SUB_KYC_ACCESS        = 'partner_sub_kyc_access';

    /**
     * Feature flag to enable to create new customer if contact and email both are null,
     * this functionality will be there by default for new merchants , flag need to be enabled if
     * needed for older merchants
     */
    const CUST_CONTACT_EMAIL_NULL         = 'cust_contact_email_null';

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

    /**
     * For emandate and nach debit payments:
     * With this feature enabled merchant can pass UMRN (gateway token)
     * instead of token_id in token field while creating debit payments
     */
    const RECURRING_DEBIT_UMRN = 'recurring_debit_umrn';

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
    const IVR_DISABLE                     = 'ivr_disable';

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
     * If set, disables all auto refund operations (from RZP side) on the merchant's account
     */
    const DISABLE_AUTO_REFUNDS               = 'disable_auto_refunds';

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
     * Please check WorkflowFeature.php before modifying this value
     */
    const PAYOUT_WORKFLOWS              = 'payout_workflows';

    /**
     * Skips workflow for API requests for creating payouts for Business Banking (RazorpayX)
     * Please check WorkflowFeature.php before modifying this value
     */
    const SKIP_WORKFLOWS_FOR_API        = 'skip_workflow_for_api';

    /**
     * Skips workflow for Payroll requests for creating payouts for Business Banking (RazorpayX)
     * Please check WorkflowFeature.php before modifying this value
     */
    const SKIP_WF_FOR_PAYROLL           = 'skip_wf_for_payroll';

    /**
     * Skips workflow for Payout Link requests for creating payouts for Business Banking (RazorpayX)
     * Please check WorkflowFeature.php before modifying this value
     */
    const SKIP_WF_FOR_PAYOUT_LINK       = 'skip_wf_for_payout_link';

    /**
     * Skips workflow payout specific requests for creating payouts for Business Banking (RazorpayX)
     * Please check WorkflowFeature.php before modifying this value
     */
    const SKIP_WF_AT_PAYOUTS            = 'skip_wf_at_payouts';

    /**
     * new banking error response is enabled by the merchant.
     */
    const NEW_BANKING_ERROR             = 'new_banking_error';

    /**
     * Aggregator Partner + OAuth Client access
     */
    const AGGREGATOR_OAUTH_CLIENT       = 'aggregator_oauth_client';

    /**
     * Gives access to apply for loan
     */
    const LOAN = 'loan';

    /**
     * Gives access to apply for cash advance
     */
    const LOC = 'loc';

    /**
     * Gives access to apply for capital card
     */
    const CAPITAL_CARDS_ELIGIBLE = 'capital_cards_eligible';

    /**
     * Alerts in #capital_cards_anomaly_alerts when merchant makes a transaction outside this limit value mapped in capital-cards
     */
    const CARDS_TRANSACTION_LIMIT_1 = 'cards_transaction_limit_1';

    /**
     * Alerts in #capital_cards_anomaly_alerts when merchant makes a transaction outside this limit value mapped in capital-cards
     */
    const CARDS_TRANSACTION_LIMIT_2 = 'cards_transaction_limit_2';

    /**
     * Gives access to los service
     */
    const LOS = 'los';

    /**
     * Gives access to loc service
     */
    const WITHDRAW_LOC = 'withdraw_loc';

    /**
     * Gives access to loc service for es amazon merchants
     */
    const WITHDRAWAL_ES_AMAZON = 'withdrawal_es_amazon';

    /**
     * Enables Cash Advance merchants to see lender migration specific details
     */
    const LOC_ESIGN = 'loc_esign';

    /**
     * Dashboard shows cashback offer provided with LOC
     */
    const LOC_FIRST_WITHDRAWAL = 'loc_first_withdrawal';

    /**
     * Gives access to loc service
     */
    const CAPITAL_CARDS = 'capital_cards';

    /**
     * Gives access to apply for line of credit service
     */
    const LOC_STAGE_1 = 'loc_stage_1';

    /**
     * Gives access to apply for line of credit service
     */
    const LOC_STAGE_2 = 'loc_stage_2';

    /**
     *  Disables ondemand since LOC is in DPD
     * Controlled by collection service
     */
    const DISABLE_ONDEMAND_FOR_LOC = 'disable_ondemand_for_loc';

    /**
     *  Disables ondemand since Loans is in DPD
     * Controlled by collection service
     */
    const DISABLE_ONDEMAND_FOR_LOAN = 'disable_ondemand_for_loan';

    /**
     *  Disables ondemand since Cards is in DPD
     * Controlled by collection service
     */
    const DISABLE_ONDEMAND_FOR_CARD = 'disable_ondemand_for_card';

    /**
     * Disables cards post dpd since merchant did not repay
     * Controlled by collections service
     */
    const DISABLE_CARDS_POST_DPD = "disable_cards_post_dpd";

    /**
     * Disables loans post dpd since merchant did not repay
     * Controlled by collections service
     */
    const DISABLE_LOANS_POST_DPD = "disable_loans_post_dpd";

    /**
     * Disables loc post dpd since merchant did not repay
     * Controlled by collections service
     */
    const DISABLE_LOC_POST_DPD = "disable_loc_post_dpd";

    /**
     * Disables amazon_is post dpd since merchant did not repay
     * Controlled by collections service
     */
    const DISABLE_AMAZON_IS_POST_DPD = "disable_amazonis_post_dpd";

    /**
     * Flag to use settlement/ondemand route for ondemand settlement .
     */
    const USE_SETTLEMENT_ONDEMAND      = 'use_settlement_ondemand';

    /**
     * Flag to show deductions for instant settlements in dashboard.
     */
    const SHOW_ON_DEMAND_DEDUCTION      = 'show_on_demand_deduction';

    /**
     * Gives access to fetching bank statement using netbanking flow
     */
    const ALLOW_NETBANKING_FETCH = 'allow_netbanking_fetch';

    /**
     * Gives access to NON-FLDG Loan Products
     */
    const ALLOW_NON_FLDG_LOANS = "allow_non_fldg_loans";

    /**
     * Gives access to Bulk edit VAN
     */
    const VA_EDIT_BULK = "va_edit_bulk";

    /**
     * Gives access to ES-AMAZON Loan Product
     */
    const ALLOW_ES_AMAZON = "es_amazon";

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

    /*
     * This flag will be used to enable x_pro on a merchant. Once enabled the merchant can
     * decide to upgrade his account to x_pro. This feature gives flexibility initially
     * to have a controlled roll out of x_pro might be removed going forward.
     */
    const X_PRO_INVITE  = 'x_pro_invite';

    /*
     * This is a feature flag is switch for Payout-Links V1+(Dashboard access) for merchants to access from microservice.
     */
    const X_PAYOUT_LINKS_MS  = 'x_payout_links_ms';

    /*
     * This flag will be used to skip some merchants from hitachi automatic onboarding
     */
    const SKIP_HITACHI_AUTO_ONBOARD  = 'skip_hitachi_auto_onboard';

    /*
     * This flag will be used to skip some merchants from fulcrum automatic onboarding
     */
    const SKIP_FULCRUM_AUTO_ONBOARD  = 'skip_fulcrum_auto_onboard';

    /*
     * This flag will be used to override the blacklist for hitachi blacklisted MCCs
     */
    const OVERRIDE_HITACHI_BLACKLIST = 'override_hitachi_blacklst'; // not a typo - there is a column length limit

    /*
     * This flag will be used to enable payment created webhook
     */
    const PAYMENT_CREATED_WEBHOOK  = 'payment_created_webhook';

    const PAYMENT_CONFIG_ENABLED   = 'payment_config_enabled';

    /*
     * This feature will be used to pass order receipt in cybersource gateway requests
     * for merchant that will have recon at their end.
     */
    const CYBERSOURCE_VAS = 'cybersource_vas';
    /*
     * This flag when enabled, will enable payment link dashboard to hit new set of
     * payment link service endpoints
     */
    const PAYMENTLINKS_V2                = 'paymentlinks_v2';

    const PAYMENTLINKS_COMPATIBILITY_V2  = 'paymentlinks_v2_compat';
    /*
     * This flag when enabled, send checkout_config_id will be send in order response
     * if the value is not null
    */
    const SEND_PAYMENT_CONFIG_ID         = 'send_payment_config_id';

    const JSON_V2                        = 'json_v2';
    const SEND_PAYMENT_LATE_AUTH         = 'send_payment_late_auth';

    /*
     * This feature will be used to pass the transacting gateway merchant id in payment
     * create request by cred to select transacting merchant terminals
     */
    const CHARGE_ACCOUNT                 = 'charge_account';

    const TRANSACTION_ON_HOLD            = 'transaction_on_hold';

    //Disable download report actions for View Only Role for RazorpayX dashboard
    const RX_BLOCK_REPORT_DOWNLOAD       = 'rx_block_report_download';

    //Show payout source in payout details
    const RX_SHOW_PAYOUT_SOURCE       = 'rx_show_payout_source';

    //NPS FEATURES

    const NPS_SURVEY_PAYMENT_LINKS       = 'nps_survey_payment_links';
    const NPS_SURVEY_PAYMENT_PAGES       = 'nps_survey_payment_pages';
    const NPS_SURVEY_PAYMENT_GATEWAY_1M  = 'nps_survey_pg_1m';
    const NPS_SURVEY_PAYMENT_GATEWAY_6M  = 'nps_survey_pg_6m';
    const NPS_SURVEY_PAYMENT_GATEWAY_12M = 'nps_survey_pg_12m';
    const NPS_SURVEY_OTHER_PRODUCTS      = 'nps_survey_other_products';

    const BLOCK_ONBOARDING_SMS           = 'block_onboarding_sms';

    //OTP Submit response feature
    const OTP_SUBMIT_RESPONSE            = 'otp_submit_response';

    /*
    * This feature is enabled  when the merchant wants emandate registration with aadhaar auth type.
    */
    const ESIGN = 'esign';

    /*
    * This feature is enabled to merchants if merchant is live on emandates with more than one aggregator.
    */
    const NPCI_SPID = 'npci_spid';

    /*
    * This feature is enabled when the merchant wants emandate debit to have same day settlement.
    */
    const EARLY_MANDATE_PRESENTMENT = 'early_mandate_presentment';

    // For merchants who want to use the alias feature on Route.
    const ROUTE_CODE_SUPPORT                = 'route_code_support';

    // google imali auth split feature
    const AUTH_SPLIT                     = 'auth_split';

    /*
    * This feature is used to allow forcing terminal_id in terminal selection during payment.
    * Its only used for terminal testing and will be enabled only on our test merchants
    */
    const ALLOW_FORCE_TERMINAL_ID = 'allow_force_terminal_id';
    /**
     * Disables card scan
     */
    const CHECKOUT_DISABLE_CARDSCAN          = 'checkout_disable_cardscan';

    // for sending x banking accounts to pure partner
    const BANKING_ACCOUNTS_ISSUED = 'banking_accounts_issued';

    // feature for enabling refund arn webhook
    const REFUND_ARN_WEBHOOK = 'refund_arn_webhook';

    const PL_BATCH_UPLOAD_FEATURE = 'pl_batch_upload_feature';

    const PAYPAL_GTM_NOTIFICATION = 'paypal_gtm_notification';

    /**
     * Disables settlement sms notifications
     */
    const SETTLEMENTS_SMS_STOP = 'settlements_sms_stop';

    /*
     * Enable feature to appear/disappear support url at org level
    */
    const SHOW_SUPPORT_URL = 'show_support_url';

    /**
     * Enables custom branding at org level.
     */
    const ORG_CUSTOM_BRANDING = 'org_custom_branding';

    /*
     * Enables admin dashboard session logout at org level
     */
    const LOGOUT_ADMIN_INACTIVITY = 'logout_admin_inactivity';

    /*
     * Enables second factor auth for axis logout at org level
     */
    const ORG_SECOND_FACTOR_AUTH = 'second_factor_auth';

     /* Org level features for featured route access
     */
    const ORG_BANK_ACCOUNT_UPDATE_SS         = 'bank_account_update_ss';
    const ORG_FRESHDESK_CREATE_TICKET        = 'freshdesk_create_ticket';
    const ORG_SUB_MERCHANT_CREATE            = 'sub_merchant_create';

    /*
     * Org level features for featured route access whitelisting
     */
    const WHITE_LABELLED_INVOICES                = 'white_labelled_invoices';
    const WHITE_LABELLED_ROUTE                   = 'white_labelled_route';
    const WHITE_LABELLED_VA                      = 'white_labelled_va';
    const WHITE_LABELLED_QRCODES                 = 'white_labelled_qrcodes';
    const WHITE_LABELLED_PL                      = 'white_labelled_pl';
    const WHITE_LABELLED_SUBS                    = 'white_labelled_subs';

    /*
     * Org level feature to hide activation form by deafult
     */
    const ORG_HIDE_ACTIVATION_FORM  = 'hide_activation_form';


    /**
     * Control enablement of Admin Dashboard Reports for Orgs
     */
    const ORG_ADMIN_REPORT_ENABLE      = 'admin_reports_enable';

    /**
     * Marks the seller eligible for automated loc as per https://jira.corp.razorpay.com/browse/CAP-519
     */
    const AUTOMATED_LOC_ELIGIBLE = 'automated_loc_eligible';

    const TRANSFER_SETTLED_WEBHOOK          = 'transfer_settled_webhook';

    const LA_BANK_ACCOUNT_UPDATE            = 'la_bank_account_update';

    const REWARD_MERCHANT_DASHBOARD        = 'reward_merchant_dashboard';

    const DIRECT_TRANSFER                   = 'direct_transfer';

    const DASHBOARD_INTERNAL               = 'DASHBOARD_INTERNAL';

    const ROUTE_LA_PENNY_TESTING           = 'route_la_penny_testing';

    /**
     * Enables Offers on Subscription
     */
    const OFFER_ON_SUBSCRIPTION = 'offer_on_subscription';

    /**
     * Payout Service enables for merchant
     */
    const PAYOUT_SERVICE_ENABLED           = 'payout_service_enabled';

    /**
     * Internal Contact Payout Service enables for merchant
     */
    const INTERNAL_CONTACT_VIA_PS         = 'internal_contact_via_ps';

    /**
     * Skips Risk check for merchants
     */
    const APPS_EXTEMPT_RISK_CHECK ='apps_exempt_risk_check';

    /*
     * Cred merchant consent for sharing contact details
     */
    const CRED_MERCHANT_CONSENT = 'cred_merchant_consent';

    /**
     * Skips customer flagging link in email and hosted page
     */
    const APPS_EXEMPT_CUSTOMER_FLAGGING ='apps_exempt_customer_flagging';

    // Used to disable tpv flow for merchants for business banking (Razorpay X) fund loading.
    const DISABLE_TPV_FLOW = 'disable_tpv_flow';

    // used for axis org
    const AXIS_ORG_FEATURE = 'axis_org';
    const SUB_VIRTUAL_ACCOUNT = 'sub_virtual_account';

    // Used to enable alternate failure reason in payout for status code.
    const ALTERNATE_PAYOUT_FR = 'alternate_payout_fr';

    // Used to enable fund account of type wallet account (provider: amazonpay) for merchants for business banking (Razorpay X).
    const DISABLE_X_AMAZONPAY = 'disable_x_amazonpay';

    /**
     * Low priority queue feature in payout for ipl.
     */
    const PAYOUT_PROCESS_ASYNC_LP = 'payout_process_async_lp';

    /**
     * queue feature in payout for create_request_submitted.
     */
    const PAYOUT_PROCESS_ASYNC = 'payout_process_async';

    /**
     * Used to control if a merchant can create UPI payouts on RBL CA.
     */
    const RBL_CA_UPI = 'rbl_ca_upi';

    /**
     * Used to identify if a payouts batch request is an MFN request
     */
    const MFN = 'mfn';

    /**
     * Used to enable covid 19 related donation on checkout
     */
    const COVID_19_RELIEF = 'covid_19_relief';

    /**
     * Used to decide if registered name to be sent in payouts response
     */
    const BENE_NAME_IN_PAYOUT = 'bene_name_in_payout';

    /**
     * Used to block customer prefill details on PL hosted page
     */
    const PL_BLOCK_CUSTOMER_PREFILL = 'pl_block_customer_prefill';

    /**
     * allow_va_to_va_payouts for payouts va to va for certain merchant
     */
    const ALLOW_VA_TO_VA_PAYOUTS        = 'allow_va_to_va_payouts';

    /**
     * Used to manage on_hold feature in case of bene/NPCI downtime for payout requests
     * If this flag is enabled the payout will be queued for a certian sla or until the uptime is detected
     */
    const PAYOUTS_ON_HOLD = 'payouts_on_hold';

    /**
     * Used to manage on_hold feature(Holding payout for a certain sla) in case of bene/NPCI downtime for payout requests
     * If this flag is enabled the payout will be not be hold for the merchant even if bene or NPCI or partner_bank is down
     */
    const SKIP_HOLD_PAYOUTS = 'skip_hold_payouts';

    /**
     * Merchant feature used to control visibility of dcc markup on frontend
     * Even if the feature 'PAYOUTS_ON_HOLD' is set for merchants, 10% of the payouts may be sent as test transactions
     * to detect uptime. If this flag is enabled ( solely for DMT merchants), the payout will never be sent as test
     * transactions and will always be queued for a certain sla or until the uptime is detected.
     */
    const SKIP_TEST_TXN_FOR_DMT = 'skip_test_txn_for_dmt';

    /**
     * Merchant feature used to not allow deduplication of fund accounts by changing the way
     * unique hashcode is computed. The unique hash computed now excludes beneficiary_name and
     *
     */
    const SKIP_CONTACT_DEDUP_FA_BA = 'skip_contact_dedup_fa_ba';

    /**
    * Merchant feature used to control visibility of dcc markup on frontend
     */
    const PAYMENT_SHOW_DCC_MARKUP   =   'payment_show_dcc_markup';

    /**
     * Merchant feature used to control visibility of MOR details on frontend
     */
    const SHOW_MOR_TNC   =   'show_mor_tnc';

    /**
     * Feature for HDFC VAS org. To enable customerfeeBearer surcharge cards gateway.
     */
    const ORG_HDFC_VAS_CARDS_SURCHARGE  =   'hdfc_vas_cards_surcharge';

    /**
     * Merchant level feature flag for HDFC 2.0 Checkout
     */
    const HDFC_CHECKOUT_2   = 'hdfc_checkout_2';

    /**
     * Org level feature flag for sub merchant activation with MCC Pending status.
     */
    const ORG_SUB_MERCHANT_MCC_PENDING  =   'sub_merchant_mcc_pending';

    /**
     * Org level feature flag for blocking account updation if org has this feature enabled.
     */
    const ORG_BLOCK_ACCOUNT_UPDATE  =   'block_account_update';

    /**
     * Show old error desc to merchant based on feature value
     */
    const SHOW_OLD_ERROR_DESC = 'show_old_error_desc';

    /**
     * Feature flag to make the FTS call in async mode
     */
    const PAYOUT_ASYNC_FTS_TRANSFER = 'payout_async_fts_transfer';

    /**
     * Feature flag to allow narration to be null in payouts instead of
     * default billing label
     */
    const NULL_NARRATION_ALLOWED = 'null_narration_allowed';

    /**
     * Feature flag for partner to skip onboarding notifications sent to submerchant
     */
    const SKIP_SUBM_ONBOARDING_COMM = 'skip_subm_onboarding_comm';

    /**
     * Feature flag to skip sending customer_id to the checkout
     */
    const SKIP_CUSTOMER_ID_CHECKOUT = 'skip_customer_id_checkout';

    /**
     * Dispute presentment needs to be enabled on the merchant via private auth
     */
    const DISPUTE_PRESENTMENT = 'dispute_presentment';

    const ORG_ENABLE_REFUNDS = 'enable_refunds';

    const MERCHANT_ENABLE_REFUND = 'merchant_enable_refunds';

    /**
     * Dispute with Deduct At Onset can not be created for EXCLUDE_DEDUCT_DISPUTE enabled
     */
    const EXCLUDE_DEDUCT_DISPUTE = 'exclude_deduct_dispute';

    /**
     * One click checkout
     */
    const ONE_CLICK_CHECKOUT = 'one_click_checkout';

    /**
     * One click dual checkout
     */
    const ONE_CLICK_DUAL_CHECKOUT = 'one_cc_dual_checkout';

    const ONE_CC_MERCHANT_DASHBOARD = 'one_cc_merchant_dashboard';

    const ONE_CC_GA_ANALYTICS = 'one_cc_ga_analytics';

    const ONE_CC_FB_ANALYTICS = 'one_cc_fb_analytics';

    const ORG_EMAIL_UPDATE_2FA_ENABLED = 'email_update_2fa_enabled';

    const ORG_TPV_DISABLE    = 'axis_tpv';

    const AXIS_TPV_ENABLE   = 'axis_tpv_enable';

    const ROUTE_KEY_MERCHANTS_QUEUE = 'route_key_merchants_queue';

    // Experiment to move transfer processing for these 2 merchants to dedicated queues.
    const CAPITAL_FLOAT_ROUTE_MERCHANT  = 'cf_route_merchant'; // Cannot be longer than 25 characters.
    const SLICE_ROUTE_MERCHANT          = 'sl_route_merchant';

    const FEATURE_BBPS = 'feature_bbps';

    /**
     * Feature flag to collect address from customers
     */
    const ADDRESS_REQUIRED = 'address_required';

    /**
     * Feature flag to collect address, first name and last name from customers
     */
    const ADDRESS_NAME_REQUIRED = 'address_name_required';

    /**
     * Merchant feature used to disable the Sift JS integration
     */
    const ENABLE_SIFT_JS   =   'enable_sift_js';
    /**
     * Merchant feature used to disable the Sift JS integration
     */
    const DISABLE_SIFT_JS   =   'disable_sift_js';

    // Merchant feature used to rollout the Cybersource JS Integration and Integration via Cybersource DM API, Fraud Marking API
    const SHIELD_CYBERSOURCE_ROLLOUT    =   'shield_cbs_rollout';

    /**
     * Merchant feature used to disable paypal as a backup in case of Intl card failure
     */
    const DISABLE_PAYPAL_AS_BACKUP   =   'disable_paypal_as_backup';

    /**
     * All Ledger Features for RX release
     */
    const LEDGER_JOURNAL_WRITES = 'ledger_journal_writes';
    const LEDGER_JOURNAL_READS  = 'ledger_journal_reads';
    const LEDGER_REVERSE_SHADOW = 'ledger_reverse_shadow';

    /**
     * All Ledger Features for RX Direct accounting release
     */
    const DA_LEDGER_JOURNAL_WRITES = 'da_ledger_journal_writes';
    const DA_LEDGER_REVERSE_SHADOW = 'da_ledger_reverse_shadow';

    /**
     * Ledger Features for PG release
     */
    const PG_LEDGER_JOURNAL_WRITES = 'pg_ledger_journal_writes';

    /**
     * If contact number updated via admin dashboard, mark it verified by default if feature present on org
     */
    const ORG_CONTACT_VERIFY_DEFAULT = 'contact_verify_default';

    const ORG_AXIS_PAYPAL = 'axis_paypal';

    const AXIS_PAYPAL_ENABLE = 'axis_paypal_enable';

    // To enable workflow payouts creation via payouts service
    const WORKFLOW_VIA_PAYOUTS_MS = 'workflow_via_payouts_ms';
    const ORG_AXIS_WHATSAPP = 'axis_whatsapp';

    const AXIS_WHATSAPP_ENABLE = 'axis_whatsapp_enable';
    /**
     * Feature flag to redirect user to the Gateway
     */
    const REDIRECT_TO_EARLYSALARY = 'redirect_to_earlysalary';
    const REDIRECT_TO_ZESTMONEY   = 'redirect_to_zestmoney';

    /**
     * Feature flag to send Compliance changes in a request
     */
    const SEND_DCC_COMPLIANCE = 'send_dcc_compliance';

    /**
     * Feature flag to give access to 'bulk payout approval using file' from admin dashboard
     * 'rx_bulk_approvals' is there from razorx dashboard
     */
    const API_BULK_APPROVALS = 'api_bulk_approvals';

    /**
     * Feature flag to enable network tokenization apis
     */
    const NETWORK_TOKENIZATION = 'network_tokenization';

    /**
     * Feature flag to enable network tokenization apis in live mode, temporaray feature
     */
    const NETWORK_TOKENIZATION_LIVE = 'network_tokenization_live';

     /**
      * Feature flag to allow network tokens in response
     */
    const ALLOW_NETWORK_TOKENS = 'allow_network_tokens';

    /**
     * Feature flag to allow creation of Payment Links for missed orders via Payment Gateway (via Orders API)
     */
    const MISSED_ORDERS_PLINK = 'missed_orders_plink';

    /**
     * Feature flag to onboard merchants on network tokenization in live mode
     */
    const ONBOARD_TOKENIZATION = 'onboard_tokenization';

    /**
     * Feature flag to onboard merchants on visa network tokenization in live mode
     */
    const ONBOARD_TOKENIZATION_VISA = 'onboard_tokenization_visa';

    /**
     * Feature flag to onboard merchants on mastercard network tokenization in live mode
     */
    const ONBOARD_TOKENIZATION_MASTERCARD = 'onboard_tokenization_mc';

    /**
     * Feature flag to onboard merchants on rupay network tokenization in live mode
     */
    const ONBOARD_TOKENIZATION_RUPAY = 'onboard_tokenization_rpy';

    /**
     * Feature flag to onboard merchants on diners network tokenization in live mode
     */
    const ONBOARD_TOKENIZATION_DINERS = 'onboard_tokenization_diners';

    /**
     * Feature flag to onboard merchants on async tokenisation
     */
    const ASYNC_TOKENISATION = 'async_tokenisation';

    /**
     * Feature flag to let Razorpay collect consent for tokenising cards in the payment flow through intermediate consent page
     * This will be used for custom checkout merchants
     * By default Razorpay collects consent
     * Can use this feature flag to disable consent collection by Razorpay
     */
    const DISABLE_COLLECT_CONSENT = 'disable_collect_consent';

    /**
     * Flag to enable the new composite payout flow meant for high tps merchants.
     * Initially implemented specifically for whatsapp.
     */
    const HIGH_TPS_COMPOSITE_PAYOUT = 'high_tps_composite_payout';

    /**
     * Used to route whatsapp merchant dashboard and admin dashboard requests to the new infra and new db.
     * If this flag is enabled the requests for this merchant will go to the new infra
     * This is just for 2 weeks campaign specifically for whatsapp
     */
    const MERCHANT_ROUTE_WA_INFRA = 'merchant_route_wa_infra';

    // Feature flag to enable/disable mobile number uniqueness check on contact
    // details page during pre-signup flow
    const UNIQUE_MOBILE_ON_PRESIGNUP    = 'unique_mobile_on_presignup';

    const PAYOUT_ASYNC_INGRESS = 'payout_async_ingress';
    /**
     * One click checkout
     */
    const ONE_CC_MANDATORY_LOGIN = 'one_cc_mandatory_login';

     /**
     * Currently to support the RazorpayX slack app use case this feature is added.
     * It is used to create exception for private auth request and makes some public setters behave like proxy auth.
     * This feature enables accessing payouts.fund_accounts, payouts.workflow_history via private auth,
     * which are ususlly not accessible via private auth.
     */
    const PUBLIC_SETTERS_VIA_OAUTH = 'public_setters_via_oauth';

    const ONE_CC_COUPONS = 'one_cc_coupons';

    /**
     * Flags to send notifications to beneficiary  when payout is processed
     */
    const BENE_EMAIL_NOTIFICATION = 'bene_email_notification';
    const BENE_SMS_NOTIFICATION   = 'bene_sms_notification';

    /**
     * Flag to enable the new granular downtimes apis & webhooks.
     * To send type and flow keys in instrument along with instrument_schema if applicable.
     */
    const ENABLE_GRANULAR_DOWNTIMES = 'enable_granular_downtimes';

    const ORG_ANNOUNCEMENT_TAB_DISABLE = 'disable_announcements';

    // Show gateway errors in merchant dashboard
    const EXPOSE_GATEWAY_ERRORS = 'expose_gateway_errors';

    const ENABLE_IFSC_VALIDATION       = 'enable_ifsc_validation';

    /**
     * Feature to control affordability widget on merchant dashboard
     */
    const AFFORDABILITY_WIDGET = 'affordability_widget';
    const EDIT_SINGLE_VA_EXPIRY = 'edit_single_va_expiry';

    const ACCEPT_LOWER_AMOUNT = 'accept_lower_amount';

    /**
     * Feature flag to allow the transition from older flow to newer flow
     * where standalone payout to cards is not allowed
     */
    const ALLOW_NON_SAVED_CARDS   = 'allow_non_saved_cards';

    /**
     * Feature flag when enabled removes name field from composite payout response
     * and replaces card.name with contact name, since card name is not allowed to be
     * stored
     */
    const ALLOW_CARD_NAME_CHANGES   = 'allow_card_name_changes';

    /**
     * Feature flag to show entire error description for each row in error csv file generated in case
     * of bulk validation during batch payouts
     */
    const ALLOW_COMPLETE_ERROR_DESC = 'allow_complete_error_desc';

    /**
     * Feature flag to add virtual account expiry
     */
    const SET_VA_DEFAULT_EXPIRY = 'set_va_default_expiry';


    /**
     * Feature flag to deactivate VA when checks fail
     */
    const FAIL_VA_ON_VALIDATION = "fail_va_on_validation";


    // This feature will be used to control the rollout of authorization via authz enforcer
    const AUTHORIZE_VIA_AUTHZ = 'authorize_via_authz';

    // This feature will be used to control the notification emails for oauth applications
    const SKIP_OAUTH_NOTIFICATION = 'skip_oauth_notification';

    /**
     * This feature flag increase the limit of payout amount
     */
    const INCREASE_PAYOUT_LIMIT = 'increase_payout_limit';

    /**
     * Disable default email receipt feature at org level
     */
    const ORG_DISABLE_DEF_EMAIL_RECEIPT = 'disable_def_email_receipt';

     /** Feature flag to skip email notifications to merchants on processed and reversed payouts
     * @see shouldNotifyTxnViaEmail function in Payouts\Enity.php
     */
    const SKIP_PAYOUT_EMAIL = 'skip_payout_email';

    const OFFLINE_PAYMENT_ON_CHECKOUT = 'offline_checkout';

    // This feature is used to send public_order_id in err instead of order_id.
    const ORDER_RECEIPT_UNIQUE_ERR = 'order_receipt_unique_err';

    /**
     * card mandate for recurring card payment for billdesk_sihub
     */
    const RECURRING_CARD_MANDATE_BILLDESK_SIHUB = 'allow_billdesk_sihub';

    /**
     * If applied on partner merchant then all its sub merchants will have QR image content visible
     */
    const SUBM_QR_IMAGE_CONTENT    = 'subm_qr_image_content';

    const RAZORPAYX_FLOWS_VIA_OAUTH = 'razorpayx_flows_via_oauth';

    /**
     * Feature flag to allow only 3ds enabled international transactions
     */
    const ACCEPT_ONLY_3DS_PAYMENTS = 'accept_only_3ds_payments';

    /**
     * Feature flag encompasses changes for optimisation of payouts summary api
     */
    const OPTIMISE_SUMMARY_API = 'optimise_summary_api';

    /**
     * Feature flag to enable pricing automation at sub merchant bulk upload
     */
    const SUB_MERCHANT_PRICING_AUTOMATION = 'subm_pricing_automation';

    /**
     * Feature flag disables a merchant from creating linked accounts with existing emails
     */
    const DISALLOW_LINKED_ACCOUNT_WITH_DUPLICATE_EMAILS = 'no_la_for_existing_emails';

    /**
     * Feature flag to block merchants on workflow service.
     * If the feature is enabled, workflow will be processed via API, else via workflow service
     */
    const BLOCKLIST_FOR_WORKFLOW_SERVICE = 'blocklist_for_wf_service';

    /**
     * Feature flag to allow merchants to use single tid.
     */
    const HDFC_SINGLE_TID = 'hdfc_single_tid';

    const MESSAGE                        = 'message';
    const INPUT                          = 'input';
    const MERCHANT_ONBOARDED             = 'merchant onboarded';
    const STATUS_CODE                    = 'status_code';
    const BODY                           = 'body';
    const BAD_REQUEST_MERCHANT_ID_ABSENT = "BAD_REQUEST_MERCHANT_ID_ABSENT";

    const ORG_POOL_ACCOUNT_SETTLEMENT = 'org_pool_settlement';

    public static $recurringFeatures = [
        self::CHARGE_AT_WILL,
        self::SUBSCRIPTIONS,
        self::RECURRING_AUTO,
    ];

    const CHECKOUT_FEATURES = [
        self::GOOGLE_PAY,
        self::CUSTOMER_ADDRESS,
        self::IRCTC_METHODS,
        self::GOOGLE_PAY_OMNICHANNEL,
        self::PHONEPE_INTENT,
        self::SAVE_VPA,
        self::REDIRECT_TO_ZESTMONEY,
        self::DISABLE_NATIVE_CURRENCY,
        self::UPI_OTM,
        self::CHECKOUT_DISABLE_I18N,
        self::CHECKOUT_DISABLE_CARDSCAN,
        self::PAYPAL_CC,
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_MERCHANT_DASHBOARD,
        self::SHOW_MOR_TNC,
        self::CRED_MERCHANT_CONSENT,
        self::TPV,
        self::DIRECT_SETTLEMENT,
        self::RAAS,
        self::DISABLE_SIFT_JS,
        self::ONE_CC_COUPONS,
        self::ONE_CC_MANDATORY_LOGIN,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::SHIELD_CYBERSOURCE_ROLLOUT,
        self::HDFC_CHECKOUT_2,
        self::RECURRING_CARD_MANDATE_BILLDESK_SIHUB,
        self::ONE_CLICK_DUAL_CHECKOUT
    ];

    const ONE_CC_FEATURES = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_MERCHANT_DASHBOARD,
        self::ONE_CC_COUPONS,
        self::ONE_CC_MANDATORY_LOGIN,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CLICK_DUAL_CHECKOUT
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
        self::PAYOUTS_BATCH                   => true,
        self::OPENWALLET                      => true,
        self::MARKETPLACE                     => true,
        self::EMAIL_OPTIONAL                  => true,
        self::CONTACT_OPTIONAL                => true,
        self::SUBSCRIPTIONS                   => true,
        self::ZOHO                            => true,
        self::EXPOSE_DOWNTIMES                => true,
        self::PAYMENT_FAILURE_EMAIL           => true,
        self::VIRTUAL_ACCOUNTS                => true,
        self::QR_IMAGE_CONTENT                => true,
        self::QR_IMAGE_PARTNER_NAME           => true,
        self::VIRTUAL_ACCOUNTS_BANKING        => true,
        self::BANK_TRANSFER_ON_CHECKOUT       => true,
        self::CHECKOUT_VA_WITH_CUSTOMER       => true,
        self::INVOICE_PARTIAL_PAYMENTS        => true,
        self::HIDE_DOWNTIMES                  => true,
        self::OLD_CREDITS_FLOW                => true,
        self::CHARGE_AT_WILL                  => true,
        self::EMI_MERCHANT_SUBVENTION         => true,
        self::FSS_RISK_UDF                    => true,
        self::RULE_FILTER                     => true,
        self::TPV                             => true,
        self::IRCTC_REPORT                    => true,
        self::DISABLE_MAESTRO                 => true,
        self::DISABLE_RUPAY                   => true,
        self::BLOCK_INTERNATIONAL_RECURRING   => false,
        self::BHARAT_QR                       => true,
        self::BHARAT_QR_V2                    => true,
        self::MOBIKWIK_OFFERS                 => true,
        self::SKIP_HOLD_FUNDS_ON_PAYOUT       => true,
        self::REPORT_V2                       => true,
        self::CORPORATE_BANKS                 => true,
        self::ORDER_ID_MANDATORY              => true,
        self::ORDER_RECEIPT_UNIQUE            => true,
        self::MAGIC                           => true,
        self::QR_CODES                        => true,
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
        self::NON_TPV_BT_REFUND               => true,
        self::CARD_TRANSFER_REFUND            => true,
        self::DISABLE_INSTANT_REFUNDS         => true,
        self::LOG_RESPONSE                    => true,
        self::EXCESS_ORDER_AMOUNT             => true,
        self::DISABLE_AMOUNT_CHECK            => true,
        self::SUBSCRIPTION_V2                 => true,
        self::SUBSCRIPTION_AUTH_V2            => true,
        self::EXPOSE_ARN_REFUND               => true,
        self::OFFERS                          => true,
        self::OTP_AUTH_DEFAULT                => true,
        self::EDIT_METHODS                    => true,
        self::CAPTURE_QUEUE                   => true,
        self::ASYNC_CAPTURE                   => true,
        self::TRANSACTION_V2                  => true,
        self::ES_ON_DEMAND                    => true,
        self::ES_ON_DEMAND_RESTRICTED         => true,
        self::BLOCK_ES_ON_DEMAND              => true,
        self::UPDATED_IMPS_ONDEMAND           => true,
        self::ES_AUTOMATIC                    => true,
        self::ES_AUTOMATIC_RESTRICTED         => true,
        self::HEADLESS_DISABLE                => true,
        self::BEPG_DISABLE                    => true,
        self::FIRST_DATA_S2S_FLOW             => true,
        self::BIN_ISSUER_VALIDATOR            => true,
        self::OFFER_PRIVATE_AUTH              => true,
        self::GOOGLE_PAY                      => true,
        self::EMANDATE_MRN                    => true,
        self::DIWALI_PROMOTIONAL_PLAN         => true,
        self::CARD_FINGERPRINTS               => true,
        self::CUSTOMER_ADDRESS                => true,
        self::IRCTC_METHODS                   => true,
        self::SKIP_CVV                        => true,
        self::BLOCK_SETTLEMENTS               => true,
        self::SKIP_INTERNATIONAL_AUTH         => true,
        self::INVOICE_NO_RECEIPT_UNIQUE       => true,
        self::PAYMENT_PAGES_NO_CAPTURE        => true,
        self::ES_AUTOMATIC_THREE_PM           => true,
        self::IIN_LISTING                     => true,
        self::BIN_API                         => true,
        self::CALLBACK_URL_VALIDATION         => true,
        self::PL_FIRST_MIN_AMOUNT             => true,
        self::PL_HIDE_ISSUED_TO               => true,
        self::REPORTING_GENRERIC_NOTES        => true,
        self::S2S_OTP_JSON                    => true,
        self::S2S_JSON                        => true,
        self::DISABLE_REFUNDS                 => true,
        self::DISABLE_CARD_REFUNDS            => true,
        self::DISABLE_AUTO_REFUNDS            => true,
        self::INVOICE_RECEIPT_MANDATORY       => true,
        self::INVOICE_NO_EXPIRY_EMAIL         => true,
        self::INVOICE_EXPIRE_BY_REQD          => true,
        self::SELF_KYC_DISABLED               => true,
        self::PAYMENT_MAILS_DISABLED          => true,
        self::DISPUTE_MAILS_DISABLED          => true,
        self::ALLOW_REVERSALS_FROM_LA         => true,
        self::BLOCK_PL_PAY_POST_EXPIRY        => true,
        self::ADHOC_SETTLEMENT                => true,
        self::NEW_SETTLEMENT_SERVICE          => true,
        self::SUB_TERMINAL_OPTIMIZE           => true,
        self::SHOW_REFUND_PUBLIC_STATUS       => true,
        self::REFUND_PENDING_STATUS           => true,
        self::OVERRIDE_SUB_CONFIG             => true,
        self::DOWNTIME_ROUTING                => true,
        self::PAYOUT_TO_CARDS                 => true,
        self::PAYMENT_ONHOLD                  => true,
        self::X_PRO_INVITE                    => true,
        self::X_PAYOUT_LINKS_MS               => true,
        self::GOOGLE_PAY_OMNICHANNEL          => true,
        self::TERMINAL_ONBOARDING             => true,
        self::SHOW_SUPPORT_URL                => true,
        self::TEST_MODE_SETTLEMENT            => true,
        self::VIJAYA_MERCHANT                 => true,
        self::VAS_MERCHANT                    => true,
        self::VA_EDIT_BULK                    => true,
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
        self::RETAIN_SUB_MERCHANT_NAME        => true,
        self::NO_COMM_WITH_SUBMERCHANTS       => true,
        self::PARTNER_SUB_KYC_ACCESS          => true,
        self::SUBMERCHANT_ONBOARDING          => true,
        self::SUBMERCHANT_ONBOARDING_V2       => true,
        self::EXPOSE_FA_VALIDATION_UTR        => true,
        self::PARTNER_ACTIVATE_MERCHANT       => true,
        self::GOOGLE_PAY_CARDS                => true,
        self::GPAY                            => true,
        self::FORCE_GREYLIST_INTERNAT         => true,
        self::SKIP_WEBSITE_INTERNAT           => true,
        self::SHOW_CREDIT_SCORE               => true,
        self::AGGREGATOR_OAUTH_CLIENT         => true,
        self::AGGREGATE_SETTLEMENT            => true,
        self::BLOCK_OFFER_CREATION            => true,
        self::ENACH_INTERMEDIATE              => true,
        self::SAVE_VPA                        => true,
        self::REDIRECT_TO_ZESTMONEY           => true,
        self::GENERATE_PARTNER_INVOICE        => true,
        self::AUTOMATED_COMM_PAYOUT           => true,
        self::OFFLINE_PAYMENTS                => true,
        self::SKIP_HITACHI_AUTO_ONBOARD       => true,
        self::SKIP_FULCRUM_AUTO_ONBOARD       => true,
        self::SKIP_WORKFLOWS_FOR_API          => true,
        self::SKIP_WF_FOR_PAYROLL             => true,
        self::SKIP_WF_AT_PAYOUTS              => true,
        self::NEW_BANKING_ERROR               => true,
        self::DISPLAY_LA_PARENT_PAYMENT_ID    => true,
        self::REDIRECTION_ONHOLD              => true,
        self::DISABLE_NATIVE_CURRENCY         => true,
        self::PAYMENT_CREATED_WEBHOOK         => true,
        self::PAYMENT_CONFIG_ENABLED          => false,
        self::CYBERSOURCE_VAS                 => true,
        self::CHECKOUT_DISABLE_RETRY          => true,
        self::CHECKOUT_DISABLE_I18N           => true,
        self::COVID                           => true,
        self::SR_SENSITIVE                    => true,
        self::RAAS                            => true,
        self::OPTIMIZER_SMART_ROUTER          => true,
        self::SKIP_NOTES_MERGING              => true,
        self::ENABLE_SINGLE_RECON             => true,
        self::PAYMENTLINKS_V2                 => true,
        self::RECURRING_DEBIT_UMRN            => true,
        self::NACH_FORM_DIRECT_DOWNLOAD       => true,
        self::CARD_MANDATE_SKIP_PAGE          => true,
        self::PAYMENTLINKS_COMPATIBILITY_V2   => true,
        self::SEND_PAYMENT_CONFIG_ID          => false,
        self::PAYMENT_STATUS_AGGREGATE        => true,
        self::JSON_V2                         => true,
        self::SEND_PAYMENT_LATE_AUTH          => true,
        self::OVERRIDE_HITACHI_BLACKLIST      => true,
        self::UPI_OTM                         => true,
        self::CHARGE_ACCOUNT                  => true,
        self::TRANSACTION_ON_HOLD             => true,
        self::REDIRECT_TO_EARLYSALARY         => true,
        self::LOAN                            => true,
        self::LOC                             => true,
        self::LOS                             => true,
        self::CAPITAL_CARDS_ELIGIBLE          => true,
        self::CARDS_TRANSACTION_LIMIT_1       => true,
        self::CARDS_TRANSACTION_LIMIT_2       => true,
        self::USE_SETTLEMENT_ONDEMAND         => true,
        self::SHOW_ON_DEMAND_DEDUCTION        => true,
        self::ALLOW_NETBANKING_FETCH          => true,
        self::ALLOW_NON_FLDG_LOANS            => true,
        self::ALLOW_ES_AMAZON                 => true,
        self::IVR_DISABLE                     => true,
        self::WITHDRAW_LOC                    => true,
        self::WITHDRAWAL_ES_AMAZON            => true,
        self::LOC_ESIGN                       => true,
        self::LOC_FIRST_WITHDRAWAL            => true,
        self::CAPITAL_CARDS                   => true,
        self::DISABLE_ONDEMAND_FOR_CARD       => true,
        self::DISABLE_ONDEMAND_FOR_LOAN       => true,
        self::DISABLE_ONDEMAND_FOR_LOC        => true,
        self::DISABLE_CARDS_POST_DPD          => true,
        self::DISABLE_LOANS_POST_DPD          => true,
        self::DISABLE_LOC_POST_DPD            => true,
        self::DISABLE_AMAZON_IS_POST_DPD      => true,
        self::LOC_STAGE_1                     => true,
        self::LOC_STAGE_2                     => true,
        self::NPS_SURVEY_PAYMENT_PAGES        => true,
        self::NPS_SURVEY_PAYMENT_LINKS        => true,
        self::NPS_SURVEY_PAYMENT_GATEWAY_1M   => true,
        self::NPS_SURVEY_PAYMENT_GATEWAY_6M   => true,
        self::NPS_SURVEY_PAYMENT_GATEWAY_12M  => true,
        self::NPS_SURVEY_OTHER_PRODUCTS       => true,
        self::RX_BLOCK_REPORT_DOWNLOAD        => true,
        self::BLOCK_ONBOARDING_SMS            => true,
        self::OTP_SUBMIT_RESPONSE             => true,
        self::ESIGN                           => true,
        self::NPCI_SPID                       => true,
        self::ROUTE_CODE_SUPPORT              => true,
        self::AUTH_SPLIT                      => true,
        self::ALLOW_FORCE_TERMINAL_ID         => true,
        self::VISA_SAFE_CLICK                 => true,
        self::RECURRING_AUTO                  => true,
        self::CHECKOUT_DISABLE_CARDSCAN       => true,
        self::RX_SHOW_PAYOUT_SOURCE           => true,
        self::REFUND_AGED_PAYMENTS            => true,
        self::BANKING_ACCOUNTS_ISSUED         => true,
        self::REFUND_ARN_WEBHOOK              => true,
        self::PL_BATCH_UPLOAD_FEATURE         => true,
        self::SETTLEMENTS_SMS_STOP            => true,
        self::ORG_CUSTOM_BRANDING             => true,
        self::EXPOSE_EXTRA_ATTRIBUTES         => true,
        self::SHOW_LATE_AUTH_ATTRIBUTES       => true,
        self::SHOW_REFND_LATEAUTH_PARAM       => true,
        self::DISABLE_FREE_CREDIT_UNREG       => true,
        self::DISABLE_FREE_CREDIT_REG         => true,
        self::LOGOUT_ADMIN_INACTIVITY         => true,
        self::ORG_SECOND_FACTOR_AUTH          => true,
        self::TRANSFER_SETTLED_WEBHOOK        => true,
        self::LA_BANK_ACCOUNT_UPDATE          => true,
        self::P2P_UPI                         => true,
        self::PAYPAL_GTM_NOTIFICATION         => true,
        self::REWARD_MERCHANT_DASHBOARD       => true,
        self::RAZORPAY_WALLET                 => true,
        self::OFFER_ON_SUBSCRIPTION           => true,
        self::AUTOMATED_LOC_ELIGIBLE          => true,
        self::PREVENT_TEST_MODE               => true,
        self::DIRECT_TRANSFER                 => true,
        self::ROUTE_LA_PENNY_TESTING          => true,
        self::APPS_EXTEMPT_RISK_CHECK         => true,
        self::APPS_EXEMPT_CUSTOMER_FLAGGING   => true,
        self::EARLY_MANDATE_PRESENTMENT       => true,
        self::CRED_MERCHANT_CONSENT           => true,
        self::DISABLE_TPV_FLOW                => true,
        self::AXIS_ORG_FEATURE                => true,
        self::SUB_VIRTUAL_ACCOUNT             => true,
        self::CAW_IGNORE_CUSTOMER_CHECK       => true,
        self::CAW_UPI                         => true,
        self::SUBSCRIPTION_UPI                => true,
        self::ALTERNATE_PAYOUT_FR             => true,
        self::DISABLE_X_AMAZONPAY             => true,
        self::CAW_RECURRING_CHARGE_AXIS       => true,
        self::RZP_TRUSTED_BADGE               => true,
        self::CAPITAL_CARDS_COLLECTIONS       => true,
        self::ORG_BANK_ACCOUNT_UPDATE_SS      => true,
        self::ORG_FRESHDESK_CREATE_TICKET     => true,
        self::ORG_SUB_MERCHANT_CREATE         => true,
        self::PAYOUT_PROCESS_ASYNC_LP         => true,
        self::PAYOUT_PROCESS_ASYNC            => true,
        self::FTS_REQUEST_NOTES               => true,
        self::RBL_CA_UPI                      => true,
        self::PAYOUT_SERVICE_ENABLED          => true,
        self::INTERNAL_CONTACT_VIA_PS         => true,
        self::WHITE_LABELLED_INVOICES         => true,
        self::WHITE_LABELLED_ROUTE            => true,
        self::WHITE_LABELLED_VA               => true,
        self::WHITE_LABELLED_QRCODES          => true,
        self::WHITE_LABELLED_PL               => true,
        self::WHITE_LABELLED_SUBS             => true,
        self::COVID_19_RELIEF                 => true,
        self::BENE_NAME_IN_PAYOUT             => true,
        self::PAYOUTS_ON_HOLD                 => true,
        self::SKIP_HOLD_PAYOUTS               => true,
        self::SKIP_TEST_TXN_FOR_DMT           => true,
        self::SKIP_CONTACT_DEDUP_FA_BA        => true,
        self::PL_BLOCK_CUSTOMER_PREFILL       => true,
        self::ALLOW_VA_TO_VA_PAYOUTS          => true,
        self::PAYMENT_SHOW_DCC_MARKUP         => true,
        self::EXPOSE_SETTLED_BY               => true,
        self::PAYPAL_CC                       => true,
        self::ORG_HIDE_ACTIVATION_FORM        => true,
        self::ORG_ADMIN_REPORT_ENABLE         => true,
        self::ORG_HDFC_VAS_CARDS_SURCHARGE    => true,
        self::HDFC_CHECKOUT_2                 => true,
        self::SHOW_OLD_ERROR_DESC             => true,
        self::PAYOUT_ASYNC_FTS_TRANSFER       => true,
        self::NULL_NARRATION_ALLOWED          => true,
        self::SKIP_SUBM_ONBOARDING_COMM       => true,
        self::ORG_CONTACT_VERIFY_DEFAULT      => true,
        self::SKIP_CUSTOMER_ID_CHECKOUT       => true,
        self::DISPUTE_PRESENTMENT             => true,
        self::ORG_ENABLE_REFUNDS              => true,
        self::MERCHANT_ENABLE_REFUND          => true,
        self::ORG_EMAIL_UPDATE_2FA_ENABLED    => true,
        self::ORG_TPV_DISABLE                 => true,
        self::AXIS_TPV_ENABLE                 => true,
        self::ROUTE_KEY_MERCHANTS_QUEUE       => true,
        self::CAPITAL_FLOAT_ROUTE_MERCHANT    => true,
        self::SLICE_ROUTE_MERCHANT            => true,
        self::SHOW_MOR_TNC                    => true,
        self::FEATURE_BBPS                    => true,
        self::ADDRESS_REQUIRED                => true,
        self::LEDGER_JOURNAL_WRITES           => true,
        self::DA_LEDGER_JOURNAL_WRITES        => true,
        self::DA_LEDGER_REVERSE_SHADOW        => true,
        self::PG_LEDGER_JOURNAL_WRITES        => true,
        self::LEDGER_JOURNAL_READS            => true,
        self::LEDGER_REVERSE_SHADOW           => true,
        self::ORG_AXIS_PAYPAL                 => true,
        self::AXIS_PAYPAL_ENABLE              => true,
        self::ORG_AXIS_WHATSAPP               => true,
        self::AXIS_WHATSAPP_ENABLE            => true,
        self::AVS                             => true,
        self::WORKFLOW_VIA_PAYOUTS_MS         => true,
        self::MFN                             => true,
        self::SEND_DCC_COMPLIANCE             => true,
        self::ORG_SUB_MERCHANT_MCC_PENDING    => true,
        self::ORG_BLOCK_ACCOUNT_UPDATE        => true,
        self::SOURCED_BY_WALNUT369            => true,
        self::M2M_REFERRAL                    => true,
        self::ENABLE_SIFT_JS                  => true,
        self::SHIELD_CYBERSOURCE_ROLLOUT      => true,
        self::DISABLE_SIFT_JS                 => true,
        self::API_BULK_APPROVALS              => true,
        self::NETWORK_TOKENIZATION            => true,
        self::CREATE_SOURCE_V2                => true,
        self::NETWORK_TOKENIZATION_LIVE       => true,
        self::HIGH_TPS_COMPOSITE_PAYOUT       => true,
        self::MERCHANT_ROUTE_WA_INFRA         => true,
        self::BENE_EMAIL_NOTIFICATION         => true,
        self::BENE_SMS_NOTIFICATION           => true,
        self::ALLOW_NETWORK_TOKENS            => true,
        self::MISSED_ORDERS_PLINK             => true,
        self::DISABLE_PAYPAL_AS_BACKUP        => true,
        self::PAYOUT_ASYNC_INGRESS            => true,
        self::ONE_CLICK_CHECKOUT              => true,
        self::ONE_CC_MANDATORY_LOGIN          => true,
        self::ONE_CC_MERCHANT_DASHBOARD       => true,
        self::ONE_CC_COUPONS                  => true,
        self::ONE_CC_GA_ANALYTICS             => true,
        self::ONE_CC_FB_ANALYTICS             => true,
        self::ONBOARD_TOKENIZATION            => true,
        self::ONBOARD_TOKENIZATION_VISA       => true,
        self::ONBOARD_TOKENIZATION_MASTERCARD => true,
        self::ONBOARD_TOKENIZATION_RUPAY      => true,
        self::ONBOARD_TOKENIZATION_DINERS     => true,
        self::ASYNC_TOKENISATION              => true,
        self::EXCLUDE_DEDUCT_DISPUTE          => true,
        self::ORG_ANNOUNCEMENT_TAB_DISABLE    => true,
        self::ASYNC_TXN_FILL_DETAILS          => true,
        self::EXPOSE_GATEWAY_ERRORS           => true,
        self::DISABLE_COLLECT_CONSENT         => true,
        self::ENABLE_IFSC_VALIDATION          => true,
        self::PUBLIC_SETTERS_VIA_OAUTH        => true,
        self::AFFORDABILITY_WIDGET            => true,
        self::ENABLE_GRANULAR_DOWNTIMES       => true,
        self::EDIT_SINGLE_VA_EXPIRY           => true,
        self::ADDRESS_NAME_REQUIRED           => true,
        self::ORG_DISABLE_DEF_EMAIL_RECEIPT   => true,
        self::NO_DOC_ONBOARDING               => true,
        self::SUBM_NO_DOC_ONBOARDING          => true,
        self::ALLOW_NON_SAVED_CARDS           => true,
        self::ALLOW_COMPLETE_ERROR_DESC       => true,
        self::ACCEPT_LOWER_AMOUNT             => true,
        self::SET_VA_DEFAULT_EXPIRY           => true,
        self::FAIL_VA_ON_VALIDATION           => true,
        self::AUTHORIZE_VIA_AUTHZ             => true,
        self::SR_SENSITIVE_BUCKET_1           => true,
        self::SR_SENSITIVE_BUCKET_2           => true,
        self::SR_SENSITIVE_BUCKET_3           => true,
        self::SR_SENSITIVE_BUCKET_4           => true,
        self::INCREASE_PAYOUT_LIMIT           => true,
        self::DCC_ON_OTHER_LIBRARY            => true,
        self::ALLOW_CARD_NAME_CHANGES         => true,
        self::SKIP_OAUTH_NOTIFICATION         => true,
        self::SKIP_PAYOUT_EMAIL               => true,
        self::OFFLINE_PAYMENT_ON_CHECKOUT     => true,
        self::ORDER_RECEIPT_UNIQUE_ERR        => true,
        self::SUBM_QR_IMAGE_CONTENT           => true,
        self::RECURRING_CARD_MANDATE_BILLDESK_SIHUB => true,
        self::RAZORPAYX_FLOWS_VIA_OAUTH       => true,
        self::ACCEPT_ONLY_3DS_PAYMENTS        => true,
        self::OPTIMISE_SUMMARY_API            => true,
        self::SUB_MERCHANT_PRICING_AUTOMATION => true,
        self::BLOCKLIST_FOR_WORKFLOW_SERVICE  => true,
        self::ONE_CLICK_DUAL_CHECKOUT         => true,
        self::HDFC_SINGLE_TID                 => true,
        self::ORG_POOL_ACCOUNT_SETTLEMENT     => true,

    ];

    // Entity type constants
    const ACCOUNT                       = 'account';
    const MERCHANT                      = 'merchant';
    const APPLICATION                   = 'application';
    const ORG                           = 'org';

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
    const RZP_TRUSTED_BADGE   = 'rzp_trusted_badge';

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
        self::CRED_MERCHANT_CONSENT  => [
            'feature'       => self::CRED_MERCHANT_CONSENT,
            'display_name'  => 'Cred Merchant Consent',
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
        self::QR_CODES => [
            'feature'       => self::QR_CODES,
            'display_name'  => 'QR codes',
            'documentation' => 'qr-codes',
        ],
        self::BHARAT_QR => [
            'feature'       => self::BHARAT_QR,
            'display_name'  => 'Bharat QR',
            'documentation' => 'qr-codes-bqr',
        ],
        self::BHARAT_QR_V2 => [
            'feature'       => self::BHARAT_QR_V2,
            'display_name'  => 'Bharat QRv2',
            'documentation' => 'qr-codes-bqr-v2',
        ],
        self::QR_IMAGE_CONTENT => [
            'feature'       => self::QR_IMAGE_CONTENT,
            'display_name'  => 'QR Intent link response',
            'documentation' => 'qr-codes',
        ],
        self::QR_IMAGE_PARTNER_NAME => [
            'feature'       => self::QR_IMAGE_PARTNER_NAME,
            'display_name'  => 'QR codes Partner Name',
            'documentation' => 'qr-codes',
        ],
        self::PAYOUT    => [
            'feature'       => self::PAYOUT,
            'display_name'  => 'Payouts',
            'documentation' => 'payouts',
        ],
        self::PAYOUTS_BATCH => [
            'feature'       => self::PAYOUTS_BATCH,
            'display_name'  => 'Payouts Batch API',
            'documentation' => 'payouts batch API'
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
        self::ES_ON_DEMAND_RESTRICTED => [
            'feature'       => self::ES_ON_DEMAND_RESTRICTED,
            'display_name'  => 'Es Ondemand Restricted',
            'documentation' => '',
        ],
        self::BLOCK_ES_ON_DEMAND        => [
            'feature'       => self::BLOCK_ES_ON_DEMAND,
            'display_name'  => 'Block Es Ondemand',
            'documentation' => '',
        ],
        self::UPDATED_IMPS_ONDEMAND => [
            'feature'       => self::UPDATED_IMPS_ONDEMAND,
            'display_name'  => 'Updated IMPS Ondemand',
            'documentation' => '',
        ],
        self::ES_AUTOMATIC              => [
            'feature'       => self::ES_AUTOMATIC,
            'display_name'  => 'Es Automatic',
            'documentation' => '',
        ],
        self::ES_AUTOMATIC_RESTRICTED   => [
            'feature'       => self::ES_AUTOMATIC_RESTRICTED,
            'display_name'  => 'Es Automatic Restricted',
            'documentation' => '',
        ],
        self::LOAN                      => [
            'feature'       => self::LOAN,
            'display_name'  => 'Loan',
            'documentation' => '',
        ],
        self::LOC                      => [
            'feature'       => self::LOC,
            'display_name'  => 'Loc',
            'documentation' => '',
        ],
        self::LOS                      => [
            'feature'       => self::LOS,
            'display_name'  => 'Los',
            'documentation' => '',
        ],
        self::CAPITAL_CARDS_ELIGIBLE   => [
            'feature'       => self::CAPITAL_CARDS_ELIGIBLE,
            'display_name'  => 'Capital cards eligible',
            'documentation' => '',
        ],
        self::CARDS_TRANSACTION_LIMIT_1 => [
            'feature'       => self::CARDS_TRANSACTION_LIMIT_1,
            'display_name'  => 'Cards transaction limit 1',
            'documentation' => 'Alerts in #capital_cards_anomaly_alerts when merchant makes a transaction outside this limit value mapped in capital-cards',
        ],
        self::CARDS_TRANSACTION_LIMIT_2 => [
            'feature'       => self::CARDS_TRANSACTION_LIMIT_2,
            'display_name'  => 'Cards transaction limit 2',
            'documentation' => 'Alerts in #capital_cards_anomaly_alerts when merchant makes a transaction outside this limit value mapped in capital-cards',
        ],
        self::WITHDRAW_LOC              => [
            'feature'       => self::WITHDRAW_LOC,
            'display_name'  => 'Withdraw Loc',
            'documentation' => '',
        ],
        self::WITHDRAWAL_ES_AMAZON              => [
            'feature'       => self::WITHDRAWAL_ES_AMAZON,
            'display_name'  => 'Withdraw Loc for ES Amazon',
            'documentation' => '',
        ],
        self::LOC_ESIGN              => [
            'feature'       => self::LOC_ESIGN,
            'display_name'  => 'LOC lender migration e-sign',
            'documentation' => '',
        ],
        self::LOC_FIRST_WITHDRAWAL              => [
            'feature'       => self::LOC_FIRST_WITHDRAWAL,
            'display_name'  => 'Loc first withdrawal',
            'documentation' => '',
        ],
        self::LOC_STAGE_1               => [
            'feature'       => self::LOC_STAGE_1,
            'display_name'  => 'Line of credit Stage 1',
            'documentation' => '',
        ],
        self::LOC_STAGE_2               => [
            'feature'       => self::LOC_STAGE_2,
            'display_name'  => 'Line of credit Stage 2',
            'documentation' => '',
        ],
        self::CAPITAL_CARDS              => [
            'feature'       => self::CAPITAL_CARDS,
            'display_name'  => 'Capital Cards',
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
        self::X_PAYOUT_LINKS_MS            => [
            'feature'       => self::X_PAYOUT_LINKS_MS,
            'display_name'  => 'Razorpay X - Payout Links Microservice',
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
        self::SKIP_WF_FOR_PAYROLL         => [
            'feature'       => self::SKIP_WF_FOR_PAYROLL,
            'display_name'  => 'Razorpay X - Skip workflows for Payroll requests',
            'documentation' => '',
        ],
        self::SKIP_WF_AT_PAYOUTS             => [
            'feature'       => self::SKIP_WF_AT_PAYOUTS,
            'display_name'  => 'Razorpay X - Skip workflows payout specific',
            'documentation' => '',
        ],
        self::NEW_BANKING_ERROR              => [
            'feature'       => self::NEW_BANKING_ERROR,
            'display_name'  => 'New banking error response is enabled by the merchant.',
            'documentation' => '',
        ],
        self::DISABLE_INSTANT_REFUNDS     => [
            'feature'       => self::DISABLE_INSTANT_REFUNDS,
            'display_name'  => 'Disable Instant Refunds',
            'documentation' => '',
        ],
        self::CARD_TRANSFER_REFUND        => [
            'feature'       => self::CARD_TRANSFER_REFUND,
            'display_name'  => 'Card Transfer Refund',
            'documentation' => '',
        ],
        self::COVID                       => [
            'feature'       => self::COVID,
            'display_name'  => 'Covid-19 relief campaign',
            'documentation' => '',
        ],
        self::PAYMENTLINKS_V2             => [
            'feature'       => self::PAYMENTLINKS_V2,
            'display_name'  => 'Payment links micro service for dashboard',
            'documentation' => '',
        ],
        self::PAYMENTLINKS_COMPATIBILITY_V2  => [
            'feature'       => self::PAYMENTLINKS_COMPATIBILITY_V2,
            'display_name'  => 'Payment links micro service compatibility with old apis',
            'documentation' => '',
        ],
        self::GENERATE_PARTNER_INVOICE => [
            'feature'       => self::GENERATE_PARTNER_INVOICE,
            'display_name'  => 'Generate Partner Invoice',
            'documentation' => 'Commissions',
        ],
        self::USE_SETTLEMENT_ONDEMAND  => [
            'feature'       => self::USE_SETTLEMENT_ONDEMAND,
            'display_name'  => 'Use the settlement/ondemand route for ondemand settlement',
            'documentation' => '',
        ],
        self::SHOW_ON_DEMAND_DEDUCTION => [
            'feature'       => self::SHOW_ON_DEMAND_DEDUCTION,
            'display_name'  => 'Flag to show deductions for instant settlements in dashboard.',
            'documentation' => '',
        ],
        self::ALLOW_NETBANKING_FETCH => [
            'feature'       => self::ALLOW_NETBANKING_FETCH,
            'display_name'  => 'Flag to allow fetching bank statement via netbanking flow',
            'documentation' => '',
        ],
        self::ALLOW_NON_FLDG_LOANS => [
            'feature'       => self::ALLOW_NON_FLDG_LOANS,
            'display_name'  => 'Flag to allow non-fldg loans',
            'documentation' => '',
        ],
        self::ALLOW_ES_AMAZON => [
            'feature'       => self::ALLOW_ES_AMAZON,
            'display_name'  => 'Flag to allow es amazon loans',
            'documentation' => '',
        ],
        self::NPS_SURVEY_OTHER_PRODUCTS  => [
            'feature'       => self::NPS_SURVEY_OTHER_PRODUCTS,
            'display_name'  => 'NPS Survey for Other Products',
            'documentation' => '',
        ],
        self::NPS_SURVEY_PAYMENT_GATEWAY_12M  => [
            'feature'       => self::NPS_SURVEY_PAYMENT_GATEWAY_12M,
            'display_name'  => 'NPS Survey for Payment Gateway 12M',
            'documentation' => '',
        ],
        self::NPS_SURVEY_PAYMENT_GATEWAY_6M  => [
            'feature'       => self::NPS_SURVEY_PAYMENT_GATEWAY_6M,
            'display_name'  => 'NPS Survey for Payment Gateway 6M',
            'documentation' => '',
        ],
        self::NPS_SURVEY_PAYMENT_GATEWAY_1M  => [
            'feature'       => self::NPS_SURVEY_PAYMENT_GATEWAY_1M,
            'display_name'  => 'NPS Survey for Payment Gateway 1M',
            'documentation' => '',
        ],
        self::NPS_SURVEY_PAYMENT_LINKS  => [
            'feature'       => self::NPS_SURVEY_PAYMENT_LINKS,
            'display_name'  => 'NPS Survey for Payment Links',
            'documentation' => '',
        ],
        self::NPS_SURVEY_PAYMENT_PAGES  => [
            'feature'       => self::NPS_SURVEY_PAYMENT_PAGES,
            'display_name'  => 'NPS Survey for Payment Pages',
            'documentation' => '',
        ],
        self::RX_BLOCK_REPORT_DOWNLOAD  => [
            'feature'       => self::RX_BLOCK_REPORT_DOWNLOAD,
            'display_name'  => 'Disable download report actions for View Only Role for RazorpayX dashboard',
            'documentation' => '',
        ],
        self::ROUTE_CODE_SUPPORT => [
            'feature'       => self::ROUTE_CODE_SUPPORT,
            'display_name'  => 'Alias on Route',
            'documentation' => '',
        ],
        self::CONTACT_OPTIONAL => [
            'feature'       => self::CONTACT_OPTIONAL,
            'display_name'  => 'Contact optional in checkout',
            'documentation' => '',
        ],
        self::EMAIL_OPTIONAL => [
            'feature'       => self::EMAIL_OPTIONAL,
            'display_name'  => 'Email optional in checkout',
            'documentation' => '',
        ],
        self::RAAS => [
            'feature'       => self::RAAS,
            'display_name'  => 'Optimizer',
            'documentation' => '',
        ],
        self::OPTIMIZER_SMART_ROUTER => [
            'feature'       => self::OPTIMIZER_SMART_ROUTER,
            'display_name'  => 'Optimizer smart router',
            'documentation' => '',
        ],
        self::SKIP_NOTES_MERGING => [
            'feature'       => self::SKIP_NOTES_MERGING,
            'display_name'  => 'Skip notes merging',
            'documentation' => '',
        ],
        self::ENABLE_SINGLE_RECON => [
            'feature'       => self::ENABLE_SINGLE_RECON,
            'display_name'  => 'Enable Single Recon For Merchant',
            'documentation' => '',
        ],
        self::RX_SHOW_PAYOUT_SOURCE => [
            'feature'       => self::RX_SHOW_PAYOUT_SOURCE,
            'display_name'  => 'Show payout source for RazorpayX dashboard',
            'documentation' => '',
        ],
        self::PL_BATCH_UPLOAD_FEATURE => [
            'feature'       => self::PL_BATCH_UPLOAD_FEATURE,
            'display_name'  => 'Enable batch upload for payment links on dashboard',
            'documentation' => '',
        ],
        self::SETTLEMENTS_SMS_STOP  => [
            'feature'       => self::SETTLEMENTS_SMS_STOP,
            'display_name'  => 'Disable SMS notifications for settlements',
            'documentation' => '',
        ],
        self::ORG_CUSTOM_BRANDING => [
            'feature' => self::ORG_CUSTOM_BRANDING,
            'display_name' => 'Custom Branding feature for an org',
        ],
        self::EXPOSE_EXTRA_ATTRIBUTES => [
            'feature' => self::EXPOSE_EXTRA_ATTRIBUTES,
            'display_name' => 'Exposing some extra entity attributes for an org',
        ],
        self::SHOW_LATE_AUTH_ATTRIBUTES => [
            'feature' => self::SHOW_LATE_AUTH_ATTRIBUTES,
            'display_name' => 'Exposing late auth attributes for the payments',
        ],
        self::SHOW_REFND_LATEAUTH_PARAM => [
            'feature' => self::SHOW_REFND_LATEAUTH_PARAM,
            'display_name' => 'Expose refund type for refunds'
        ],
        self::DISABLE_FREE_CREDIT_UNREG => [
            'feature' => self::DISABLE_FREE_CREDIT_UNREG,
            'display_name' => 'Org level feature for disable amount credits for unregistered merchants',
        ],
        self::DISABLE_FREE_CREDIT_REG => [
            'feature' => self::DISABLE_FREE_CREDIT_REG,
            'display_name' => 'Org level feature for disable amount credits for registered merchants',
        ],
        self::TRANSFER_SETTLED_WEBHOOK => [
            'feature'       => self::TRANSFER_SETTLED_WEBHOOK,
            'display_name'  => 'transfer.settled webhook',
            'documentation' => '',
        ],
        self::LA_BANK_ACCOUNT_UPDATE => [
            'feature'       => self::LA_BANK_ACCOUNT_UPDATE,
            'display_name'  => 'la_bank_account_update',
            'documentation' => '',
        ],
        self::PAYPAL_GTM_NOTIFICATION  => [
            'feature'       => self::PAYPAL_GTM_NOTIFICATION,
            'display_name'  => 'paypal gtm notification',
            'documentation' => '',
        ],
        self::INVOICE_NO_RECEIPT_UNIQUE => [
            'feature'       => self::INVOICE_NO_RECEIPT_UNIQUE,
            'display_name'  => 'Bypass Receipt Unique Check for payment links',
            'documentation' => '',
        ],
        self::BLOCK_PL_PAY_POST_EXPIRY => [
            'feature'       => self::BLOCK_PL_PAY_POST_EXPIRY,
            'display_name'  => 'Block payment past expiry for partially paid links',
            'documentation' => '',
        ],
        self::REWARD_MERCHANT_DASHBOARD => [
            'feature'       => self::REWARD_MERCHANT_DASHBOARD,
            'display_name'  => 'Checkout Reward on merchant dashboard',
            'documentation' => '',
        ],
        self::OFFER_ON_SUBSCRIPTION => [
            'feature'       => self::OFFER_ON_SUBSCRIPTION,
            'display_name'  => 'Enable Offers on Subscription Payment',
            'documentation' => '',
        ],
        self::AUTOMATED_LOC_ELIGIBLE => [
            'feature'       => self::AUTOMATED_LOC_ELIGIBLE,
            'display_name'  => 'Mark the merchant eligible for Automated Withdrawals',
            'documentation' => '',
        ],
        self::PREVENT_TEST_MODE  => [
            'feature'       => self::PREVENT_TEST_MODE,
            'display_name'  => 'Prevent user to switch to test mode from live mode',
            'documentation' => '',
        ],
        self::DIRECT_TRANSFER => [
            'feature'       => self::DIRECT_TRANSFER,
            'display_name'  => 'Required to make direct transfers',
            'documentation' => '',
        ],
        self::ROUTE_LA_PENNY_TESTING => [
            'feature'       => self::ROUTE_LA_PENNY_TESTING,
            'display_name'  => 'For penny testing bank details of Linked Accounts',
            'documentation' => ''
        ],
        self::APPS_EXTEMPT_RISK_CHECK => [
            'feature'       => self::APPS_EXTEMPT_RISK_CHECK,
            'display_name'  => 'Exempts merchant from risk check for Apps products',
            'documentation' => '',
        ],
        self::APPS_EXEMPT_CUSTOMER_FLAGGING => [
            'feature'       => self::APPS_EXEMPT_CUSTOMER_FLAGGING,
            'display_name'  => 'Exempts merchant from customer flagging link in email and hosted pages',
            'documentation' => '',
        ],
        self::CAW_UPI => [
            'feature'       => self::CAW_UPI,
            'display_name'  => 'Allow UPI payment method on creating auth link',
            'documentation' => '',
        ],
        self::SUBSCRIPTION_UPI => [
            'feature'       => self::SUBSCRIPTION_UPI,
            'display_name'  => 'Allow UPI payment method on subscription',
            'documentation' => '',
        ],
        self::CAW_RECURRING_CHARGE_AXIS => [
            'feature'       => self::CAW_RECURRING_CHARGE_AXIS,
            'display_name'  => 'Custom Recurring Charge Batch for Axis',
            'documentation' => '',
        ],
        self::CARD_MANDATE_SKIP_PAGE => [
            'feature' => self::CARD_MANDATE_SKIP_PAGE,
            'display_name'  => 'Skip summary page for card recurring payments',
            'documentation' => '',
        ],
        self::RZP_TRUSTED_BADGE => [
            'feature'       => self::RZP_TRUSTED_BADGE,
            'display_name'  => 'Razorpay Trusted Badge',
            'documentation' => '',
        ],
        self::CAPITAL_CARDS_COLLECTIONS => [
            'feature'       => self::CAPITAL_CARDS_COLLECTIONS,
            'display_name'  => 'Autocollection for credit cards',
            'documentation' => '',
        ],
        self::DISABLE_TPV_FLOW => [
            'feature'       => self::DISABLE_TPV_FLOW,
            'display_name'  => 'Disable tpv flow for the merchant',
            'documentation' => '',
        ],
        self::SUB_VIRTUAL_ACCOUNT => [
            'feature'       => self::SUB_VIRTUAL_ACCOUNT,
            'display_name'  => 'Enable Sub Virtual Account',
            'documentation' => '',
        ],
        self::COVID_19_RELIEF => [
            'feature'       => self::COVID_19_RELIEF,
            'display_name'  => 'Will show covid 19 related donation after successful payment',
            'documentation' => '',
        ],
        self::PL_BLOCK_CUSTOMER_PREFILL => [
            'feature'       => self::PL_BLOCK_CUSTOMER_PREFILL,
            'display_name'  => 'Will not prefill the customer details on Payment Link hosted page',
            'documentation' => '',
        ],
        self::DISABLE_X_AMAZONPAY => [
            'feature'       => self::DISABLE_X_AMAZONPAY,
            'display_name'  => 'Disable amazonpay payouts for the merchant',
            'documentation' => '',
        ],
        self::RBL_CA_UPI => [
            'feature'       => self::RBL_CA_UPI,
            'display_name'  => 'Enable UPI mode for merchants on RBL CA',
            'documentation' => '',
        ],
        self::SKIP_CUSTOMER_ID_CHECKOUT => [
            'feature'       => self::SKIP_CUSTOMER_ID_CHECKOUT,
            'display_name'  => 'Skip sending customer_id to checkout',
            'documentation' => '',
        ],
        self::ROUTE_KEY_MERCHANTS_QUEUE => [
            'feature'       => self::ROUTE_KEY_MERCHANTS_QUEUE,
            'display_name'  => 'To use new transfer processing queue',
            'documentation' => '',
        ],
        self::CAPITAL_FLOAT_ROUTE_MERCHANT => [
            'feature'       => self::CAPITAL_FLOAT_ROUTE_MERCHANT,
            'display_name'  => 'To use dedicated transfer processing queue',
            'documentation' => '',
        ],
        self::SLICE_ROUTE_MERCHANT => [
            'feature'       => self::SLICE_ROUTE_MERCHANT,
            'display_name'  => 'To use dedicated transfer processing queue',
            'documentation' => '',
        ],
        self::FEATURE_BBPS => [
            'feature'       => self::FEATURE_BBPS,
            'display_name'  => 'Enable BBPS Product for the merchant',
            'documentation' => '',
        ],
        self::DISPUTE_PRESENTMENT => [
            'feature'       => self::DISPUTE_PRESENTMENT,
            'display_name'  => 'Enable dispute presentment',
            'documentation' => '',
        ],
        self::M2M_REFERRAL =>[
            'feature'       => self::M2M_REFERRAL,
            'display_name'  => 'M2M Referrals',
            'documentation' => '',
        ],
        self::API_BULK_APPROVALS => [
            'feature'       => self::API_BULK_APPROVALS,
            'display_name'  => 'Enable API bulk Approvals for the merchant',
            'documentation' => '',
        ],
        self::DISABLE_ONDEMAND_FOR_CARD =>  [
            'feature'       => self::DISABLE_ONDEMAND_FOR_CARD,
            'display_name'  => 'Disable ondemand for card',
            'documentation' =>  '',
        ],
        self::DISABLE_ONDEMAND_FOR_LOAN =>  [
            'feature'       =>  self::DISABLE_ONDEMAND_FOR_LOAN,
            'display_name'  =>  'Disable ondemand for loan',
            'documentation' =>  '',
        ],
        self::DISABLE_ONDEMAND_FOR_LOC  =>  [
            'feature'       =>  self::DISABLE_ONDEMAND_FOR_LOC,
            'display_name'  =>  'Disable ondemand for loc',
            'documentation' =>  '',
        ],
        self::DISABLE_CARDS_POST_DPD  =>  [
            'feature'       =>  self::DISABLE_CARDS_POST_DPD,
            'display_name'  =>  'Disable cards post dpd',
            'documentation' =>  '',
        ],
        self::DISABLE_LOANS_POST_DPD  =>  [
            'feature'       =>  self::DISABLE_LOANS_POST_DPD,
            'display_name'  =>  'Disable loans post dpd',
            'documentation' =>  '',
        ],
        self::DISABLE_LOC_POST_DPD  =>  [
            'feature'       =>  self::DISABLE_LOC_POST_DPD,
            'display_name'  =>  'Disable loc post dpd',
            'documentation' =>  '',
        ],
        self::DISABLE_AMAZON_IS_POST_DPD  =>  [
            'feature'       =>  self::DISABLE_AMAZON_IS_POST_DPD,
            'display_name'  =>  'Disable amazon_is post dpd',
            'documentation' =>  '',
        ],
        self::HIGH_TPS_COMPOSITE_PAYOUT => [
            'feature'       => self::HIGH_TPS_COMPOSITE_PAYOUT,
            'display_name'  => 'Feature to have a separate composite API for High TPS merchants',
            'documentation' => '',
        ],
        self::BENE_EMAIL_NOTIFICATION => [
            'feature'       => self::BENE_EMAIL_NOTIFICATION,
            'display_name'  => 'Feature to enable email notification to bene',
            'documentation' => '',
        ],
        self::BENE_SMS_NOTIFICATION => [
            'feature'       => self::BENE_SMS_NOTIFICATION,
            'display_name'  => 'Feature to enable sms notification to bene',
            'documentation' => '',
        ],
        self::NEW_SETTLEMENT_SERVICE => [
            'feature'       => self::NEW_SETTLEMENT_SERVICE,
            'display_name'  => 'Enable new flow for settlements',
            'documentation' => '',
        ],
        self::PAYOUT_ASYNC_INGRESS => [
            'feature'       => self::PAYOUT_ASYNC_INGRESS,
            'display_name'  => 'Feature to have a separate composite API ingress',
            'documentation' => '',
        ],
        self::ONE_CLICK_CHECKOUT => [
            'feature'       => self::ONE_CLICK_CHECKOUT,
            'display_name'  => 'One click checkout',
            'documentation' => '',
        ],
        self::ONE_CC_MANDATORY_LOGIN => [
            'feature'       => self::ONE_CC_MANDATORY_LOGIN,
            'display_name'  => '1cc mandatory login',
            'documentation' => '',
        ],
        self::ONE_CC_COUPONS => [
            'feature'       => self::ONE_CC_COUPONS,
            'display_name'  => 'One click checkout',
            'documentation' => '',
        ],
        self::ONE_CC_MERCHANT_DASHBOARD => [
            'feature'       => self::ONE_CC_MERCHANT_DASHBOARD,
            'display_name'  => 'One click checkout tab on merchant dashboard',
            'documentation' => '',
        ],
        self::DISABLE_COLLECT_CONSENT => [
            'feature'       => self::DISABLE_COLLECT_CONSENT,
            'display_name'  => 'Disable tokenisation consent collection by Razorpay',
            'documentation' => '',
        ],
        self::RECURRING_CARD_MANDATE_BILLDESK_SIHUB => [
            'feature' => self::RECURRING_CARD_MANDATE_BILLDESK_SIHUB,
            'display_name'  => 'Card mandate for card recurring payments for billdesk sihub',
            'documentation' => '',
        ],
        self::ONE_CC_GA_ANALYTICS => [
            'feature'       => self::ONE_CC_GA_ANALYTICS,
            'display_name'  => 'Enable GA analytics for One Click checkout',
            'documentation' => '',
        ],
        self::ONE_CC_FB_ANALYTICS => [
            'feature'       => self::ONE_CC_FB_ANALYTICS,
            'display_name'  => 'Enable FB analytics for One Click checkout',
            'documentation' => '',
        ],
        self::PARTNER_SUB_KYC_ACCESS => [
          'feature'       => self::PARTNER_SUB_KYC_ACCESS,
          'display_name'  => 'Skip approval workflow to access submerchant Kyc',
          'documentation' => '',
      ],
        self::AUTHORIZE_VIA_AUTHZ => [
            'feature'       => self::AUTHORIZE_VIA_AUTHZ,
            'display_name'  => 'Enable authorization via authz enforcer',
            'documentation' => 'This feature will be used to control the rollout of authorization via authz enforcer',
        ],
        self::SKIP_OAUTH_NOTIFICATION => [
            'feature'       => self::SKIP_OAUTH_NOTIFICATION,
            'display_name'  => 'Enable oauth application for application',
            'documentation' => 'This feature will be used to control the notification emails for oauth applications',
        ],
        self::SKIP_PAYOUT_EMAIL => [
            'feature'      => self::SKIP_PAYOUT_EMAIL,
            'display_name' => 'Skip email notification to merchants',
            'description'  => 'This feature, if enabled, will not send email notifications to merchants',
        ],
        self::LEDGER_JOURNAL_READS => [
            'feature'      => self::LEDGER_JOURNAL_READS,
            'display_name' => 'Ledger Journal Reads',
            'description'  => 'This feature, if enabled, will cause reporting to be served from ledger data',
        ],
        self::RAZORPAYX_FLOWS_VIA_OAUTH => [
            'feature'      => self::RAZORPAYX_FLOWS_VIA_OAUTH,
            'display_name' => 'Access to RazorpayX flows via oauth',
            'description'  => 'This feature, if enabled, will allow access to RazorpayX exclusive flows via oauth',
        ],
        self::BLOCKLIST_FOR_WORKFLOW_SERVICE => [
            'feature'      => self::BLOCKLIST_FOR_WORKFLOW_SERVICE,
            'display_name' => 'Process workflows via API (old workflow setup)',
            'description'  => 'This feature, if enabled, will process the workflows for the merchant via API Monolith',
        ]
    ];

    /**
     * Disable feature enable mail notification.
     *
     * @var array
     */
    public static $skipFeaturesEnableMail = [
        self::SUBSCRIPTIONS,
    ];

    /**
     * Features that are dependant on other features.
     * Dependency is checked when the merchant makes the request to update the features.
     *
     * @var array
     */
    public static $featureDependencyMap = [
        self::LOC_STAGE_2         => [
            self::LOC_STAGE_1
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
        self::QR_CODES,
        self::ES_AUTOMATIC,
        self::ES_AUTOMATIC_RESTRICTED,
        self::SKIP_WORKFLOWS_FOR_API,
        self::LOC_STAGE_2,
        self::SETTLEMENTS_SMS_STOP,
        self::COVID_19_RELIEF,
        self::CRED_MERCHANT_CONSENT,
        self::MISSED_ORDERS_PLINK,
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_COUPONS,
        self::ONE_CC_MANDATORY_LOGIN,
        self::ONE_CC_MERCHANT_DASHBOARD,
        self::DISABLE_COLLECT_CONSENT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::CARD_MANDATE_SKIP_PAGE
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
