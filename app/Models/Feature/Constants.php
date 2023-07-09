<?php

namespace RZP\Models\Feature;

use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

class Constants
{

    //M2M referral feature metadata
    const M2M_REFERRAL                          = 'm2m_referral';

    //M2M referral environment keys
    const M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED           = "M2M_REFERRAL_MAX_REFERRED_COUNT_ALLOWED";

    CONST FEE_PAGE_TIMEOUT_CUSTOM         = 'fee_page_timeout_custom';
    CONST SILENT_REFUND_LATE_AUTH         = 'silent_refund_late_auth';

    const ENTITY_IDS                      = 'entity_ids';
    const ENTITY_TYPE                     = 'entity_type';
    const NAMES                           = 'names';
    const DUMMY                           = 'dummy';
    const WEBHOOKS                        = 'webhooks';
    const AGGREGATOR                      = 'aggregator';
    const TERMINAL_ONBOARDING             = 'terminal_onboarding';
    const ALLOW_ICICI_SHARED              = 'allow_icici_shared';
    const RULE_BASED_ENABLEMENT           = 'rule_based_enablement';
    const SPR_DISABLE_METHOD_RESET        = 'spr_disable_method_reset';
    const S2SWALLET                       = 's2swallet';
    const S2SUPI                          = 's2supi';
    const S2SAEPS                         = 's2saeps';
    const SETL_REPORT                     = 'setl_report';
    const NOFLASHCHECKOUT                 = 'noflashcheckout';
    const RECURRING                       = 'recurring';
    const S2S                             = 's2s';
    const S2S_JSON                        = 's2s_json';
    const S2S_DISABLE_CARDS               = 's2s_disable_cards';
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
    const ONDEMAND_LINKED                 = 'ondemand_linked';
    const ONDEMAND_LINKED_PREPAID         = 'ondemand_linked_prepaid';
    const ONDEMAND_ROUTE                  = 'ondemand_route';
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
    const DISABLED_FEATURE                = 'disabled_feature';
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

    const CUSTOMER_FEE_DONT_SETTLE        = 'customer_fee_dont_settle';
    /**
     * Feature flag used to enable dynamic currency conversion payments to get authorized and captured via cybersource gateway.
     */
    const DYNAMIC_CURRENCY_CONVERSION_CYBS = 'dcc_cybs';

    /**
     * Feature flag used to display modified checkout UI in terms of Dynamic Currency Conversion related disclosures,
     * defined by card-networks.
     */
    const SHOW_CUSTOM_DCC_DISCLOSURES = 'show_custom_dcc_discl';

    /**
     * The feature flag is used for VAS merchant on-boarding, enabling the skipping of document verification in KYC form submission,
     * from the backend. Since KYC verification does not apply to the banking's merchants, the bank itself performs the verification.
     */
    const SKIP_KYC_VERIFICATION = "skip_kyc_verification";

    /**
     * Feature flag to allow customer fee bearer model on international payments
     * Affects DCC and MCC flows
     */

    const ALLOW_CFB_INTERNATIONAL        = 'allow_cfb_international';

    const DIRECT_SETTLEMENT               = 'direct_settlement';

    const AVS                             = 'avs';

    const COVID                           = 'covid';
    const SR_SENSITIVE                    = 'sr_sensitive';
    const RAAS                            = 'raas';
    const OPTIMIZER_SMART_ROUTER          = 'optimizer_smart_router';
    const SKIP_NOTES_MERGING              = 'skip_notes_merging';
    const ENABLE_SINGLE_RECON             = 'enable_single_recon';

    const OPTIMIZER_RAZORPAY_VAS          = 'optimizer_razorpay_vas';

    // Ledger constants
    const IDEMPOTENCY_KEY                   = 'idempotency_key';
    const MERCHANT_ID                       = 'merchant_id';
    const MODE                              = 'mode';
    const PG_GATEWAY_ONBOARD                = 'pg_gateway_onboard';
    const SUCCESS                           = 'success';
    const FAILURE                           = 'failure';
    const MERCHANT_FEATURE_ALREADY_ENABLED  = 'merchant feature already enabled';
    const MERCHANT_FEATURE_ALREADY_DISABLED = 'merchant feature already disabled';
    const ACCOUNT_CREATION_FAILED           = 'account creation failed';
    const BALANCE_RESPONSE                  = 'balance_response';
    const CREDITS_RESPONSE                  = 'credits_response';

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

    const ADDITIONAL_ONBOARDING = 'additional_onboarding';

    const HIDE_INSTRUMENT_REQUEST = 'hide_instrument_request';

    const CUSTOM_REPORT_EXTENSIONS = 'custom_report_extensions';

    const QC_INTIMATION_EMAIL = 'qc_intimation_email';

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

    /*
     * disable commission invoice auto-approval for both partner and finance
     */
    const AUTO_COMM_INV_DISABLED          = 'auto_comm_inv_disabled';

    /*
     *  when a partner is onboarded via Admin lead invite flow, then the flag for the partner is enabled
     */
    const ADMIN_LEAD_PARTNER              = 'admin_lead_partner';

    /**
     * This feature will enable the linked accounts of the partner to be automatically
     * available as recipients for transfers from all the partner sub-merchants given
     * the transfer is made via partner auth
     */
    const ROUTE_PARTNERSHIPS = 'route_partnerships';

    /**
     * This feature enables the partner to put the payment settlements of all sub-merchants on hold
     * by default. Later, the partner can release individual payments for settlement using an API
     */
    const SUBM_MANUAL_SETTLEMENT = 'subm_manual_settlement';

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
     * show summary page for card mandate recurring initial payment
     * Note: we flipped the use case of this feature flag. Earlier it used to skip the summary page if enabled.
     * Now when it is enabled, it will show the mandate summary page.
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
     * This feature is assigned for marketplace parent merchants for linked account kyc verifications.
     */
    const ROUTE_NO_DOC_KYC = 'route_no_doc_kyc';

    /**
     * This feature will be assinged to all submerchants onboarded via V2 onboarding APIs
     */
    const CREATE_SOURCE_V2 = 'create_source_v2';

    /**
     * Flag to decide whether razorpay can send communication mails to partner's submerchants
     */
    const NO_COMM_WITH_SUBMERCHANTS       = 'no_comm_with_submerchants';

    /**
     * This feature will enable partners to get the sub-merchants,
     * onboarded via V2 onboarding APIs, instantly_activated after L1 form submission
     */
    const INSTANT_ACTIVATION_V2_API = 'instant_activation_v2_api';

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
     * Gives access to Cash On Card Service
     */
    const CASH_ON_CARD = 'cash_on_card';

    /**
     * Gives access to loc service for es amazon merchants
     */
    const WITHDRAWAL_ES_AMAZON = 'withdrawal_es_amazon';

    /**
     * Gives access to loc cli offer
     */
    const LOC_CLI_OFFER = 'loc_cli_offer';

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

    // With this feature, an Rx merchant can act as a sub-account on top of a master merchant.
    // Sub account is a virtual segregation that can be independently operated by the sub-merchant
    const ASSUME_SUB_ACCOUNT = 'assume_sub_account';

    // With this feature, an Rx merchant with a bank account can act as a master account onto
    // which they can onboard sub-account(s)
    const ASSUME_MASTER_ACCOUNT = 'assume_master_account';

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

    /**
     * Enables Currency_cloud for B2B export transactions
     */
    const ENABLE_B2B_EXPORT = 'enable_b2b_export';

    /**
     * Enabled Global Bank Account solution (Temporary)
     */
    const ENABLE_GLOBAL_ACCOUNT = 'enable_global_account';

    /**
     * Enables Settlement Flow for B2B export Transactions
     * Done by Risk Team
     */
    const ENABLE_SETTLEMENT_FOR_B2B = 'enable_settlement_for_b2b';

    /**
     * Enables OPGSP import flow on merchant
     */
    const OPGSP_IMPORT_FLOW = 'opgsp_import_flow';

    /**
     * Allows the dashboard to show the B2B account creation for merchants
     */
    const ENABLE_INTL_BANK_TRANSFER = 'enable_intl_bank_transfer';

    /**
     * Add 3ds merchant details to Authorize body
     */
    const ENABLE_3DS2 = 'enable_3ds2';

    /*
     * Enable feature to appear/disappear support url at org level
    */
    const SHOW_SUPPORT_URL = 'show_support_url';

    /**
     * Enables custom branding at org level.
     */
    const ORG_CUSTOM_BRANDING = 'org_custom_branding';

    const ORG_CUSTOM_SETTLEMENT_CONF = 'org_custom_settl_conf';

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
    const WHITE_LABELLED_VA                      = 'white_labelled_va'; // Virtual accounts
    const WHITE_LABELLED_QRCODES                 = 'white_labelled_qrcodes'; // QR codes
    const WHITE_LABELLED_PL                      = 'white_labelled_pl'; // payment links
    const WHITE_LABELLED_SUBS                    = 'white_labelled_subs'; // subscriptions

    const WHITE_LABELLED_PAYMENT_PAGES           = 'white_labelled_pp';
    const WHITE_LABELLED_PAYMENT_BUTTONS         = 'white_labelled_pb';
    const WHITE_LABELLED_SUBS_BUTTONS            = 'white_labelled_sb';
    const WHITE_LABELLED_MARKETPLACE             = 'white_labelled_mp';
    const WHITE_LABELLED_STORES                  = 'white_labelled_stores';
    const WHITE_LABELLED_OFFERS                  = 'white_labelled_offers';
    const WHITE_LABELLED_CHECKOUT_REWARDS        = 'white_labelled_chk_reward';
    const CROSS_ORG_LOGIN                        = 'cross_org_login';

    /*
     * Org level feature to hide activation form by deafult
     */
    const ORG_HIDE_ACTIVATION_FORM  = 'hide_activation_form';

    /**
     * To Enable merchant to access Partner LMS Dashboard
     */
    const RBL_BANK_LMS_DASHBOARD = 'rbl_bank_lms_dashboard';

    /**
     * Org level flag to show custom Logo on checkout page
     */
    const ORG_CUSTOM_CHECKOUT_LOGO = 'custom_checkout_logo';


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
     * Enable Razorpay secure for shopify merchant
     */
    const RAZORPAY_SECURE_MERCHANT = 'razorpay_secure_merchant';
    /**
     * Enables Offers on Subscription
     */
    const OFFER_ON_SUBSCRIPTION = 'offer_on_subscription';

    /**
     * Payout Service enables for merchant
     */
    const PAYOUT_SERVICE_ENABLED           = 'payout_service_enabled';

    /**
     *  Schedule Payout Service enables for merchant
     */
    const SCHEDULE_PAYOUT_VIA_PS  =  'schedule_payout_via_ps';

    /**
     * Internal Contact Payout Service enables for merchant
     */
    const INTERNAL_CONTACT_VIA_PS = 'internal_contact_via_ps';

    /*
     * Allow get calls (id and multiple) for virtual account payouts via payout service
     */
    const FETCH_VA_PAYOUTS_VIA_PS = 'fetch_va_payouts_via_ps';

    /*
     * Rollout idempotency key payouts to go via via payout service
     */
    const IDEMPOTENCY_API_TO_PS = 'idempotency_api_to_ps';

    /*
     * Rollback idempotency key payouts to go via via api instead of payouts service
     */
    const IDEMPOTENCY_PS_TO_API = 'idempotency_ps_to_api';

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

    const DEDUPE_CONTACT_ON_REPLICA = 'dedupe_contact_on_replica';

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

    const ORG_NUMERIC_OPTION_FALSE     = 'numeric_option_false';

    const AXIS_ACCESS                   = 'axis_access';

    public static $merchantFeaturesForOrgAccess = [
        'axis_org' => 'axis_access',
    ];

    /**
     * Used to manage on_hold feature in case of bene/NPCI downtime for payout requests
     * If this flag is enabled the payout will be queued for a certian sla or until the uptime is detected
     */
    const PAYOUTS_ON_HOLD = 'payouts_on_hold';

    /**
     * Used to manage ip whitelisting mandating on a merchant. This feature will control ip whitelisting
     * to be applied for X usecases.
     */
    const ENABLE_IP_WHITELIST = 'enable_ip_whitelist';

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
     * Used to manage exposing fee recovery details in banking_accounts api
     * Since fee recovery calculation is taking a lot of time, we will not send it when frontend does not need it.
     * This feature flag is temporary to test out the fee recovery skipping.To be removed once UI starts sending the flag
     */
    const SKIP_EXPOSE_FEE_RECOVERY = 'skip_expose_fee_recovery';

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
     * Merchant/Org level feature flag for HDFC 2.0 Checkout
     */
    const HDFC_CHECKOUT_2   = 'hdfc_checkout_2';

    /**
     * Banking Orgs UI Controls
     */
    const ORG_HIDE_SETTLEMENT_DETAILS = 'hide_settlement_details';
    const ORG_ENABLE_EXTERNAL_REDIRECT = 'enable_external_redirect';
    const ORG_HIDE_RAZORPAY_TEXT_LINKS = 'hide_razorpay_text_link';

    /**
     * Feature flag for controlling SI transaction CyberSource.
     */
    const CYBERSOURCE_SI_TXN_TEST    =   'cybersource_si_txn_test';

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
     * Feature flag to temporarily hold implementation of VA to VA payouts using creditTransfers
     */
    const HANDLE_VA_TO_VA_PAYOUT = 'handle_va_to_va_payout';

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
    const EXCLUDE_DISPUTE_PRESENTMENT = 'exclude_disp_presentment';

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
    const ONE_CC_INPUT_ENGLISH = 'one_cc_input_english';

    const ONE_CLICK_DUAL_CHECKOUT = 'one_cc_dual_checkout';

    const ONE_CC_STORE_ACCOUNT = 'one_cc_store_account';

    const ONE_CLICK_OVERRIDE_THEME = 'one_cc_override_theme';

    /**
     * Feature flag to disable shopify address ingestion
     */
    const ONE_CC_ADDRESS_SYNC_OFF = 'one_cc_address_sync_off';

    const ONE_CC_REPORTING_TEST  = 'one_cc_reporting_test';

    const ONE_CC_MERCHANT_DASHBOARD = 'one_cc_merchant_dashboard';

    const ONE_CC_GA_ANALYTICS = 'one_cc_ga_analytics';

    const ONE_CC_FB_ANALYTICS = 'one_cc_fb_analytics';

    const ONE_CC_CONSENT_DEFAULT = 'one_cc_consent_default';

    const ONE_CC_CONSENT_NOTDEFAULT = 'one_cc_consent_notdefault';

    const ONE_CC_COUPON_DISABLE_COD = 'one_cc_coupon_disable_cod';

    const ONE_CC_DISABLE_EMAIL_COOKIE = 'one_cc_disableemailcookie';

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
     * All Ledger Features for RX Virtual accounting release
     */
    const LEDGER_JOURNAL_WRITES = 'ledger_journal_writes';
    const LEDGER_JOURNAL_READS  = 'ledger_journal_reads';
    const LEDGER_REVERSE_SHADOW = 'ledger_reverse_shadow';

    /**
     * All Ledger Features for RX Direct accounting release
     */
    const DA_LEDGER_JOURNAL_WRITES = 'da_ledger_journal_writes';

    /**
     * Ledger Features for PG release
     */
    const PG_LEDGER_JOURNAL_WRITES = 'pg_ledger_journal_writes';

    const PG_LEDGER_REVERSE_SHADOW = 'pg_ledger_reverse_shadow';

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
     * Feature flag for custom checkout merchants to enable network tokenization in live mode
     * Saved card feature for custom checkout merchants will not work without this feature flag
     */
    const NETWORK_TOKENIZATION_PAID = 'network_tokenization_paid';

    /**
     * Feature flag to enable issuer tokenization apis in live mode
     */
    const ISSUER_TOKENIZATION_LIVE = 'issuer_tokenization_live';

     /**
      * Feature flag to allow network tokens in response
     */
    const ALLOW_NETWORK_TOKENS = 'allow_network_tokens';

    /**
     * Feature flag to allow creation of Payment Links for missed orders via Payment Gateway (via Orders API)
     */
    const MISSED_ORDERS_PLINK = 'missed_orders_plink';

    /**
     * Feature flag to allow custom domain linking with payment pages
     */
    const PP_CUSTOM_DOMAIN = 'pp_custom_domain';

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
    const ONBOARD_TOKENIZATION_DINERS = 'onboard_tokenization_dnrs';

    /**
     * Feature flag to onboard merchants on async tokenisation
     */
    const ASYNC_TOKENISATION = 'async_tokenisation';

    /**
     * Feature flag to onboard merchants on async tokenisation for recurring
     */
    const ASYNC_TOKENISATION_RECUR = 'async_tokenisation_recur';

     /* Feature flag to enable consent collection screen on custom checkout
     * merchants in the scenario where Razorpay would collect consent
     * on-behalf of the merchant.
     *
     * @see https://razorpay.com/docs/payments/payment-gateway/web-integration/custom/features/saved-cards/scenario-2/
     */
    const CUSTOM_CHECKOUT_CONSENT_SCREEN = 'cust_checkout_cnsnt_scrn';


    /**
     * Feature flag to let Razorpay collect consent for tokenising cards in the payment flow through intermediate consent page
     * This will be used for custom checkout merchants
     * By default Razorpay collects consent
     * Can use this feature flag to disable consent collection by Razorpay
     *
     * @deprecated
     * @see self::CUSTOM_CHECKOUT_CONSENT_SCREEN
     */
    const DISABLE_COLLECT_CONSENT = 'disable_collect_consent';

    /**
     * Feature flag to let Razorpay collect consent for tokenising cards in the payment flow through intermediate consent page
     * This will be used for custom checkout merchants for recurring payment only
     * By default Razorpay collects consent
     * Can use this feature flag to disable consent collection by Razorpay
     *
     */
    const NO_CUSTOM_CHECKOUT_RECURRING_CONSENT = 'no_cust_chekout_rec_cons';

    /**
     * Flag to be enabled for merchants for whom we want to generate the axis card payments and refunds file
     */
    const AXIS_SETTLEMENT_FILE = 'axis_settlement_file';

    /**
     * Feature flag to control tokenised card payments
     * If activated, tokenised card payments will go through plain card number
     *      instead of tokenised card number for the merchant
     * Can be removed post June 30th tokenisation deadline
     *      since plain card number won't be available post deadline
     */
    const DISABLE_TOKENISED_PAYMENT = 'disable_tokenised_payment';

    /**
     * Flag to enable the new composite payout flow meant for high tps merchants.
     * Initially implemented specifically for whatsapp.
     */
    const HIGH_TPS_COMPOSITE_PAYOUT = 'high_tps_composite_payout';

    /**
     * Flag to enable faster payout creation flow
     */
    const HIGH_TPS_PAYOUT_INGRESS = 'high_tps_payout_ingress';

    /**
     * Flag to enable faster payout processing flow
     */
    const HIGH_TPS_PAYOUT_EGRESS = 'high_tps_payout_egress';

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

    // Deprecated
    const BENE_EMAIL_NOTIFICATION = 'bene_email_notification';
    const BENE_SMS_NOTIFICATION   = 'bene_sms_notification';

    /**
     * Flags to send notifications to beneficiary  when payout is processed
     */
    const DISABLE_API_PAYOUT_BENE_EMAIL = 'disable_api_payout_email';
    const DISABLE_DB_PAYOUT_BENE_EMAIL = 'disable_db_payout_email';
    const ENABLE_API_PAYOUT_BENE_SMS = 'enable_api_payout_sms';
    const DISABLE_DB_PAYOUT_BENE_SMS = 'disable_db_payout_sms';

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

    const AFFORDABILITY_WIDGET_WHITE_LABEL = 'aff_widget_white_label';

    /**
     * Feature flag to allow the transition from older flow to newer flow
     * for payouts to cards tokenisation
     */
    const ALLOW_NON_SAVED_CARDS = 'allow_non_saved_cards';

    /**
     * Feature flag to allow bu_namespace changes for payouts to cards
     */
    const PAYOUT_NAMESPACE_CHANGES = 'payout_namespace_changes';

    /**
     * Feature flag to check if the vault token generated from card vault is proper
     * according to the payout to cards flow
     */
    const VAULT_COMPLIANCE_CHECK = 'vault_compliance_check';

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

    const ORG_SETTLE_TO_BANK = "org_settle_to_bank";

    const CANCEL_SETTLE_TO_BANK = "cancel_settle_to_bank";

    const ENABLE_ORG_ACCOUNT = "enable_org_account";
    const OLD_CUSTOM_SETTL_FLOW = "old_custom_settl_flow";

    /* Banking pod Feature flags */

    // hdfc pl/pp
    const ENABLE_PAYER_NAME_FOR_PL = 'enable_payer_name_for_pl';

    const HIDE_NO_EXPIRY_FOR_PL = 'hide_no_expiry_for_pl';

    const SHOW_PNAME_IN_CHECKOUT_PL = 'show_pname_in_chkout_pl';

    const ENABLE_PAYER_NAME_FOR_PP = 'enable_payer_name_for_pp';

    const HIDE_NO_EXPIRY_FOR_PP = 'hide_no_expiry_for_pp';

    const HIDE_CREATE_NEW_TEMPLATE_PP = 'hide_create_new_tmpl_pp';

    const HIDE_DYNAMIC_PRICE_PP = 'hide_dynamic_price_pp';

    const ENABLE_MERCHANT_EXPIRY_PL = 'enable_merchant_expiry_pl';

    const ENABLE_MERCHANT_EXPIRY_PP = 'enable_merchant_expiry_pp';

    const ENABLE_CREATE_OWN_TEMPLATE = 'enbl_create_own_tmpl';

    const ENABLE_CUSTOMER_AMOUNT = 'enable_customer_amount';

    // form builder

    const FILE_UPLOAD_PP = 'file_upload_pp';

    // udf additional fields

    const ENABLE_ADDITIONAL_INFO_UPI = 'enable_addtl_info_upi';

    // merchant T&C

    const ENABLE_TC_DASHBOARD = 'enable_tc_dashboard';

    const DISABLE_TC_DASHBOARD = 'disable_tc_dashboard';

    // si billdesk

    const CYBERSOURCE_SI_TXN_LIVE = 'cybersource_si_txn_live';

    //  QR

    const DISABLE_QR_V2 = 'disable_qr_v2';

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


    //Role base FE feature
    const VIEW_OPFIN_SSO_ANNOUNCEMENT       = 'view_opfin_sso_announcement';

    //Role base FE feature
    const VIEW_SSL_BANNER                   = 'view_ssl_banner';

    //Role base FE feature
    const VIEW_ONBOARDING_CARDS             = 'view_onboarding_cards';

    const QR_CUSTOM_TXN_NAME              = 'qr_custom_txn_name';

    const CAN_ROLE_VIEW_TRXN_CARDS          = 'can_role_view_trxn_cards';

    const RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK    = 'RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK';
    /**
     * Feature flag to allow merchants to use single tid.
     */
    const HDFC_SINGLE_TID = 'hdfc_single_tid';

    /**
     * Feature flag to check if international recurring payment via checkout_dot_com gateway is supported for a merchant
     */
    const RECURRING_CHECKOUT_DOT_COM = "recurring_chkout_dot_com";

    const MESSAGE                        = 'message';
    const INPUT                          = 'input';
    const MERCHANT_ONBOARDED             = 'merchant onboarded';
    const STATUS_CODE                    = 'status_code';
    const BODY                           = 'body';
    const BAD_REQUEST_MERCHANT_ID_ABSENT = "BAD_REQUEST_MERCHANT_ID_ABSENT";
    const MERCHANT_OFFBOARDED            = 'merchant offboarded';
    const ALL_MERCHANT_BALANCES_SYNCED      = 'ALL_MERCHANT_BALANCES_SYNCED';


    const ORG_POOL_ACCOUNT_SETTLEMENT = 'org_pool_settlement';

    const CART_API_AMOUNT_CHECK  =  "cart_api_amount_check";

    const FK_NEW_ERROR_RESPONSE  = "fk_new_error_response";

    const SHOW_PAYMENT_RECEIVER_TYPE = 'show_pmt_receiver_type';

    /**
     * Feature flag to allow creation of inter account test payouts
     */
    const INTER_ACCOUNT_TEST_PAYOUT = 'inter_account_test_payout';

     /**
     * Feature flag to allow token interoperabilty between partner's sub merchant for saved card
     */
    const TOKEN_INTEROPERABILITY = 'token_interoperability';

      /**
     * Feature flag to configure report for KFIN
     */
    const KFIN_MERCHANT_REPORT = 'kfin_merchant_report';

    /*
     * Feature flag for HDFC QR Specification
     * */
    const UPIQR_V1_HDFC         = 'upiqr_v1_hdfc';

    const ORG_CUSTOM_UPI_LOGO   = 'org_custom_upi_logo';

    /**
     * NB : Enable only for maker-checker corporate flow
     *
     * Enable feature flag if corporate net banking auto refund shouldn't occur for 30 minutes
     * same as retail or normal corporate flow.
     * It will be refunded only after Merchant\Entity::AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE value
     */
    const NETBANKING_CORPORATE_DELAY_REFUND = 'nb_corporate_delay_refund';

     /**
     * Feature flag to configure report for CAMS
     */
    const CAMS_MERCHANT_REPORT = 'cams_merchant_report';

     /**
     * Feature flag to configure report for BSE
     */
    const BSE_MERCHANT_REPORT = 'bse_merchant_report';

    /**
     * Feature flag for enabling 2FA payout flows for ICICI CA merchants;
     */
    const ICICI_2FA = 'icici_2fa';

    /**
     * Feature flag for enabling BAAS flows for ICICI CA merchants;
     */
    const ICICI_BAAS = 'icici_baas';

    /**
     * Feature flag to download consolidated org reports from respective org admin dashboard
     */
    const CONSOLIDATED_ORG_REPORTS = 'consolidated_org_reports';

    /**
     * Feature flag to to expose arn and rrn in payment acquirer data.
     */
    const EXPOSE_RRN = 'expose_rrn';

     /**
     * Feature flag to configure report for NIUM
     */
    const IMPORT_FLOW_OPEX_REPORT = 'import_flow_opex_report';

    //Feature flag for sending payer name in payment email notification for QR payments
    const SEND_NAME_IN_EMAIL_FOR_QR = 'send_name_in_email_for_qr';

    /**
     * Feature flag to do avs check irrespective of if the card is 3ds or not
     */
    const MANDATORY_AVS_CHECK = 'mandatory_avs_check';

    /**
     * Feature flag to enable account <> sub-account setup. This is used when payouts are to be initiated from
     * a virtual account (read as sub VA) where as balance deduction happens from parent DA (read as master DA).
     * Main use case is to enable NBFCs integrate with FinTech's on X post RBI's lending guidelines.
     */
    const SUB_VA_FOR_DIRECT_BANKING = 'sub_va_for_direct_banking';

    /**
     Feature Flag to enable auto debit (link and pay) wallet flow for merchants. The customers paying to the merchant can
     * save their paytm wallets during the fisrt time payment later on they can make a payment with single click.
     **/

    const WALLET_PAYTM_AUTO_DEBIT = 'wallet_paytm_auto_debit';
    /**
     * Truecaller Login Feature Flags. purpose of having these at different levels is due to compliance reasons.
     * We may have to disable this feature on specific screen for specific merchant on specific platform.
     * having this feature as true lets user login/prefill their contact, email on checkout without OTP.
     */
    const DISABLE_TRUECALLER_LOGIN                     = 'dis_truecaller';
    const DISABLE_TRUECALLER_LOGIN_MWEB                = 'dis_truecaller_mweb';
    const DISABLE_TRUECALLER_LOGIN_SDK                 = 'dis_truecaller_sdk';
    const DISABLE_TRUECALLER_LOGIN_HOME_SCREEN         = 'dis_truecaller_home';
    const DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN      = 'dis_truecaller_contact';
    const DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN  = 'dis_truecaller_saved_card';
    const DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN = 'dis_truecaller_add_card';

    const ORG_ADMIN_PASSWORD_RESET = 'org_admin_password_reset';

    /**
     * Feature flag to block VA payouts from master merchant on account <> sub account flow.
     */
    const BLOCK_VA_PAYOUTS = 'block_va_payouts';

    const BLOCK_FAV = 'block_fav';

    /*
     * Feature flags for exceptions raised to merchant onboarding flow
     * */
    const ONLY_DS  = 'only_ds';

    const ORG_PROGRAM_DS_CHECK = 'program_ds_check';

    const OPTIMIZER_ONLY_MERCHANT   = 'optimizer_only_merchant';
    const REGULAR_TEST_MERCHANT     = 'regular_test_merchant';

    /**
     * Reduces OD balance from available balance for CA payouts
     */
    const REDUCE_OD_BALANCE_FOR_CA = 'reduce_od_balance_for_ca';

    /*
     * This feature will be used to enable payout approve/reject using oauth token for a merchant
     */
    const ENABLE_APPROVAL_VIA_OAUTH = 'enable_approval_via_oauth';

    /**
     * Feature flag to check push provisioning is enabled for the merchant
     */
    const PUSH_PROVISIONING_LIVE = 'push_provisioning_live';

    const DISABLE_UPI_NUM_CHECKOUT = 'disable_upi_num_checkout';
    const DISABLE_UPI_NUM_ON_L0 = 'disable_upi_num_on_l0';
    const DISABLE_UPI_NUM_ON_L1 = 'disable_upi_num_on_l1';
    const CLOSE_QR_ON_DEMAND    = 'close_qr_on_demand';

    const ONE_CC_SHOPIFY_ACC_CREATE = 'one_cc_shopify_acc_create';
    const ONE_CC_SHOPIFY_MULTIPLE_SHIPPING = 'one_cc_multiple_shipping';

    /**
     * Feature flag is used to disable auto read and auto submit feature on checkout.
     */
    const DISABLE_OTP_AUTO_READ_AND_SUBMIT = 'dis_otp_auto_read_submit';

    public static $recurringFeatures = [
        self::CHARGE_AT_WILL,
        self::SUBSCRIPTIONS,
        self::RECURRING_AUTO,
    ];

    const CHECKOUT_FEATURES = [
        self::GOOGLE_PAY,
        DcsConstants::EligibilityCheckDecline,
        self::CUSTOMER_ADDRESS,
        self::IRCTC_METHODS,
        self::GOOGLE_PAY_OMNICHANNEL,
        self::PHONEPE_INTENT,
        self::SAVE_VPA,
        self::REDIRECT_TO_ZESTMONEY,
        self::REDIRECT_TO_EARLYSALARY,
        self::DISABLE_NATIVE_CURRENCY,
        self::ALLOW_CFB_INTERNATIONAL,
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
        self::ONE_CLICK_DUAL_CHECKOUT,
        self::ONE_CC_REPORTING_TEST,
        self::ONE_CLICK_OVERRIDE_THEME,
        self::ONE_CC_INPUT_ENGLISH,
        self::ONE_CC_STORE_ACCOUNT,
        self::ONE_CC_CONSENT_DEFAULT,
        self::ONE_CC_CONSENT_NOTDEFAULT,
        self::ONE_CC_COUPON_DISABLE_COD,
        self::ONE_CC_DISABLE_EMAIL_COOKIE,
        DcsConstants::EmailOptionalOnCheckout,
        DcsConstants::ShowEmailOnCheckout,
        DcsConstants::CvvLessFlowDisabled,
        DcsConstants::UpiTurboDisabled,
        self::DISABLE_UPI_NUM_CHECKOUT,
        self::DISABLE_UPI_NUM_ON_L0,
        self::DISABLE_UPI_NUM_ON_L1,
        self::SHOW_CUSTOM_DCC_DISCLOSURES,
        self::DISABLE_OTP_AUTO_READ_AND_SUBMIT,
    ];

    const ONE_CC_FEATURES = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_MERCHANT_DASHBOARD,
        self::ONE_CC_COUPONS,
        self::ONE_CC_MANDATORY_LOGIN,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CLICK_DUAL_CHECKOUT,
        self::ONE_CC_REPORTING_TEST,
        self::ONE_CLICK_OVERRIDE_THEME,
        self::ONE_CC_INPUT_ENGLISH,
        self::ONE_CC_STORE_ACCOUNT,
        self::ONE_CC_CONSENT_DEFAULT,
        self::ONE_CC_CONSENT_NOTDEFAULT,
        self::ONE_CC_COUPON_DISABLE_COD,
        self::ONE_CC_DISABLE_EMAIL_COOKIE,
        self::ONE_CC_ADDRESS_SYNC_OFF,
        self::ONE_CC_SHOPIFY_ACC_CREATE,
        self::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING,
    ];

    const TRUECALLER_FEATURES = [
        self::DISABLE_TRUECALLER_LOGIN,
        self::DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN,
        self::DISABLE_TRUECALLER_LOGIN_HOME_SCREEN,
        self::DISABLE_TRUECALLER_LOGIN_MWEB,
        self::DISABLE_TRUECALLER_LOGIN_SDK,
        self::DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN,
        self::DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN,
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
        self::S2S_DISABLE_CARDS               => true,
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
        self::ONDEMAND_LINKED                 => true,
        self::ONDEMAND_LINKED_PREPAID         => true,
        self::ONDEMAND_ROUTE                  => true,
        self::HEADLESS_DISABLE                => true,
        self::BEPG_DISABLE                    => true,
        self::FIRST_DATA_S2S_FLOW             => true,
        self::BIN_ISSUER_VALIDATOR            => true,
        self::OFFER_PRIVATE_AUTH              => true,
        self::QR_CUSTOM_TXN_NAME              => true,
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
        self::RULE_BASED_ENABLEMENT           => true,
        self::ALLOW_ICICI_SHARED              => true,
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
        self::ALLOW_CFB_INTERNATIONAL         => true,
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
        self::OPTIMIZER_RAZORPAY_VAS          => true,
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
        self::CASH_ON_CARD                    => true,
        self::WITHDRAWAL_ES_AMAZON            => true,
        self::LOC_CLI_OFFER                   => true,
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
        self::ORG_CUSTOM_SETTLEMENT_CONF      => true,
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
        self::SCHEDULE_PAYOUT_VIA_PS          => true,
        self::FETCH_VA_PAYOUTS_VIA_PS         => true,
        self::IDEMPOTENCY_API_TO_PS           => true,
        self::IDEMPOTENCY_PS_TO_API           => true,
        self::INTERNAL_CONTACT_VIA_PS         => true,
        self::WHITE_LABELLED_INVOICES         => true,
        self::WHITE_LABELLED_ROUTE            => true,
        self::WHITE_LABELLED_VA               => true,
        self::WHITE_LABELLED_QRCODES          => true,
        self::WHITE_LABELLED_PL               => true,
        self::WHITE_LABELLED_SUBS             => true,
        self::WHITE_LABELLED_PAYMENT_PAGES    => true,
        self::WHITE_LABELLED_PAYMENT_BUTTONS  => true,
        self::WHITE_LABELLED_SUBS_BUTTONS     => true,
        self::WHITE_LABELLED_MARKETPLACE      => true,
        self::WHITE_LABELLED_STORES           => true,
        self::WHITE_LABELLED_OFFERS           => true,
        self::WHITE_LABELLED_CHECKOUT_REWARDS => true,
        self::COVID_19_RELIEF                 => true,
        self::BENE_NAME_IN_PAYOUT             => true,
        self::PAYOUTS_ON_HOLD                 => true,
        self::ENABLE_IP_WHITELIST             => true,
        self::SKIP_EXPOSE_FEE_RECOVERY        => true,
        self::SKIP_HOLD_PAYOUTS               => true,
        self::SKIP_TEST_TXN_FOR_DMT           => true,
        self::AXIS_SETTLEMENT_FILE            => true,
        self::SKIP_CONTACT_DEDUP_FA_BA        => true,
        self::PL_BLOCK_CUSTOMER_PREFILL       => true,
        self::ALLOW_VA_TO_VA_PAYOUTS          => true,
        self::AXIS_ACCESS                     => true,
        self::PAYMENT_SHOW_DCC_MARKUP         => true,
        self::EXPOSE_SETTLED_BY               => true,
        self::PAYPAL_CC                       => true,
        self::ORG_HIDE_ACTIVATION_FORM        => true,
        self::ORG_ADMIN_REPORT_ENABLE         => true,
        self::ORG_HDFC_VAS_CARDS_SURCHARGE    => true,
        self::HDFC_CHECKOUT_2                 => true,
        self::ORG_CUSTOM_CHECKOUT_LOGO        => true,
        self::ORG_HIDE_SETTLEMENT_DETAILS     => true,
        self::ORG_ENABLE_EXTERNAL_REDIRECT    => true,
        self::ORG_HIDE_RAZORPAY_TEXT_LINKS    => true,
        self::SHOW_OLD_ERROR_DESC             => true,
        self::PAYOUT_ASYNC_FTS_TRANSFER       => true,
        self::NULL_NARRATION_ALLOWED          => true,
        self::SKIP_SUBM_ONBOARDING_COMM       => true,
        self::ORG_CONTACT_VERIFY_DEFAULT      => true,
        self::SKIP_CUSTOMER_ID_CHECKOUT       => true,
        self::EXCLUDE_DISPUTE_PRESENTMENT     => true,
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
        self::PG_LEDGER_JOURNAL_WRITES        => true,
        self::PG_LEDGER_REVERSE_SHADOW        => true,
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
        self::NETWORK_TOKENIZATION_PAID       => true,
        self::ISSUER_TOKENIZATION_LIVE        => true,
        self::HIGH_TPS_COMPOSITE_PAYOUT       => true,
        self::HIGH_TPS_PAYOUT_INGRESS         => true,
        self::HIGH_TPS_PAYOUT_EGRESS          => true,
        self::MERCHANT_ROUTE_WA_INFRA         => true,
        self::BENE_EMAIL_NOTIFICATION         => true,
        self::BENE_SMS_NOTIFICATION           => true,
        self::DISABLE_API_PAYOUT_BENE_EMAIL   => true,
        self::DISABLE_DB_PAYOUT_BENE_EMAIL    => true,
        self::ENABLE_API_PAYOUT_BENE_SMS      => true,
        self::DISABLE_DB_PAYOUT_BENE_SMS      => true,
        self::ALLOW_NETWORK_TOKENS            => true,
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
        self::ASYNC_TOKENISATION_RECUR        => true,
        self::ASYNC_TOKENISATION              => true,
        self::DISABLE_TOKENISED_PAYMENT       => true,
        self::EXCLUDE_DEDUCT_DISPUTE          => true,
        self::ORG_ANNOUNCEMENT_TAB_DISABLE    => true,
        self::ASYNC_TXN_FILL_DETAILS          => true,
        self::EXPOSE_GATEWAY_ERRORS           => true,
        self::CUSTOM_CHECKOUT_CONSENT_SCREEN  => true,
        self::DISABLE_COLLECT_CONSENT         => true,
        self::ENABLE_IFSC_VALIDATION          => true,
        self::PUBLIC_SETTERS_VIA_OAUTH        => true,
        self::AFFORDABILITY_WIDGET            => true,
        self::AFFORDABILITY_WIDGET_WHITE_LABEL => true,
        self::ENABLE_GRANULAR_DOWNTIMES       => true,
        self::EDIT_SINGLE_VA_EXPIRY           => true,
        self::ADDRESS_NAME_REQUIRED           => true,
        self::ORG_DISABLE_DEF_EMAIL_RECEIPT   => true,
        self::NO_DOC_ONBOARDING               => true,
        self::SUBM_NO_DOC_ONBOARDING          => true,
        self::ALLOW_NON_SAVED_CARDS           => true,
        self::PAYOUT_NAMESPACE_CHANGES        => true,
        self::VAULT_COMPLIANCE_CHECK          => true,
        self::ALLOW_COMPLETE_ERROR_DESC       => true,
        self::ACCEPT_LOWER_AMOUNT             => true,
        self::SET_VA_DEFAULT_EXPIRY           => true,
        self::FAIL_VA_ON_VALIDATION           => true,
        self::ORG_SETTLE_TO_BANK              => true,
        self::CANCEL_SETTLE_TO_BANK           => true,
        self::ENABLE_ORG_ACCOUNT              => true,
        self::OLD_CUSTOM_SETTL_FLOW           => true,
        self::ENABLE_PAYER_NAME_FOR_PL        => true,
        self::HIDE_NO_EXPIRY_FOR_PL           => true,
        self::ENABLE_PAYER_NAME_FOR_PP        => true,
        self::HIDE_NO_EXPIRY_FOR_PP           => true,
        self::HIDE_CREATE_NEW_TEMPLATE_PP     => true,
        self::HIDE_DYNAMIC_PRICE_PP           => true,
        self::SHOW_PNAME_IN_CHECKOUT_PL       => true,
        self::ENABLE_MERCHANT_EXPIRY_PL       => true,
        self::ENABLE_MERCHANT_EXPIRY_PP       => true,
        self::ENABLE_CREATE_OWN_TEMPLATE      => true,
        self::ENABLE_CUSTOMER_AMOUNT          => true,
        self::FILE_UPLOAD_PP                  => true,
        self::ENABLE_ADDITIONAL_INFO_UPI      => true,
        self::ENABLE_TC_DASHBOARD             => true,
        self::DISABLE_TC_DASHBOARD            => true,
        self::CYBERSOURCE_SI_TXN_LIVE         => true,
        self::DISABLE_QR_V2                   => true,
        self::AUTHORIZE_VIA_AUTHZ             => true,
        self::SR_SENSITIVE_BUCKET_1           => true,
        self::SR_SENSITIVE_BUCKET_2           => true,
        self::SR_SENSITIVE_BUCKET_3           => true,
        self::SR_SENSITIVE_BUCKET_4           => true,
        self::INCREASE_PAYOUT_LIMIT           => true,
        self::DCC_ON_OTHER_LIBRARY            => true,
        self::SKIP_OAUTH_NOTIFICATION         => true,
        self::SKIP_PAYOUT_EMAIL               => true,
        self::OFFLINE_PAYMENT_ON_CHECKOUT     => true,
        self::ORDER_RECEIPT_UNIQUE_ERR        => true,
        self::SUBM_QR_IMAGE_CONTENT           => true,
        self::RECURRING_CARD_MANDATE_BILLDESK_SIHUB => true,
        self::RAZORPAYX_FLOWS_VIA_OAUTH       => true,
        self::ACCEPT_ONLY_3DS_PAYMENTS        => true,
        self::SUB_MERCHANT_PRICING_AUTOMATION => true,
        self::BLOCKLIST_FOR_WORKFLOW_SERVICE  => true,
        self::VIEW_OPFIN_SSO_ANNOUNCEMENT     => true,
        self::VIEW_SSL_BANNER                 => true,
        self::VIEW_ONBOARDING_CARDS           => true,
        self::CAN_ROLE_VIEW_TRXN_CARDS    => true,
        self::RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK  => true,
        self::ORG_NUMERIC_OPTION_FALSE        => true,
        self::ONE_CLICK_DUAL_CHECKOUT         => true,
        self::HDFC_SINGLE_TID                 => true,
        self::ORG_POOL_ACCOUNT_SETTLEMENT     => true,
        self::ONE_CC_REPORTING_TEST           => true,
        self::SHOW_PAYMENT_RECEIVER_TYPE      => true,
        self::HANDLE_VA_TO_VA_PAYOUT          => true,
        self::RECURRING_CHECKOUT_DOT_COM      => true,
        self::DEDUPE_CONTACT_ON_REPLICA       => true,
        self::INTER_ACCOUNT_TEST_PAYOUT       => true,
        self::CART_API_AMOUNT_CHECK           => true,
        self::ENABLE_B2B_EXPORT               => true,
        self::ENABLE_GLOBAL_ACCOUNT           => true,
        self::ENABLE_INTL_BANK_TRANSFER       => true,
        self::ENABLE_SETTLEMENT_FOR_B2B       => true,
        self::OPGSP_IMPORT_FLOW               => true,
        DcsConstants::ImportSettlement        => true,
        self::ONE_CLICK_OVERRIDE_THEME        => true,
        self::PP_CUSTOM_DOMAIN                => true,
        self::TOKEN_INTEROPERABILITY          => true,
        self::ONE_CC_INPUT_ENGLISH            => true,
        self::RAZORPAY_SECURE_MERCHANT        => true,
        self::RBL_BANK_LMS_DASHBOARD          => true,
        self::FK_NEW_ERROR_RESPONSE           => true,
        self::NO_CUSTOM_CHECKOUT_RECURRING_CONSENT => true,
        self::CROSS_ORG_LOGIN                 => true,
        self::INSTANT_ACTIVATION_V2_API       => true,
        self::MISSED_ORDERS_PLINK             => true,
        self::KFIN_MERCHANT_REPORT            => true,
        self::UPIQR_V1_HDFC                   => true,
        self::ORG_CUSTOM_UPI_LOGO             => true,
        self::NETBANKING_CORPORATE_DELAY_REFUND => false,
        self::ONE_CC_STORE_ACCOUNT            => true,
        self::ONE_CC_COUPON_DISABLE_COD       => true,
        self::CAMS_MERCHANT_REPORT            => true,
        self::ICICI_2FA                       => true,
        self::ICICI_BAAS                      => true,
        self::BSE_MERCHANT_REPORT             => true,
        self::CONSOLIDATED_ORG_REPORTS        => true,
        self::ONE_CC_CONSENT_DEFAULT          => true,
        self::ONE_CC_CONSENT_NOTDEFAULT       => true,
        self::EXPOSE_RRN                      => true,
        self::IMPORT_FLOW_OPEX_REPORT         => true,
        self::ENABLE_3DS2                     => true,
        self::CYBERSOURCE_SI_TXN_TEST         => true,
        self::MANDATORY_AVS_CHECK             => true,
        self::SUB_VA_FOR_DIRECT_BANKING       => true,
        self::BLOCK_VA_PAYOUTS                => true,
        self::BLOCK_FAV                       => true,
        self::SPR_DISABLE_METHOD_RESET        => true,
        self::SEND_NAME_IN_EMAIL_FOR_QR       => true,
        self::ONE_CC_DISABLE_EMAIL_COOKIE     => true,
        self::ROUTE_NO_DOC_KYC                => true,
        DcsConstants::RefundEnabled           => true,  // Example Feature for DCS
        DcsConstants::DisableAutoRefund       => true,  // Example Feature for DCS
        DcsConstants::EligibilityEnabled      => true,
        DcsConstants::EligibilityCheckDecline => true,
        DcsConstants::EmailOptionalOnCheckout => true,
        DcsConstants::ShowEmailOnCheckout     => true,
        DcsConstants::CvvLessFlowDisabled     => true,
        DcsConstants::AdminPasswordResetEnabled => true,
        DcsConstants::UpiTurboDisabled => true,
        self::DISABLE_UPI_NUM_CHECKOUT => true,
        self::DISABLE_UPI_NUM_ON_L0 => true,
        self::DISABLE_UPI_NUM_ON_L1 => true,
        self::ADDITIONAL_ONBOARDING => true,
        self::HIDE_INSTRUMENT_REQUEST => true,
        self::CUSTOM_REPORT_EXTENSIONS => true,
        self::QC_INTIMATION_EMAIL   => true,
        self::AUTO_COMM_INV_DISABLED          => true,
        self::ADMIN_LEAD_PARTNER      => true,
        self::ROUTE_PARTNERSHIPS => true,
        self::SUBM_MANUAL_SETTLEMENT => true,
        DcsConstants::EnableMerchantExpiryForPP => true,
        DcsConstants::EnableMerchantExpiryForPL => true,
        DcsConstants::EnableCustomerAmount => true,
        DcsConstants::EnableMerchantCreateOwnTemplate => true,
        self::ONE_CC_ADDRESS_SYNC_OFF         => true,
        self::REDUCE_OD_BALANCE_FOR_CA        => true,
        self::PUSH_PROVISIONING_LIVE          => true,
        self::DISABLE_TRUECALLER_LOGIN => true,
        self::DISABLE_TRUECALLER_LOGIN_CONTACT_SCREEN => true,
        self::DISABLE_TRUECALLER_LOGIN_HOME_SCREEN => true,
        self::DISABLE_TRUECALLER_LOGIN_MWEB => true,
        self::DISABLE_TRUECALLER_LOGIN_SDK => true,
        self::DISABLE_TRUECALLER_LOGIN_ADD_NEW_CARD_SCREEN => true,
        self::DISABLE_TRUECALLER_LOGIN_SAVED_CARDS_SCREEN  => true,
        self::ONLY_DS                         => true,
        self::ORG_PROGRAM_DS_CHECK            => true,
        self::OPTIMIZER_ONLY_MERCHANT         => true,
        self::REGULAR_TEST_MERCHANT           => true,
        DcsConstants::AffordabilityWidgetSet           => true,
        DcsConstants::AssumeSubAccount        => true,
        DcsConstants::AssumeMasterAccount     => true,
        self::CLOSE_QR_ON_DEMAND              => true,
        self::ONE_CC_SHOPIFY_ACC_CREATE       => true,
        self::CUSTOMER_FEE_DONT_SETTLE        => true,
        self::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING => true,
        self::SHOW_CUSTOM_DCC_DISCLOSURES      => true,
        self::DYNAMIC_CURRENCY_CONVERSION_CYBS => true,
        self::ORG_ADMIN_PASSWORD_RESET        => true,
        self::FEE_PAGE_TIMEOUT_CUSTOM         => true,
        self::SILENT_REFUND_LATE_AUTH         => true,
        self::DISABLE_OTP_AUTO_READ_AND_SUBMIT => true,
        self::WALLET_PAYTM_AUTO_DEBIT         => true,
        self::ENABLE_APPROVAL_VIA_OAUTH       => true,
        self::SKIP_KYC_VERIFICATION           => true,
    ];

    // Entity type constants
    const ACCOUNT                       = 'account';
    const MERCHANT                      = 'merchant';
    const APPLICATION                   = 'application';
    const ORG                           = 'org';
    const PARTNER_APPLICATION           = 'partner_application';

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
        self::MISSED_ORDERS_PLINK => [
            'feature' => self::MISSED_ORDERS_PLINK,
            'display_name' => "Enable missed orders payment-links feature from the dashboard",
            'documentation' => "",
        ],
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
        self::ONDEMAND_LINKED   => [
            'feature'       => self::ONDEMAND_LINKED,
            'display_name'  => 'Ondemand Linked',
            'documentation' => '',
        ],
        self::ONDEMAND_LINKED_PREPAID   => [
            'feature'       => self::ONDEMAND_LINKED_PREPAID,
            'display_name'  => 'Ondemand Linked Prepaid',
            'documentation' => '',
        ],
        self::ONDEMAND_ROUTE   => [
            'feature'       => self::ONDEMAND_ROUTE,
            'display_name'  => 'Ondemand Route',
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
        self::CASH_ON_CARD              => [
            'feature'       => self::CASH_ON_CARD,
            'display_name'  => 'Cash On Card',
            'documentation' => '',
        ],
        self::WITHDRAWAL_ES_AMAZON              => [
            'feature'       => self::WITHDRAWAL_ES_AMAZON,
            'display_name'  => 'Withdraw Loc for ES Amazon',
            'documentation' => '',
        ],
        self::LOC_CLI_OFFER              => [
            'feature'       => self::LOC_CLI_OFFER,
            'display_name'  => 'LOC CLI Offer',
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
        self::OPTIMIZER_RAZORPAY_VAS => [
            'feature'       => self::OPTIMIZER_RAZORPAY_VAS,
            'display_name'  => 'Razorpay Gateway for optimiser',
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
        self::RULE_BASED_ENABLEMENT  => [
            'feature'       => self::RULE_BASED_ENABLEMENT,
            'display_name'  => 'Enable rule based/ system based instrument enablement/disablement',
            'documentation' => '',
        ],
        self::ALLOW_ICICI_SHARED  => [
            'feature'       => self::ALLOW_ICICI_SHARED,
            'display_name'  => 'Accept UPI_ICICI shared terminal',
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
        self::EXCLUDE_DISPUTE_PRESENTMENT => [
            'feature'       => self::EXCLUDE_DISPUTE_PRESENTMENT,
            'display_name'  => 'Disable dispute presentment',
            'documentation' => '',
        ],
        self::M2M_REFERRAL =>[
            'feature'       => self::M2M_REFERRAL,
            'display_name'  => 'M2M Referrals',
            'documentation' => '',
        ],
        self::OPTIMIZER_ONLY_MERCHANT =>[
            'feature'       => self::OPTIMIZER_ONLY_MERCHANT,
            'display_name'  => 'Optimizer Only Merchant',
            'documentation' => '',
        ],
        self::REGULAR_TEST_MERCHANT =>[
            'feature'       => self::REGULAR_TEST_MERCHANT,
            'display_name'  => 'Regular Test Merchant',
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
        ],
        self::VIEW_OPFIN_SSO_ANNOUNCEMENT => [
            'feature'      => self::VIEW_OPFIN_SSO_ANNOUNCEMENT,
            'display_name' => 'View opfin sso announcemnet',
            'description'  => 'This feature, allows visibility of opfin sso announcement to owner and admin',
        ],
        self::VIEW_SSL_BANNER => [
            'feature'      => self::VIEW_SSL_BANNER,
            'display_name' => 'View SSL banner',
            'description'  => 'This feature, allows visibility of ssl banner to owner and admin',
        ],
        self::VIEW_ONBOARDING_CARDS => [
            'feature'      => self::VIEW_ONBOARDING_CARDS,
            'display_name' => 'View onboarding cards',
            'description'  => 'This feature, allows visibility of onboarding cards to all roles except view_only',
        ],
        self::CAN_ROLE_VIEW_TRXN_CARDS => [
            'feature'      => self::CAN_ROLE_VIEW_TRXN_CARDS,
            'display_name' => 'View Transaction/Cards banner in test mode',
            'description'  => 'This feature, allows view_only, fl1, fl2, fl3 roles to see transaction cards and banners on homepage',
        ],
        self::PP_CUSTOM_DOMAIN => [
            'feature'      => self::PP_CUSTOM_DOMAIN,
            'display_name' => 'Custom Domain for Payment Pages',
            'description'  => 'This feature is to allow merchants to link their payment pages with a custom domain',
        ],

        //TODO:: Handle vai custom roles
        self::RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK => [
            'feature'      => self::RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK,
            'display_name' => 'This feature is used to block the download report option',
            'description'  => 'This feature is used to block the download report option, to block reports RX_BLOCK_REPORT_DOWNLOAD should also be present at merchant level.',
        ],
        self::FILE_UPLOAD_PP => [
            'feature'       => self::FILE_UPLOAD_PP,
            'display_name'  => 'Feature to enable file upload functionality on payment pages',
            'description'   => 'Feature to enable file upload functionality on payment pages.This flag should be enabled at merchant level.',
        ],
        self::ACCEPT_ONLY_3DS_PAYMENTS =>[
            'feature'       => self::ACCEPT_ONLY_3DS_PAYMENTS,
            'display_name'  => 'Disable Non-3ds card processing',
            'documentation' => '',
        ],
        self::ICICI_2FA => [
            'feature'       => self::ICICI_2FA,
            'display_name'  => 'Feature for enabling ICICI 2FA flow for payouts',
            'documentation' => '',
        ],
        self::ENABLE_IP_WHITELIST => [
            'feature'       => self::ENABLE_IP_WHITELIST,
            'display_name'  => 'Feature for enabling ip whitelisting on X api requests',
            'documentation' => '',
        ],
        self::DISABLE_API_PAYOUT_BENE_EMAIL => [
            'feature'       => self::DISABLE_API_PAYOUT_BENE_EMAIL,
            'display_name'  => 'Feature to disable email notification to beneficiary for Payouts made via API',
            'documentation' => '',
        ],
        self::DISABLE_DB_PAYOUT_BENE_EMAIL => [
            'feature'       => self::DISABLE_DB_PAYOUT_BENE_EMAIL,
            'display_name'  => 'Feature to disable email notification to beneficiary for Payouts made via Dashboard',
            'documentation' => '',
        ],
        self::ENABLE_API_PAYOUT_BENE_SMS => [
            'feature'       => self::ENABLE_API_PAYOUT_BENE_SMS,
            'display_name'  => 'Feature to enable sms notification to beneficiary for Payouts made via API',
            'documentation' => '',
        ],
        self::DISABLE_DB_PAYOUT_BENE_SMS => [
            'feature'       => self::DISABLE_DB_PAYOUT_BENE_SMS,
            'display_name'  => 'Feature to disable sms notification to beneficiary for Payouts made via Dashboard',
            'documentation' => '',
        ],
        self::CANCEL_SETTLE_TO_BANK => [
            'feature'       => self::CANCEL_SETTLE_TO_BANK,
            'display_name'  => 'Feature to cancel bank settlement',
            'documentation' => '',
        ],
        self::OLD_CUSTOM_SETTL_FLOW => [
            'feature'       => self::OLD_CUSTOM_SETTL_FLOW,
            'display_name'  => 'Feature to enable old custom settlement flow',
            'documentation' => '',
        ],
        self::SPR_DISABLE_METHOD_RESET  => [
            'feature'       => self::SPR_DISABLE_METHOD_RESET,
            'display_name'  => 'Special pricing plan assigned to the merchant',
            'documentation' => '',
        ],
        self::ROUTE_NO_DOC_KYC  => [
            'feature'       => self::ROUTE_NO_DOC_KYC,
            'display_name'  => 'route_no_doc_kyc',
            'documentation' => '',
        ],
        self::ENABLE_MERCHANT_EXPIRY_PL =>[
            'feature'       => self::ENABLE_MERCHANT_EXPIRY_PL,
            'display_name'  => 'Enables merchant to select no expiry option on payment links',
            'documentation' => '',
        ],
        self::ENABLE_MERCHANT_EXPIRY_PP =>[
            'feature'       => self::ENABLE_MERCHANT_EXPIRY_PP,
            'display_name'  => 'Enables merchant to select no expiry option on payment pages',
            'documentation' => '',
        ],
        self::ENABLE_CREATE_OWN_TEMPLATE =>[
            'feature'       => self::ENABLE_CREATE_OWN_TEMPLATE,
            'display_name'  => 'Enables merchant to select create own template option',
            'documentation' => '',
        ],
        self::ENABLE_CUSTOMER_AMOUNT =>[
            'feature'       => self::ENABLE_CUSTOMER_AMOUNT,
            'display_name'  => 'Enables merchant to select dynamic customer amount',
            'documentation' => '',
        ],

        DcsConstants::RefundEnabled => [
            'feature'       => DcsConstants::RefundEnabled,
            'display_name'  => 'Feature to enable Refunds api flow',
            'documentation' => '',
        ],
        DcsConstants::DisableAutoRefund => [
            'feature'       => DcsConstants::DisableAutoRefund,
            'display_name'  => 'Feature to disable Auto Refunds',
            'documentation' => '',
        ],
        DcsConstants::EligibilityEnabled => [
            'feature'       => DcsConstants::EligibilityEnabled,
            'display_name'  => 'Feature to enable Eligibility api flow',
            'documentation' => '',
        ],
        DcsConstants::ShowEmailOnCheckout => [
            'feature'       => DcsConstants::ShowEmailOnCheckout,
            'display_name'  => 'Show email on std/hosted checkout',
            'documentation' => '',
        ],
        DcsConstants::EmailOptionalOnCheckout => [
            'feature'       => DcsConstants::EmailOptionalOnCheckout,
            'display_name'  => 'Email optional on std/hosted checkout',
            'documentation' => '',
        ],
        self::DISABLE_UPI_NUM_CHECKOUT => [
            'feature'       => self::DISABLE_UPI_NUM_CHECKOUT,
            'display_name'  => 'Disable UPI Number feature on std checkout',
            'documentation' => '',
        ],
        self::DISABLE_UPI_NUM_ON_L0 => [
            'feature'       => self::DISABLE_UPI_NUM_ON_L0,
            'display_name'  => 'Disable UPI Number feature in Preferred section (L0 screen) on std checkout',
            'documentation' => '',
        ],
        self::DISABLE_UPI_NUM_ON_L1 => [
            'feature'       => self::DISABLE_UPI_NUM_ON_L1,
            'display_name'  => 'Disable UPI Number feature in UPI section (L1 screen) on std checkout',
            'documentation' => '',
        ],
        self::AUTO_COMM_INV_DISABLED => [
            'feature'       => self::AUTO_COMM_INV_DISABLED,
            'display_name'  => 'Commission invoice Partner auto approval disable',
            'documentation' => '',
        ],
        self::ADMIN_LEAD_PARTNER => [
            'feature'       => self::ADMIN_LEAD_PARTNER,
            'display_name'  => 'onboarded via Admin lead invite flow',
            'documentation' => '',
        ],
        self::ROUTE_PARTNERSHIPS => [
            'feature'       => self::ROUTE_PARTNERSHIPS,
            'display_name'  => 'Enables the partner to create transfer to its linked accounts from its sub-merchants',
            'documentation' => '',
        ],
        self::SUBM_MANUAL_SETTLEMENT => [
            'feature'       => self::SUBM_MANUAL_SETTLEMENT,
            'display_name'  => 'Enables the partner to hold the sub-merchant payments settlements and later release it',
            'documentation' => '',
        ],
        self::QR_CUSTOM_TXN_NAME => [
            'feature'       => self::QR_CUSTOM_TXN_NAME,
            'display_name'  => 'QR Payment custom notes',
            'documentation' => 'qr-codes',
        ],
        DcsConstants::AffordabilityWidgetSet  => [
            'feature'       => DcsConstants::AffordabilityWidgetSet,
            'display_name'  => 'Affordability Widget',
            'documentation' => 'Feature to enable Affordability Widget',
        ],
        // Deprecated this flag. Adding for time being to keep woocommerce & shopify working
        self::AFFORDABILITY_WIDGET => [
            'feature'       => self::AFFORDABILITY_WIDGET,
            'display_name'  => 'Affordability Widget',
            'documentation' => 'Feature to enable Affordability Widget',
        ],
        self::ASSUME_SUB_ACCOUNT => [
            'feature'       => self::ASSUME_SUB_ACCOUNT,
            'display_name'  => 'Assume Sub Account',
            'documentation' => 'Rx merchant can act as a sub-account on top of a master merchant.',
        ],
        self::ASSUME_MASTER_ACCOUNT => [
            'feature'       => self::ASSUME_MASTER_ACCOUNT,
            'display_name'  => 'Assume Master Account',
            'documentation' => 'Rx merchant with a bank account can act as a master account',
        ],
        self::PAYOUT_SERVICE_ENABLED => [
            'feature'       => self::PAYOUT_SERVICE_ENABLED,
            'display_name'  => 'Payouts Service',
            'documentation' => 'To control onboarding to the new payouts service',
        ],
        self::CLOSE_QR_ON_DEMAND => [
            'feature'       => self::CLOSE_QR_ON_DEMAND,
            'display_name'  => 'Close QR On Demand',
            'documentation' => 'Feature to allow the merchant to consume the QR Code close API,',
        ],
        self::WALLET_PAYTM_AUTO_DEBIT => [
            'feature'       => self::WALLET_PAYTM_AUTO_DEBIT,
            'display_name'  => 'Paytm Wallet Link and Pay',
            'documentation' => '',
        ],
        self::ENABLE_APPROVAL_VIA_OAUTH => [
            'feature'       => self::ENABLE_APPROVAL_VIA_OAUTH,
            'display_name'  => 'Enable Approval via OAuth',
            'documentation' => 'Feature to control payout approvals via Authorized OAuth Tokens'
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
        self::DISABLE_COLLECT_CONSENT,
        self::CARD_MANDATE_SKIP_PAGE,
        self::AFFORDABILITY_WIDGET,
        DcsConstants::ShowEmailOnCheckout,
        DcsConstants::EmailOptionalOnCheckout,
        DcsConstants::AffordabilityWidgetSet,
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

    // Ledger related features
    const LEDGER_FEATURES = [
        self::LEDGER_JOURNAL_WRITES,
        self::LEDGER_JOURNAL_READS,
        self::LEDGER_REVERSE_SHADOW,
        self::DA_LEDGER_JOURNAL_WRITES,
        self::PG_LEDGER_JOURNAL_WRITES,
        self::PG_LEDGER_REVERSE_SHADOW
    ];

    // Payout service related features
    const PAYOUT_SERVICE_FEATURES = [
        self::PAYOUT_SERVICE_ENABLED
    ];

    // Payout service idempotency key features
    const PAYOUT_SERVICE_IDEMPOTENCY_KEY_FEATURES = [
        self::IDEMPOTENCY_API_TO_PS,
        self::IDEMPOTENCY_PS_TO_API,
    ];

    const ACCOUNT_SUB_ACCOUNT_FEATURES = [
        self::SUB_VA_FOR_DIRECT_BANKING,
        self::ASSUME_SUB_ACCOUNT,
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

    public const FEATURES_WITHOUT_MERCHANT_AUTHENTICATION = [
        self::ONLY_DS,
        self::REGULAR_TEST_MERCHANT,
        self::OPTIMIZER_ONLY_MERCHANT,
        self::ADMIN_LEAD_PARTNER,
    ];

    // These features can be checked on app level additionally if not enabled at
    // partner level for pure platform partner
    const PARTNER_AND_APP_LEVEL_FEATURES = [
        self::ROUTE_PARTNERSHIPS,
        self::SUBM_MANUAL_SETTLEMENT
    ];
}
