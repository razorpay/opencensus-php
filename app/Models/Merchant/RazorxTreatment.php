<?php

namespace RZP\Models\Merchant;

final class RazorxTreatment
{
    const GIFU_CUSTOM = 'gifu_custom';

    const WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION         = 'WEBSITE_ADHERENCE_WHATSAPP_COMMUNICATION';

    const EDUCATION_OTHERS_BUSINESS_TYPE = 'EDUCATION_OTHERS_BUSINESS_TYPE';
    const HUF_BUSINESS_TYPE              = 'huf_business_type';
    const MAILMODO_L1_FORM_EMAIL_TRIGGER = 'mailmodo_l1_form_email_trigger';
    const PAR_ASYNC_FOR_CARD_FINGERPRINT = 'par_async_for_card_fingerprint';

    const DISABLE_RZP_TOKENISED_PAYMENT        = 'disable_rzp_tokenised_payment';

    const EMANDATE_NET_REVENUE_IMPROVEMENT  = 'emandate_net_revenue_improvement';

    //Razorx treatment constant, whether to make sync call or not

    const GSTIN_SYNC                = "gstin_sync";
    const LLPIN_SYNC                = "llpin_sync";
    const CIN_SYNC                  = "cin_sync";
    const PERSONAL_PAN_SYNC         = "personal_pan_sync";
    const BUSINESS_PAN_SYNC         = "business_pan_sync";
    const BANK_SYNC                 = "bank_sync";
    const AADHAR_FRONT_BACK_SYNC    = "aadhar_front_back_sync";
    const AADHAR_EKYC_SYNC          = "aadhar_ekyc_sync";
    const VOTERS_ID_SYNC            = 'voters_id_sync';
    const PASSPORT_SYNC             = 'passport_sync';
    const BVS_IN_SYNC               = "bvs_in_sync";

    // the ON variant
    const RAZORX_VARIANT_ON     = 'on';

    //Razorx treatment constant, allows system to show friend buy widget to merchant
    const SHOW_FRIENDBUY_WIDGET = "show_friendbuy_widget";

    //Razorx treatment constant, allows system to send new bu namespace to vault service
    const VAULT_BU_NAMESPACE_MIGRATION  = 'vault_bu_namespace_migration';

    // razorx treatment constant, allows saving and fetching card meta data in vault service temporarily
    const VAULT_BU_NAMESPACE_CARD_METADATA_VARIANT = 'vault_bu_namespace_card_metadata_variant';

    //Razorx treatment constant, allows system to save the cards data temporarily in vault db
    const VAULT_TEMP_SAVE  = 'vault_temp_save';

    //Razorx treatment constant , allows system to control not saving of metadata for non exempted cases

    const STORE_EMPTY_VALUE_FOR_NON_EXEMPTED_CARD_METADATA  = 'store_empty_value_for_non_exempted_card_metadata';

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

    // Razorx treatment constant, allows system to call ledger service.
    const LEDGER_ONBOARDING_PG_MERCHANT = 'ledger_onboarding_pg_merchant';

    // Razorx treatment constant, for onboarding the order update via order outbox.
    const ORDER_OUTBOX_ONBOARDING = 'order_outbox_onboarding';

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

    // Onboard merchant on Ledger with reverse shadow
    const LEDGER_ONBOARDING_REVERSE_SHADOW = 'ledger_onboarding_reverse_shadow';

    // Expt to cache ledger account id for a merchant for RX transaction Fetch API
    const LEDGER_TIDB_MERCHANT_ACCOUNT_ID_CACHE = 'ledger_tidb_merchant_account_id_cache';

    // Onboard direct accounting merchant on Ledger with shadow
    const DA_LEDGER_ONBOARDING = 'da_ledger_onboarding';

    // Onboard direct accounting merchant on Ledger with reverse shadow
    const DA_LEDGER_ONBOARDING_REVERSE_SHADOW = 'da_ledger_onboarding_reverse_shadow';

    // Expt to use transactions table if count is 1
    const LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE = 'ledger_reverse_shadow_latest_txn_balance';

    // Fetch balance from ledger TiDB
    const LEDGER_BALANCE_FETCH_FROM_TIDB = 'ledger_balance_fetch_from_tidb';

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

    // internal payout via payout service
    const INTERNAL_PAYOUT_VIA_PS = 'internal_payout_via_ps';

    // Experiment to skip payroll payouts in the payouts list/detail view
    const RX_UNDO_PAYOUTS_FEATURE = 'rx_undo_payout_feature';

    // status reason map via PS
    const STATUS_REASON_MAP_VIA_PS = 'status_reason_map_via_ps';

    const BATCH_PAYOUTS_SUMMARY_EMAIL = 'batch_payouts_summary_email';

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

    // To skip generating QR image in the QR create flow in order to reduce latency.
    const QR_CODE_GENERATE_IMAGE = 'qr_code_generate_image';

    const QR_CODE_CUTOFF_CONFIG = 'qr_code_cutoff_config';

    const QR_ON_EMAIL = 'qr_on_email';

    const QR_PAYMENT_AUTO_CAPTURE_FOR_CLOSED_QR = 'qr_payment_auto_capture_for_closed_qr';

    // experiment for merchant when trim migration in progress
    const TRIM_MIGRATION_IN_PROGRESS = 'trim_migration_in_progress';

    const PAYOUT_TO_CARDS_VIA_RBL = 'payout_to_cards_via_rbl';

    // experiment for opting out of settlement notification
    const SETTLEMENT_NOTIFICATION_OPT_OUT = 'settlement_notification_opt_out';

    const TOKENIZE_QR_STRING_MPANS = 'tokenize_qr_string_mpans';

    // experiment to enable webhooks on route gateway_payment_static_s2scallback_post/gateway_payment_static_s2scallback_get
    const ENABLE_WEBHOOKS = 'enable_webhooks';

    const BULK_PAYOUTS_IMPROVEMENTS_ROLLOUT = 'bulk_payouts_improvements_rollout';

    // experiment to enable whatsapp notifications and also refactoring notifications;
    const WHATSAPP_NOTIFICATIONS = 'whatsapp_notifications';

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

    // experiment to enable ipAddress-clientId dedupe functionality
    const DEDUPE_FUNCTIONALITY_FOR_CLIENT_IP_ID = 'DEDUPE_FUNCTIONALITY_FOR_CLIENT_IP_ID';

    // experiment to add merchant TnC urls for their website
    const MERCHANT_TNC = 'merchant_tnc';
    const RAZORPAY_TNC = 'rzp_merchant_tnc';

    // experiment to enable whatsapp notifications for settlements
    const WHATSAPP_NOTIFICATIONS_SETTLEMENTS = 'whatsapp_notification_enablement';

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

    // for creating upi recurring auth link via batch upload pick up as presented frequency by default
    const UPI_AUTH_LINK_FREQUENCY_AS_PRESENTED_DEFAULT = 'upi_auth_link_frequency_as_presented_default';

    const BANKING_ACCOUNT_STATEMENT_TEMP_RECORDS = 'banking_account_statement_temp_records';

    const BANKING_ACCOUNT_STATEMENT_FETCH_DEDUP = 'banking_account_statement_fetch_dedup';

    const API_EMAILS_MAILGUN_DRIVER = 'api_emails_mailgun_driver';

    const RBL_V2_BAS_API_INTEGRATION = 'rbl_v2_bas_api_integration';

    const SKIP_NON_DS_CHECK              = 'skip_non_ds_check';

    const BANK_TRANSFER_DISABLE_GATEWAY = 'bank_transfer_disable_gateway';
    // This is to be used to block VA to VA payouts
    const RX_ALLOW_VA_TO_VA_PAYOUTS = 'rx_allow_va_to_va_payouts';

    const RX_FEE_RECOVERY_CONTROL_ROLL_OUT = 'rx_fee_recovery_control_roll_out';

    const APPS_RISK_CHECK_CREATE_VA = 'apps_risk_check_create_va';

    const BT_RBL_CREATE_VIRTUAL_ACCOUNT = 'bt_rbl_create_virtual_account';
    const SC_STOP_QR_AS_RECEIVER_FOR_VIRTUAL_ACCOUNT = 'sc_stop_qr_as_receiver_for_virtual_account';

    const GSTIN_SELF_SERVE_V2 = 'gstin_self_serve_v2';

    const SYSTEM_BASED_NEEDS_CLARIFICATION_NOT_MATCHED = 'system_based_needs_clarification_not_matched';

    // to a/b test between offer tile vs subtext for cred
    const CRED_OFFER_SUBTEXT = 'cred_offer_subtext';
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

    const PERFORM_ACTION_ON_INTERNAL_ACTIVATION_STATUS_CHANGE = 'perform_action_on_internal_activation_status_change';

    // covid 19 related donation
    const COVID_19_DONATION_SHOW = 'covid_19_donation_show';

    // Experiment to decide whether to send support related notifications on whatsapp
    const WHATSAPP_SUPPORT_NOTIFICATIONS = 'whatsapp_support_notifications';

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


    /**
     * Experiment to indicate if a payment should go via capture queue for fulcrum gateway
     */
    const PAYMENT_GATEWAY_CAPTURE_ASYNC_FULCRUM = 'payment_gateway_capture_async_fulcrum';

    // Experiment to have refunds created directly on scrooge based on merchant id
    const MERCHANTS_REFUND_CREATE_V_1_1 = 'merchants_refund_create_v1.1';

    // Experiment to have refunds created directly on scrooge based on merchant id
    const NON_MERCHANT_REFUND_CREATE_V_1_1 = 'non_merchant_refund_create_v1.1';

    // Experiment to have batch refunds created directly on scrooge
    const BATCH_REFUND_CREATE_V_1_1 = 'batch_refund_create_v1.1';

    // Experiment to enable queued payouts creation via payouts service
    const ENABLE_QUEUED_PAYOUTS_VIA_PAYOUTS_SERVICE = 'enable_queued_payouts_via_payouts_service';

    // Experiment to enable on_hold payouts creation via payouts service
    const ENABLE_ON_HOLD_PAYOUTS_VIA_PAYOUTS_SERVICE = 'enable_on_hold_payouts_via_payouts_service';

    const ROUTE_ORDER_TO_PG_ROUTER = "route_order_to_pg_router";

    const ROUTE_ORDER_TO_PG_ROUTER_REVERSE = "route_order_to_pg_router_reverse";

    const ROUTE_CONVENIENCE_FEE_ORDER_TO_PG_ROUTER = "route_convenience_fee_order_to_pg_router";

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

    const BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE = 'bulk_payout_ca_va_segregation_payouts_service';

    const WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE = 'workflow_action_with_db_dual_write_payouts_service';

    // Experiment to send create validation metadata to BVS
    const BVS_CREATE_VALIDATION_METADATA = 'BVS_CREATE_VALIDATION_METADATA';

    // Experiment to send manual verification data to BVS
    const BVS_MANUAL_VERIFICATION_DATA = 'BVS_MANUAL_VERIFICATION_DATA';

    // Experiment for removal of extra fields during onboarding
    const LITE_ONBOARDING = 'lite_onboarding';

    // Experiment for removal of extra fields during onboarding
    const UPDATED_LITE_ONBOARDING = 'updated_lite_onboarding';

    const HARVESTER_SEGREGATE_QUERIES = 'HARVESTER_SEGREGATE_QUERIES';

    const HARVESTER_V2_MIGRATION = 'HARVESTER_V2_MIGRATION';

    const DRUID_MIGRATION = 'DRUID_MIGRATION';

    const WHATCMS_EXPERIMENT = 'WHATCMS_EXPERIMENT';

    const ESIGN_AADHAR_VERIFICATION = 'ESIGN_AADHAR_VERIFICATION';

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

    //Experiment for enabling dcc on various libraries
    const DCC_ON_INTERNATIONAL = 'dcc_on_international';

    //Experiment for making a sync call to bank api for fetching balance
    const SYNC_CALL_FOR_FRESH_BALANCE = 'sync_call_for_fresh_balance';

    // experiment to send uploaded signed form nach payment in fetch token api
    const SEND_NACH_SIGNED_FORM_TO_MERCHANT_IN_RESPONSE_AUTHLINK = 'send_nach_signed_form_to_merchant_in_response_authlink';

    // experiment to remove duplicate recurring saved card token in checkout page
    const DEDUP_RECURRING_SAVED_CARD_TOKEN = 'dedup_recurring_saved_card_token';

    //Experiment flag for reset password using sms
    const RESET_PASSWORD_USING_SMS = 'reset_password_using_sms';

    //Experiment flag for sending events to segment
    const SEND_EVENTS_TO_SEGMENT = 'send_events_to_segment';

    //Experiment to disable statement fetch for merchants
    const DISABLE_STATEMENT_FETCH = 'disable_statement_fetch';

    //Experiment to switch to missing statements insertion optimisation logic
    const OPTIMISE_INSERTION_LOGIC = 'optimise_insertion_logic';

    //Experiment to choose gateway balance in case of CA flows
    const USE_GATEWAY_BALANCE = 'use_gateway_balance';

    // Experiment to use secure context for OTP generation
    const SECURE_OTP_CONTEXT = 'secure_otp_context';

    // Experiment to add request metaData in FTS request for MasterCard Send
    const ENABLE_MCS_TRANSFER = 'enable_mcs_transfer';

    // Experiment to block pan details in html code
    const BLOCK_PAN_DETAIL_IN_AUTHLINK_HTML = 'block_pan_detail_in_authlink_html';

    // Experiment to pass unused rejected tokens along with regular tokens in fetchTokens api call
    const PASS_REJECTED_UNUSED_TOKENS = 'pass_rejected_unused_tokens';

    // razorx treatment for fetch from scrooge service
    const ENTITY_RELATIONAL_LOAD_FROM_SCROOGE = 'entity_relational_load_from_scrooge';
    const ENTITY_RELATIONAL_LOAD_FROM_SCROOGE_NON_SHADOW = 'entity_relational_load_from_scrooge_non_shadow';

    // Razorx treatment constant to send a single request to bvs for validating aadhaar
    // document, rather than sending a single one.
    const AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION = "aadhaar_front_and_back_joint_validation";

    // Experiment to use the flow in which there is improvement in GET - /submerchants latency
    const SUBMERCHANTS_FETCH_API_LATENCY_IMPROVE = 'submerchants_fetch_api_latency_improve';

    // Experiment to control Emandate Nach payments processing through async worker queues
    const EMANDATE_ASYNC_PAYMENT_PROCESSING_ENABLED = 'emandate_async_payment_processing_enabled';

    // Experiment to control Emandate Nach payments processing for async balance merchants through worker queues
    const EMANDATE_ASYNC_PAYMENT_WITH_ASYNC_BAL_ENABLED = 'emandate_async_payment_with_async_balance';

    // Experiment to cache terminals for bank Transfer
    const SMART_COLLECT_TERMINAL_CACHING = 'smart_collect_terminal_caching';

    // Experiment to accept new axis UMRN in mandate migration
    const ACCEPT_NEW_AXIS_UMRN_MANDATE_MIGRATION = 'accept_new_axis_umrn_mandate_migration';

    // Experiment to toggle unhappy flow handling for tokenisation failure in recurring
    const RECURRING_TOKENISATION_UNHAPPY_FLOW_HANDLING = 'recurring_tokenisation_unhappy_flow_handling';

    // Experiment to toggle tokenisation for recurring tokens
    const RECURRING_TOKENISATION = 'recurring_tokenisation';

    // Experiment to control card IIN usage for recurring tokenisation cases
    const RECURRING_TOKENISATION_NOT_USING_ACTUAL_CARD_IIN = 'recurring_tokenisation_not_using_actual_card_iin';

    // Experiment to toggle block notifying MandateHub after recurring tokenisation
    const BLOCK_HUB_REPORT_AFTER_ASYNC_RECURRING_TOKENISATION = 'block_hub_report_after_async_recurring_tokenisation';

    // Experiment to control recurring auto payment process through actual card number/tokenised card number for tokenised cards
    const RECURRING_SUBSEQUENT_THROUGH_TOKENISED_CARD = 'recurring_subsequent_through_tokenised_card';

    /** Experiment to enable recurring transaction for Rupay Cards Merchant Level Control*/
    const RECURRING_THROUGH_RUPAY_CARD_MID = 'recurring_through_rupay_card_mid';

    /** Experiment to enable recurring transaction for Rupay Cards IIN Level Control*/
    const RECURRING_THROUGH_RUPAY_CARD_IIN = 'recurring_through_rupay_card_iin';

    /** @var string Experiment to deprecate tos_acceptance field from /accounts api */
    const IGNORE_TOS_ACCEPTANCE = 'ignore_tos_acceptance';

    // Refund source fallback is enabled on merchant or not
    const REFUND_FALLBACK_ENABLED_ON_MERCHANT = 'refund_source_fallback_enabled';

    // Experiment to use new PG Invoice template
    const MERCHANT_PG_INVOICE_V2 = 'merchant_pg_invoice_v2';

    //Experiment to validate Urls, emails, html tags inclusions in Linked Account name, business_name
    public const URL_VALIDATION_FOR_LINKED_ACCOUNT_NAME = 'url_validation_for_linked_account_name';

    /** @var string Experiment to use passport params for authentication.(required for removal of OAuth Library
     * oauth verification which is redundant)
     */
    const USE_EDGE_PASSPORT_FOR_AUTH = 'use_edge_passport_for_auth';

    // Experiment to control sync/async call on scrooge
    public const SCROOGE_SYNC_CALL = 'scrooge_sync_call';

    const PAYMENT_METHOD_CONFIG_UPDATE = 'payment_method_config_update';

    // Razorx treatment constant  for which validating multiple sheets for recurring charge axis;
    const DUPLICATE_SHEET_VALIDATION_BATCH = 'duplicate_sheet_validation_batch';

    const ASYNC_TOKEN_MIGRATION = 'async_token_migration';

    const NON_RZP_TOKENISED_IR = "non_rzp_tokenised_ir";

    /** Experiment to enable custom access control */
    public const RX_CUSTOM_ACCESS_CONTROL_ENABLED = "rx_custom_access_control_enabled";

    public const RX_CUSTOM_ACCESS_CONTROL_DISABLED = "rx_custom_access_control_disabled";

    public const RX_PAYOUT_RECEIPT_BENE_NOTIFICATION = "rx_payout_receipt_bene_notification";

    public const PANSOURCE_CHANGE_RUPAY = 'pansource_change_rupay';

    public const DELETE_CARD_METADATA_AFTER_RECONCILIATION = 'delete_card_metadata_after_reconciliation';

    public const DELETE_CARD_METADATA_AFTER_RECONCILIATION_FOR_PAYSECURE_AND_FULCRUM = 'delete_card_metadata_after_reconciliation_for_paysecure_and_fulcrum';

    public const PANSOURCE_CHANGE_MIGRATION_RUPAY = 'pansource_change_migration_rupay';

    public const  SMARTCOLLECT_SERVICE_BANK_TRANSFER = 'smartcollect_service_bank_transfer';

    public const SMARTCOLLECT_SERVICE_QR_PAYMENTS_CALLBACK = 'smartcollect_service_qr_payments_callback';

    /* Experiment to enable self serve workflow */
    public const RX_SELF_SERVE_WORKFLOW = "rx_self_serve_workflow";

    // Experiment to ramp up the refund credits fetch mechanism with locking
    public const REFUND_CREDITS_WITH_LOCK = 'refund_credits_with_lock';

    // Experiment to ramp up the disable amount credits for greater than txn amount
    public const DISABLE_AMOUNT_CREDITS_FOR_GREATER_THAN_TXN_AMOUNT = 'disable_amount_credits_for_greater_than_txn_amount';

    /** This is a mock experiment and used to control the test suite behaviour **/
    public const DISABLE_CAC_FOR_GITHUB_TEST_SUITES = "disable_cac_for_github_test_suites";

    public const EMAIL_EASY_ONBOARDING_SIGNUP = 'email_easy_onboarding_signup';

    public const REFUND_AMOUNT_VALIDATION_FROM_REFUND_ENTITY = 'refund_amt_validation_from_refund_entity';

    // This is for upi autopay subsequent payment if capture setting time is less then 36 hours
    public const DEFAULT_CAPTURE_SETTING_CONFIG_UPI_AUTOPAY = "default_capture_setting_config_upi_autopay";

    // This is for czrd recurring subsequent payment capture setting
    public const DEFAULT_CAPTURE_SETTING_CONFIG_CARD_RECURRING = "default_capture_setting_config_card_recurring";

    // This is to show the feedback collection popup for npci
    public const ALLOW_NPCI_FEEDBACK_POPUP = "allow_npci_feedback_popup";

    // This is to show the feedback collection popup for npci
    public const ALLOW_NPCI_FEEDBACK_POPUP_EMANDATE_FAILURE = "allow_npci_feedback_popup_emandate_failure";

    // This is to get the query data via WDA for emandate
    public const FETCH_PENDING_EMANDATE_REGISTRATION_FROM_WDA = "fetch_pending_emandate_registration_from_wda";

    public const CARD_MANDATE_CORRECT_DETAILS_FETCH = "card_mandate_correct_details_fetch";

    // Experiment to migrate yes bank debit file batch processing
    public const BATCH_SERVICE_ENACH_NPCI_NETBANKING_MIGRATION = "batch_service_enach_npci_netbanking_migration";

    // Experiment to migrate rbl bank debit file batch processing
    public const BATCH_SERVICE_EMANDATE_DEBIT_ENACH_RBL_MIGRATION = "batch_service_emandate_debit_enach_rbl_migration";

    // Experiment to migrate sbi bank debit file batch processing
    public const BATCH_SERVICE_EMANDATE_DEBIT_SBI_MIGRATION = "batch_service_emandate_debit_sbi_migration";

    // Experiment to migrate merchant_risk_fact to datalake
    public const MERCHANT_RISK_FACT_MIGRATION = "merchant_risk_fact_migration";

    // Experiment to enable Whatsapp Notification for Risk chargeback intimation
    const RISK_WHATSAPP_NOTIFICATION = 'risk_whatsapp_notification';

    // Experiment to show billing label over merchant label for billing label field. Slack : https://razorpay.slack.com/archives/C7WEGELHJ/p1660714831872219
    const SHOW_BILLING_LABEL_OVER_MERCHANT_LABEL_FOR_RECURRING = 'show_billing_label_over_merchant_label_for_recurring';

    // Experiment to use card / token for sihub validation calls
    const SIHUB_VALIDATION_FORCE_CARD_INSTRUMENT_FIRST = 'sihub_validation_force_card_instrument_first';

    //Experiment to disable card flow for sihub post tokenization deadline
    const SIHUB_DISABLE_CARD_FLOW_POST_TOKENIZATION = 'sihub_disable_card_flow_post_tokenization';

    //Experiment used to control for the flow of create account API for performance analysis
    const CREATE_ACCOUNT_API_PERFORMANCE_ANALYSIS = 'create_account_api_performance_analysis';

    const TRIGGER_NEW_ONBOARDING_ESCALATION_FLOW = 'trigger_new_onboarding_escalation_flow';

    //Experiment used to control whether routes specified in $bankingDisabledRoutes should block banking requests or not
    const BLOCK_BANKING_REQUESTS = 'block_banking_requests';

    const NC_REVAMP = 'nc_revamp';

    //Experiment used to ramp up requests for edge url created for scrooge
    const SCROOGE_EDGE_MIGRATION = 'scrooge_edge_migration';

    const PP_MAGIC_SETTING = 'pp_magic_setting';


    //Experiment used to control whether requests route to DCS or NOT
    const DCS_EDIT_ENABLED = 'dcs_edit_enabled';

    //Experiment used to control whether aggregate read requests route to DCS or NOT
    const DCS_AGGREGATE_READ_ENABLED = 'dcs_aggregate_read_enabled';

    //Experiment used to control whether requests route to DCS or NOT
    const DCS_ENABLED_NEW_USECASE = 'dcs_new_usecases_enabled';

    const DEDICATED_TERMINAL_QR_CODE = 'dedicated_terminal_qr_code';

    const MIGRATE_TO_NEW_BEAM_PUSH_URL = 'migrate_to_new_beam_push_url';

    const RBL_CA_USE_NEW_STATE_MACHINE = 'rbl_ca_use_new_state_machine';

    // Experiment to migrate disputes routes from API to disputes service
    const DISPUTES_DECOMP = 'disputes_decomp';

    // Experiment to enable dual writes on dispute service
    const DISPUTES_DUAL_WRITE = 'disputes_dual_write';

    // Experiment to enable dual writes on dispute service in shadow mode
    const DISPUTES_DUAL_WRITE_SHADOW_MODE = 'disputes_dual_write_shadow_mode';

    // Experiment to migrate shield international traffic to separate pods
    const SHIELD_INTL_POD = 'shield_intl_pod';

    // Experiment to use card number from input to use dummy cvv in payment
    const USE_DETECT_NETWORK_FOR_DUMMY_CVV = 'use_detect_network_for_dummy_cvv';

    //Experiment to remove invalid filters in the terminals proxy calls
    const REMOVE_GET_TERMINALS_PROXY_INVALID_FILTERS = "remove_get_terminals_proxy_invalid_filters";

    // Experiment to disable upi terminal
    const DISABLE_UPI_TERMINAL = "disable_upi_terminal";

    /** Experiment used check whether mapns to be de-tokenized or not **/
    const DETOKENIZE_MPANS = "detokenize_mpans";

    // Experiment to use old Pricing plan for upi autopay
    const UPI_AUTOPAY_PRICING_BLACKLIST = 'upi_autopay_pricing_blacklist';

    //Experiment of susbcription other frequency changes
    public const UPI_AUTOPAY_CORRECT_FREQUENCY_FETCH = "upi_autopay_correct_frequency_fetch";

    const ALLOW_CC_ON_UPI_PRICING = 'allow_cc_on_upi_pricing';

    // Experiment to enable async bulk approval or not
    const PAYOUT_BULK_APPROVE_ASYNC = 'payout_bulk_approve_async';

    //Experiment to create dedicated UPI terminal
    const UPI_DEDICATED_TERMINAL = 'upi_dedicated_terminal';

    // Unexpected payment refund delay to T+1
    const UNEXPECTED_PAYMENT_REFUND_DELAY = 'unexpected_payment_refund_delay';

    // Experiment to ramp up international refunds
    const SCROOGE_INTERNATIONAL_REFUND = 'scrooge_international_refund';

    // Experiment to push payout attachment email job to SQS instead of metro
    const PAYOUT_ATTACHMENT_EMAIL_VIA_SQS = 'payout_attachment_email_via_sqs';

    // Experiment to use UPI Autopay Promo Intent flow instead of normal checkout flow for authlinks
    const UPI_AUTOPAY_PROMOTIONAL_INTENT = 'upi_autopay_promotional_intent';

    // Partner bank hold payouts experiment
    const PARTNER_BANK_ON_HOLD_PAYOUT = 'partner_bank_on_hold_payout';

    const UPI_AUTOPAY_REVOKE_PAUSE_TOKEN = 'upi_autopay_revoke_pause_token';

    // Experiment to enable mandate non-revokable
    const UPI_AUTOPAY_REVOKABLE_FEATURE = 'UPI_AUTOPAY_REVOKABLE_FEATURE';

    // Experiment to increase debit retries for merchants
    const UPI_AUTOPAY_INCREASE_DEBIT_RETRIES = 'upi_autopay_increase_debit_retries';

    const DISABLE_QR_CODE_ON_DEMAND_CLOSE = 'disable_qr_code_on_demand_close';

    // If true, it will select Optimizer mandate hub for card recurring payments.
    const ALLOW_OPTIMIZER_CARD_MANDATE_HUB = 'allow_optimizer_card_mandate_hub';

    /**
     * Razorx flag to enable capture settings for optimizer merchants overriding the Direct settlement capture flow
     */
    const ENABLE_CAPTURE_SETTINGS_FOR_OPTIMIZER = 'enable_capture_settings_for_optimizer';

    // Expt to handle non terminal payouts after migration
    const NON_TERMINAL_MIGRATION_HANDLING       = 'non_terminal_migration_handling';

    // Experiment for sending user details to getsimpl
    const SEND_USER_DETAILS_TO_GETSIMPL         = 'send_user_details_to_getsimpl';

    // Experiment to handle PS fund account optimisation
    const PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD = 'ps_fund_account_consume_from_payload';

    /**
     * Razorx flag to enable timeout of upi collect payment with input expiry time
     */
    const ENABLE_TIMEOUT_ON_UPI_COLLECT_EXPIRY = 'enable_timeout_on_upi_collect_expiry';

    /**
     * Razorx flag to enable sync processing for Route transfers in route transfer creation APIs
     */
    const ENABLE_TRANSFER_SYNC_PROCESSING_VIA_API = 'enable_transfer_sync_processing_via_api';

    /**
     * Razorx flag to enable sync processing for Route transfers via cron
     */
    const ENABLE_TRANSFER_SYNC_PROCESSING_VIA_CRON = 'enable_transfer_sync_processing_via_cron';

    // FeatureFlag to toggle Pricing Rule Fee Model override for BPCL
    const FEE_MODEL_OVERRIDE = 'FEE_MODEL_OVERRIDE';

    const CLEVERTAP_MIGRATION = 'clevertap_migration';

    /**
     * Razorx flag to enable Standard Checkout merchants to be onboarded for Push Token Provisioning
     */
    const ENABLE_STANDARD_CHECKOUT_MERCHANTS_ON_PUSH_TOKEN_PROVISIONING = 'enable_standard_checkout_merchants_on_push_token_provisioning';

    const QR_PAYMENT_PROCESS_RETRY = 'qr_payment_process_retry';

    /**
     * Razorx flag to skip callback for upi_icici BT(Beneficiary Timeout) cases
     *
     */
    const SKIP_UPI_ICICI_CALLBACK_FOR_BT = 'skip_upi_icici_callback_for_bt';
    const RECURRING_SIHUB_CANCEL_WEBHOOK_ENABLED = 'recurring_sihub_webhook_enabled';

    // Experiment to pass mandate date creation in icici paper nach registration files
    const ICICI_PNACH_MANDATE_CREATION_DATE_RAZORX = 'icici_pnach_mandate_creation_date_razorx';

    // Experiment to support multiple frequencies for card recurring payment CAW
    const CARD_MANDATE_ENABLE_MULTIPLE_FREQUENCIES = "card_mandate_enable_multiple_frequencies";

    // experiment to disable default max amount for upi autopay
    const UPI_AUTOPAY_DISABLE_MAX_AMOUNT_BLACKLIST = 'upi_autopay_disable_max_amount_blacklist';
}
