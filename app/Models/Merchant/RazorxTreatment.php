<?php

namespace RZP\Models\Merchant;

final class RazorxTreatment
{
    const HUF_BUSINESS_TYPE = 'huf_business_type';
    const AADHAAR_EKYC_FOR_REG_BUSINESS_TYPES  = 'adharEkyc_for_reg_businessTypes';
    const AADHAAR_EKYC_FOR_TRUST_SOCIETY_NGO   = 'aadharEkyc_for_trust_society_ngo';

    //Razorx treatment constant, whether to make sync call or not

    const GSTIN_SYNC                = "gstin_sync";
    const LLPIN_SYNC                = "llpin_sync";
    const CIN_SYNC                  = "cin_sync";
    const PERSONAL_PAN_SYNC         = "personal_pan_sync";
    const BUSINESS_PAN_SYNC         = "business_pan_sync";
    const BANK_SYNC                 = "bank_sync";
    const AADHAR_FRONT_BACK_SYNC         = "aadhar_front_back_sync";
    const AADHAR_EKYC_SYNC          = "aadhar_ekyc_sync";
    const VOTERS_ID_SYNC            = 'voters_id_sync';
    const PASSPORT_SYNC             = 'passport_sync';
    const BVS_IN_SYNC               = "bvs_in_sync";

    const CORPORATE_PRICING_FUNCTIONALITY = 'CORPORATE_PRICING_FUNCTIONALITY';

    // the ON variant
    const RAZORX_VARIANT_ON     = 'on';

    //Razorx treatment constant, allows system to show friend buy widget to merchant
    const SHOW_FRIENDBUY_WIDGET = "show_friendbuy_widget";

    // Response filed filtering based on role
    const RESPONSE_FIELDS_FILTERING_FOR_ROLES = 'response_fields_filtering_for_roles';

    //Razorx treatment constant, allows system to call bvs for partnership deed verification.
    const AUTO_KYC_PARTNERSHIP = "auto_kyc_partnership";

    //Razorx treatment constant, allows system to call bvs for certificate of incorporation verification.
    const AUTO_KYC_COI = "auto_kyc_coi";

    //Razorx treatment constant, allows system to call bvs for trust society ngo business certificate verification.
    const TRUST_SOCIETY_NGO_BVS_VALIDATION = "trust_society_ngo_bvs_validation";

    //Razorx treatment constant, allows system to call bvs for trust and society autokyc.
    const AUTO_KYC_TRUST_SOCIETY = "auto_kyc_trust_society";

    // Razorx treatment constant, allows merchant to submit support call requests.
    const SUPPORT_CALL = 'support_call';

    // Decide whom to use k8s batch upload job instead of queue worker
    const K8S_BATCH_TREATMENT   = 'k8s-batch-upload';

    // Decide if recon batch to use k8s batch upload job instead of queue worker processing
    const K8S_RECON_BATCH_TREATMENT = 'k8s_recon_batch_upload';

    // Decide whom to send new design mailers
    const MJML_BASED_MAILERS = 'mjml_based_mailers';

    //Control when to enable instant activation of 2.0 products
    const INSTANT_ACTIVATION_2_0_PRODUCTS = 'instant_activation_2_0_products';

    // Decide whom to activate on international payments
    const INTERNATIONAL_ACTIVATIONS = 'international_activations';

    // Razorx treatment constant for whom to forward/redirect to New Batch service.
    const BATCH_SERVICE_PAYMENT_LINK = 'batch_service_payment_link_updated';

    // Razorx treatment constant for which batch validation needs to skip or not.
    const BATCH_SERVICE_SKIP_VALIDATION = 'batch_service_skip_validation';

    const REGISTERED_ONBOARDING_AUTO_KYC = 'registered_onboarding_auto_kyc';

    // Experiment to enable UFH Cloudfront for merchant documents upload
    const PG_ONBOARDING_CLIENT_CLOUDFRONT_EXP = 'pg_onboarding_client_cloudfront_exp';

    const INVOICE_CLOUDFRONT_ONBOARDING ='invoice_cloudfront_onborading';

    const OLD_TO_NEW_IFSC_FOR_MERGED_BANK = 'old_to_new_ifsc_for_merged_bank';

    //
    // This experiment is used for POI verification only
    //
    const BVS_AUTO_KYC = 'bvs_auto_kyc';

    const BVS_AUTO_KYC_OCR           = 'bvs_auto_kyc_ocr';
    const BVS_GSTIN_VALIDATION       = 'bvs_gstin_validation';
    const BVS_CIN_VALIDATION         = 'bvs_cin_validation';
    const BVS_PERSONAL_PAN_OCR       = 'bvs_personal_pan_ocr';
    const BVS_BUSINESS_PAN_OCR       = 'bvs_business_pan_ocr';
    const BVS_SHOP_ESTB_AUTH         = 'bvs_shop_estb_auth';
    const BVS_PENNY_TESTING          = 'bvs_penny_testing';

    // Decides if increased cap for allowed line items in invoice to be used for merchant's invoice.
    const INV_INCREASED_LINE_ITEMS_CAP = 'inv_increased_line_items_cap';

    const CHANGE_QUEUE_BATCH_INVOICE = 'change_queue_batch_invoice';

    const SECOND_FACTOR_AUTH_PROJECT_EXP   = 'second_factor_auth_project';

    const SELLER_APP_PL_BATCH_UPLOAD_EXPERIMENT = 'sellerapp_PL_batch_upload';

    const RENDERING_PREFERENCES_PAYMENT_LINKS = 'rendering_preferences_payment_links';

    // Decides if fund account and contact creation should have duplicate checks
    const X_CONTACT_AND_FUND_ACCOUNT_CREATION = 'x_contact_and_fund_account_creation';

    // Decides whether reject reason has to be sent in webhook
    const PAYOUTS_REJECT_COMMENT_IN_WEBHOOK_FILTER = 'payouts_reject_comment_in_webhook_filter';

    // Decides if the Settlement UX changes are displayed to the merchant
    const SETTLEMENT_UX_REVAMP = 'settlement_ux_revamp';

    // Decides payout channel based on IMPS mode
    const IMPS_MODE_PAYOUT_FILTER = 'imps_mode_payout_filter';

    // Decides payout channel based on NEFT mode
    const NEFT_MODE_PAYOUT_FILTER = 'neft_mode_payout_filter';

    // Decides payout channel based on RTGS mode
    const RTGS_MODE_PAYOUT_FILTER = 'rtgs_mode_payout_filter';

    // Decides payout channel based on UPI mode
    const UPI_MODE_PAYOUT_FILTER = 'upi_mode_payout_filter';

    // Decides payout channel based on amazonpay mode
    const AMAZONPAY_MODE_PAYOUT_FILTER = 'amazonpay_mode_payout_filter';

    // For whitelisting merchants that can access amazon pay wallet payouts in X
    const ENABLE_WALLET_ACCOUNT_AMAZON_PAYOUT = 'rx_enable_amazonpay_wallet_payout';

    // Decides payout channel based on IFT mode
    const IFT_MODE_PAYOUT_FILTER = 'ift_mode_payout_filter';

    // Decides whether or not to display tr attribute in upi_transfer entity
    const UPI_TRANSFER_TR = 'upi_transfer_tr';

    // Onboard merchant on RazorpayX test mode
    const RAZORPAY_X_TEST_MODE_ONBOARDING = 'razorpayx_x_test_mode_onboarding';

    // Onboard merchant on Ledger
    const LEDGER_ONBOARDING = 'ledger_onboarding';

    // Onboard merchant on Ledger with reverse shadow
    const LEDGER_ONBOARDING_REVERSE_SHADOW = 'ledger_onboarding_reverse_shadow';

    // Onboard direct accounting merchant on Ledger with shadow
    const DA_LEDGER_ONBOARDING = 'da_ledger_onboarding';

    // Onboard direct accounting merchant on Ledger with reverse shadow
    const DA_LEDGER_ONBOARDING_REVERSE_SHADOW = 'da_ledger_onboarding_reverse_shadow';

    // Fund transfer request from payout to fts in sync mode
    const PAYOUT_TO_FTS_SYNC_MODE = 'payout_to_fts_sync_mode';

    // New user EmailVerify through OTP
    const EMAIL_VERIFICATION_USING_OTP = 'email_verification_using_otp';

    // Access control to allow pg request after new acl
    const RAZORPAY_X_ACL_DENY_UNAUTHORISED = 'razorpay_x_acl_deny_unauthorised';

    // Allow CA activated merchants to hit X related routes
    const RAZORPAY_X_AUTHORISE_CA_ACTIVATED_MERCHANT_TO_ACCESS_X_PRIVATE_ROUTES = 'razorpay_x_authorise_ca_activated_merchant_to_access_x_private_routes';

    // restrict user to one role per merchant and product
    const RESTRICT_USER_TO_ONE_ROLE_PER_MERCHANT_AND_PRODUCT = 'restrict_user_to_one_role_per_merchant_and_product';

    // Forces ICICI channel when we get channel as yesbank
    const FORCE_ICICI_OVER_YESBANK_FOR_PAYOUTS = 'force_icici_over_yesbank_for_payouts';

    const RAZORPAY_X_ENABLE_YESBANK_PAYOUTS = 'razorpay_x_enable_yesbank_payouts';

    // Experiment to skip payroll payouts in the payouts list/detail view
    const RX_SKIP_PAYROLL_PAYOUTS = 'rx_skip_payroll_payouts';

    // Experiment to skip payroll payouts in the payouts list/detail view
    const RX_UNDO_PAYOUTS_FEATURE = 'rx_undo_payout_feature';

    // Experiment to send login email notification to user
    const USER_LOGIN_EMAIL_NOTIFICATION = 'user_login_email_notification';

    // Block external transaction webhooks for RBL CA
    const BLOCK_EXTERNAL_TRANSACTION_CREATED_WEBHOOK_RBL = 'block_external_transaction_created_webhook_rbl';

    // Check whether trimming is allowed for merchant or not.
    const BLOCKED_MERCHANT_FOR_TRIM_SPACE = 'blocked_merchant_for_trim_space';

    const PAYMENT_TRANSFER_ASYNC = 'payment_transfer_async';

    const EMANDATE_NONZERO_AMOUNT = 'emandate_nonzero_amount';

    // Added to test support of prepaid cards payouts for test merchants.
    // TODO: Remove experiment once testing concludes.
    const PAYOUT_TO_PREPAID_CARDS = 'payout_to_prepaid_cards';

    // To decide whether a merchant without specific Instant Refunds pricing - will have the old default pricing or
    // the new default pricing applied
    const INSTANT_REFUNDS_DEFAULT_PRICING_V1 = 'instant_refunds_default_pricing_v1';

    const PROCESS_VIA_WORKFLOW_SERVICE = 'forward_to_new_workflows_service';

    // Check whether Payout Link Service is up or not.
    // If the feature is mapped to a merchant, the Service is down for that merchant
    const RX_IS_PAYOUT_LINK_SERVICE_DOWN = 'rx_payout_links_inactive';

    // Decides if routes should go to Payout Link MicroService or API
    // If the feature is mapped to a merchant, the routes will go to MicroService
    const RX_PAYOUT_LINK_MICROSERVICE = 'rx_payout_links_ms';

    // To decide if approval workflow is enabled for the merchant (general availability)
    const RX_PAYOUT_LINK_WORKFLOW_GA = 'rx_payout_link_workflow_ga';

    // Check whether skip_workflow_payout_specific feature is allowed for merchant or not.
    const SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE = 'skip_workflow_payout_specific_feature';

    const PUBLIC_KEY_SIGNATURE_GENERATION = 'public_key_signature_generation';

    const PG_ROUTER_ORDER_SHOULD_DISPATCH_TO_QUEUE = 'pg_router_order_should_dispatch_to_queue';

    const QR_CODE_BANK_TRANSFER = 'qr_code_bank_transfer';

    const QR_CODE_CUTOFF_CONFIG = 'qr_code_cutoff_config';

    // experiment for sending UPI links to customers while created missed order payment links
    const PL_MISSED_ORDER_UPI_LINK = 'pl_missed_order_upi_link';

    // experiment for controlling delay seconds for missed order payment link creation
    const PL_MISSED_ORDER_SEND_AFTER_SECONDS = 'pl_missed_order_send_after_seconds';

    // experiment for merchant when trim migration in progress
    const TRIM_MIGRATION_IN_PROGRESS = 'trim_migration_in_progress';

    const PAYOUT_TO_CARDS_VIA_RBL = 'payout_to_cards_via_rbl';

    // experiment for opting out of settlement notification
    const SETTLEMENT_NOTIFICATION_OPT_OUT = 'settlement_notification_opt_out';

    const TOKENIZE_QR_STRING_MPANS = 'tokenize_qr_string_mpans';

    const BANK_TRANSFER_QUEUE = 'bank_transfer_queue';

    // experiment to enable webhooks on route gateway_payment_static_s2scallback_post/gateway_payment_static_s2scallback_get
    const ENABLE_WEBHOOKS = 'enable_webhooks';

    const BULK_PAYOUTS_IMPROVEMENTS_ROLLOUT = 'bulk_payouts_improvements_rollout';

    // experiment to enable whatsapp notifications and also refactoring notifications;
    const WHATSAPP_NOTIFICATIONS = 'whatsapp_notifications';

    // experiment to enable esign aadhar functionality
    const ESIGN_AADHAR_FUNCTIONALITY = 'esign_aadhar_functionality';

    // experiment to enable pushing events to segment
    const SEGMENT_ANALYTICS_FUNCTIONALITY = 'segment_analytics_functionality';

    // experiment to enable instant activations on L1 submit;
    const INSTANT_ACTIVATION_FUNCTIONALITY = 'instant-activations-functionality';

    // experiment to enable shop estb doc verification through OCR;
    const SHOP_ESTABLISHMENT_DOC_VERIFICATION = 'shop_establishment_doc_verification';

    // experiment to enable gst in doc verification through OCR;
    const GST_IN_DOC_VERIFICATION = 'gst_in_doc_verification';

    const POA_VERIFICATION_AUTO_KYC = 'poa_verification_auto_kyc';

    // experiment to skip poa documents if aadhaar esign is done
    const SKIP_POA_DOCUMENT_FUNCTIONALITY = "SKIP_POA_DOCUMENT_FUNCTIONALITY";

    // experiment to enable axis wrapper feature
    const AXIS_WRAPPER_ENABLED = "AXIS_WRAPPER_ENABLED";

    //
    const ORG_LEVEL_2FA_ENFORCED_FUNCTIONALITY = 'org_level_2fa_enforced_functionality';

    // experiment to enable ipAddress-clientId dedupe functionality
    const DEDUPE_FUNCTIONALITY_FOR_CLIENT_IP_ID = 'DEDUPE_FUNCTIONALITY_FOR_CLIENT_IP_ID';

    // experiment to add merchant TnC urls for their website
    const MERCHANT_TNC = 'merchant_tnc';
    const RAZORPAY_TNC = 'rzp_merchant_tnc';

    // experiment to enable whatsapp notifications for settlements
    const WHATSAPP_NOTIFICATIONS_SETTLEMENTS = 'whatsapp_notification_enablement';

    // experiment to enable 2fa for admin dashboard
    const ORG_SECOND_FACTOR_AUTH = 'org_second_factor_auth';

    // release duplicate receipt check in refunds only for Google merchant as of now
    const DUPLICATE_RECEIPT_CHECK = 'duplicate_receipt_check';

    // Check if Offers is enabled for subscription
    const OFFER_ON_SUBSCRIPTION = 'offer_on_subscription';

    const SYSTEM_BASED_NEEDS_CLARIFICATION = 'system_based_needs_clarification';

    const SYSTEM_BASED_NEEDS_CLARIFICATION_FOR_PARTNER = 'system_based_needs_clarification_for_partner';

    const PARTNER_KYC_COMMUNICATION = 'partner_kyc_communication';

    const PARTNER_SUBMERCHANT_INVITE_SMS = 'partner_submerchant_invite_sms';

    // Experiment to verify bank account via karza
    const KARZA_BANK_ACCOUNT_VERIFICATION = 'KARZA_BANK_ACCOUNT_VERIFICATION';

    // some merchants require more attempts to fetch their whole statement. hence special attempt limit should be enabled for them.
    const BANKING_ACCOUNT_STATEMENT_SPECIAL_ATTEMPT_LIMIT = 'banking_account_statement_special_attempt_limit';

    const BANKING_ACCOUNT_STATEMENT_TEMP_RECORDS = 'banking_account_statement_temp_records';

    const API_EMAILS_MAILGUN_DRIVER = 'api_emails_mailgun_driver';

    const RBL_V2_BAS_API_INTEGRATION = 'rbl_v2_bas_api_integration';

    // This is to be used to block VA to VA payouts
    const RX_ALLOW_VA_TO_VA_PAYOUTS = 'rx_allow_va_to_va_payouts';

    const APPS_RISK_CHECK_CREATE_VA = 'apps_risk_check_create_va';

    const GSTIN_SELF_SERVE_V2 = 'gstin_self_serve_v2';

    const SYSTEM_BASED_NEEDS_CLARIFICATION_NOT_MATCHED = 'system_based_needs_clarification_not_matched';

    // to a/b test between offer tile vs subtext for cred
    const CRED_OFFER_SUBTEXT = 'cred_offer_subtext';
    //razorx experiment for using scheduler
    const GATEWAY_SCHEDULER_VERIFY_EXPERIMENT           = 'gateway_scheduler_verify_experiment';
    const GATEWAY_SCHEDULER_TIMEOUT_EXPERIMENT          = 'gateway_scheduler_timeout_experiment';
    // controls %age of the mail to be sent via stork
    const API_STORK_MAIL_PAYMENT_CAPTURE        = 'api_stork_mail_payment_capture';
    const API_STORK_MAIL_PAYMENT_FAILURE        = 'api_stork_mail_payment_failure';
    const API_STORK_MAIL_CONTACT_MOBILE_UPDATED = 'api_stork_mail_contact_mobile_updated';
    const API_STORK_MAIL_CUSTOMER_PAYMENT       = 'api_stork_mail_customer_payment';
    const API_STORK_MAIL_CUSTOMER_INVOICE       = 'api_stork_mail_customer_invoice';

    // will route the traffic slave or master based on the replica
    const SETTLEMENT_TXN_FETCH_TO_SLAVE = 'settlement_transaction_fetch_to_slave';

    const PERFORM_ACTION_ON_WORKFLOW_OBSERVER_DATA = 'perform_action_on_workflow_observer_data';

    const M2M_REWARDS_AB_TESTING = 'M2m_rewards_ab_testing';

    // covid 19 related donation
    const COVID_19_DONATION_SHOW = 'covid_19_donation_show';

    // Experiment to decide whether to send support related notifications on whatsapp
    const WHATSAPP_SUPPORT_NOTIFICATIONS = 'whatsapp_support_notifications';

    const NEEDS_CLARIFICATION_REQUEST_DOCUMENT_NOTIFICATION = 'needs_clarification_request_document_notification';

    // Experiment to decide whether to send support related notifications on sms
    const SMS_SUPPORT_NOTIFICATIONS = 'sms_support_notifications';

    const SHOW_CREATE_TICKET_POPUP = 'show_create_ticket_popup';

    const NON_TPV_REFUNDS_VIA_X = 'non_tpv_refunds_via_x';

    /**
     * Experiment to indicate if a payment should go via capture queue for Master Card Network
     */
    const PAYMENT_GATEWAY_CAPTURE_ASYNC_MC = 'payment_gateway_capture_asyc_mc';

    /**
     * Experiment to indicate if a payment should go via capture queue for Visa and other n/ws like Amex,Diner etc.
     */
    const PAYMENT_GATEWAY_CAPTURE_ASYNC_OTHER_NETWORKS = 'payment_gateway_capture_async_other_networks';

    // Experiment to have refunds created directly on scrooge based on merchant id
    const MERCHANTS_REFUND_CREATE_V_1_1 = 'merchants_refund_create_v1.1';

    // Experiment to enable queued payouts creation via payouts service
    const ENABLE_QUEUED_PAYOUTS_VIA_PAYOUTS_SERVICE = 'enable_queued_payouts_via_payouts_service';

    // Experiment to enable on_hold payouts creation via payouts service
    const ENABLE_ON_HOLD_PAYOUTS_VIA_PAYOUTS_SERVICE = 'enable_on_hold_payouts_via_payouts_service';

    const ROUTE_ORDER_TO_PG_ROUTER = "route_order_to_pg_router";

    //Experiment to allow mtu coupon code application
    const MTU_COUPON_CODE = 'mtu_coupon_code';

    const PARTNER_QR_CODE_FEATURE_OVERRIDE = 'partner_qr_code_feature_override';

    const IGNORE_INDEX_IN_TRANSACTIONS_FETCH = 'ignore_index_in_transactions_fetch_2';

    const GATEWAY_BALANCE_FETCH_V2 = 'gateway_balance_fetch_v2';

    const BAS_FETCH_RE_ARCH = 'bas_fetch_re_arch';

    // Experiment to send merchant downtimes to checkout and in fetch api
    const SEND_MERCHANT_DOWNTIMES           = 'send_merchant_downtimes';

    // Experiment to save transaction app urls in merchant business detail
    const SAVE_TXN_APP_URLS = 'save_txn_app_urls';

    // Experiment for transfers state machine.
    const ROUTE_TRANSFER_STATE = 'route_transfer_state';

    // Experiment to send looker link with downtimes notifications to slack
    const DOWNTIMES_LOOKER_TO_SLACK = 'downtime_looker_to_slack';

    // Experiment to send create validation metadata to BVS
    const BVS_CREATE_VALIDATION_METADATA = 'BVS_CREATE_VALIDATION_METADATA';

    // Experiment to send manual verification data to BVS
    const BVS_MANUAL_VERIFICATION_DATA = 'BVS_MANUAL_VERIFICATION_DATA';

    // Experiment for removal of extra fields during onboarding
    const LITE_ONBOARDING = 'lite_onboarding';

    // Experiment for removal of extra fields during onboarding
    const UPDATED_LITE_ONBOARDING = 'updated_lite_onboarding';

    // Experiment to send manual verification data to BVS
    const HARVESTER_SEGREGATE_QUERIES = 'HARVESTER_SEGREGATE_QUERIES';

    //Experiment for removal of extra fields in payment response
    const DISALLOW_ORG_DATA_IN_RESPONSE = 'disallow_org_data_in_response';

    // Experiment for sending auth header for Stores
    const KEYLESS_HEADER_STORES = 'keyless_header_stores';

    // Experiment for sending auth header for Payout Link Pages
    const KEYLESS_HEADER_POUTLK = 'keyless_header_poutlk';

    //Experiment for showing status details to selected merchant
    const ENABLE_STATUS_DETAILS_FEATURE = 'enable_status_details_feature';

    //Experiment for showing status details in timeline view on dashboard
    const STATUS_DETAILS_TIMELINE_VIEW = 'status_details_timeline_view';

    // Experiment to block customer prefill on authlink checkout
    const BLOCK_CUSTOMER_PREFILL_IN_AUTHLINK = 'block_customer_prefill_in_authlink';

    // Experiment for RX Rearch (Ledger <> RX integration)
    const RX_REARCH_TIDB_EXPERIMENT = 'rx_rearch_fetch_tidb';

    //Experiment for enabling dcc on various libraries
    const DCC_ON_INTERNATIONAL = 'dcc_on_international';

    // experiment to send uploaded signed form nach payment in fetch token api
    const SEND_NACH_SIGNED_FORM_TO_MERCHANT_IN_RESPONSE_AUTHLINK = 'send_nach_signed_form_to_merchant_in_response_authlink';

    //Experiment flag for reset password using sms
    const RESET_PASSWORD_USING_SMS = 'reset_password_using_sms';

    //Experiment flag for sending events to segment
    const SEND_EVENTS_TO_SEGMENT = 'send_events_to_segment';

    //Experiment to disable statement fetch for merchants
    const DISABLE_STATEMENT_FETCH = 'disable_statement_fetch';

    // Experiment to block pan details in html code
    const BLOCK_PAN_DETAIL_IN_AUTHLINK_HTML = 'block_pan_detail_in_authlink_html';

    // Experiment to pass unused rejected tokens along with regular tokens in fetchTokens api call
    const PASS_REJECTED_UNUSED_TOKENS = 'pass_rejected_unused_tokens';

    // Experiment to disable status update going to payout service via api
    // payout service will be consuming status independently from kafka
    const DISABLE_STATUS_UPDATE_TO_PAYOUT_SERVICE = 'disable_status_update_to_payout_service';

    // razorx treatment for fetch from scrooge service
    const ENTITY_RELATIONAL_LOAD_FROM_SCROOGE = 'entity_relational_load_from_scrooge';

    // Razorx treatment constant to send a single request to bvs for validating aadhaar
    // document, rather than sending a single one.
    const AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION = "aadhaar_front_and_back_joint_validation";

    // Experiment to use the flow in which there is improvement in GET - /submerchants latency
    const SUBMERCHANTS_FETCH_API_LATENCY_IMPROVE = 'submerchants_fetch_api_latency_improve';

    // Experiment to control payment process through actual card number/tokenised card number for tokenised cards
    const PAYMENT_PROCESS_THROUGH_TOKENISED_CARD = 'payment_process_through_tokenised_card';

    // Experiment to cache terminals for bank Transfer
    const SMART_COLLECT_TERMINAL_CACHING = 'smart_collect_terminal_caching';

    /** @var string Experiment to deprecate tos_acceptance field from /accounts api */
    const IGNORE_TOS_ACCEPTANCE = 'ignore_tos_acceptance';
    /** @var string Experiment to control the provisioning of network tokens for global saved cards. */
    public const PROVISION_GLOBAL_NETWORK_TOKEN = 'provision_global_network_token';
}
