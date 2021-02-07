<?php

namespace RZP\Models\Merchant;

final class RazorxTreatment
{
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

    const OLD_TO_NEW_IFSC_FOR_MERGED_BANK = 'old_to_new_ifsc_for_merged_bank';

    //
    // This experiment is used for POI verification only
    //
    const BVS_AUTO_KYC = 'bvs_auto_kyc';

    const BVS_PENNY_TESTING        = 'bvs_penny_testing';

    // this experiment is used to enable/disable company search

    const BVS_COMPANY_SEARCH = "bvs_company_search";

    // Decides if increased cap for allowed line items in invoice to be used for merchant's invoice.
    const INV_INCREASED_LINE_ITEMS_CAP = 'inv_increased_line_items_cap';

    const CHANGE_QUEUE_BATCH_INVOICE = 'change_queue_batch_invoice';

    const SECOND_FACTOR_AUTH_PROJECT_EXP   = 'second_factor_auth_project';

    const SELLER_APP_PL_BATCH_UPLOAD_EXPERIMENT = 'sellerapp_PL_batch_upload';

    const RENDERING_PREFERENCES_PAYMENT_LINKS = 'rendering_preferences_payment_links';

    // Decides if fund account and contact creation should have duplicate checks
    const X_CONTACT_AND_FUND_ACCOUNT_CREATION = 'x_contact_and_fund_account_creation';

    // Decides what payload to return in the payouts webhook
    const PAYOUTS_WEBHOOK_FILTER = 'payouts_webhook_filter';

    // Decides whether reject reason has to be sent in webhook
    const PAYOUTS_REJECT_COMMENT_IN_WEBHOOK_FILTER = 'payouts_reject_comment_in_webhook_filter';

    // Decides if payout.created webhook should be fired for the merchant
    const PAYOUTS_CREATED_WEBHOOK = 'payouts_created_webhook';

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

    // Decides payout channel based on IFT mode
    const IFT_MODE_PAYOUT_FILTER = 'ift_mode_payout_filter';

    // Decides whether or not to display tr attribute in upi_transfer entity
    const UPI_TRANSFER_TR = 'upi_transfer_tr';

    // Onboard merchant on RazorpayX test mode
    const RAZORPAY_X_TEST_MODE_ONBOARDING = 'razorpayx_x_test_mode_onboarding';

    // New user EmailVerify through OTP
    const EMAIL_VERIFICATION_USING_OTP = 'email_verification_using_otp';

    // Access control to allow pg request after new acl
    const RAZORPAY_X_ACL_DENY_UNAUTHORISED = 'razorpay_x_acl_deny_unauthorised';

    // restrict user to one role per merchant and product
    const RESTRICT_USER_TO_ONE_ROLE_PER_MERCHANT_AND_PRODUCT = 'restrict_user_to_one_role_per_merchant_and_product';

    // Forces ICICI channel when we get channel as yesbank
    const FORCE_ICICI_OVER_YESBANK_FOR_PAYOUTS = 'force_icici_over_yesbank_for_payouts';

    const RAZORPAY_X_ENABLE_YESBANK_PAYOUTS = 'razorpay_x_enable_yesbank_payouts';

    // Check whether payout to amex cards is supported for a merchant ot not.
    const PAYOUT_TO_AMEX_CARDS = 'payout_to_amex_cards';

    // Decides if should forward passport(jwt) received from edge to subscriptions service.
    const FORWARD_PASSPORT_TO_SUBSCRIPTIONS = 'forward_passport_to_subscriptions';

    // Experiment for 2FA on critical actions
    const VALIDATE_USER_2FA_STATUS = 'validate_user_2fa_status';

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

    // Check whether skip_workflow_payout_specific feature is allowed for merchant or not.
    const SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE = 'skip_workflow_payout_specific_feature';

    const PUBLIC_KEY_SIGNATURE_GENERATION = 'public_key_signature_generation';

    const PG_ROUTER_ORDER_SHOULD_DISPATCH_TO_QUEUE = 'pg_router_order_should_dispatch_to_queue';

    // experiment for merchant when trim migration in progress
    const TRIM_MIGRATION_IN_PROGRESS = 'trim_migration_in_progress';

    const PAYOUT_TO_CARDS_VIA_RBL = 'payout_to_cards_via_rbl';

    // FTS-FTA Holiday Management changes
    const ALLOWED_MERCHANTS = 'hm_merchant_api';

    // Ledger Async For payouts
    const QUEUE_PAYOUT_CREATE_REQUEST = 'queue_payout_create_request';

    // experiment to ramping pg persistent invoice
    const PG_PERSISTENT_INVOICE = 'pg_persistent_invoice';

    // experiment for opting out of settlement notification
    const SETTLEMENT_NOTIFICATION_OPT_OUT = 'settlement_notification_opt_out';

    const TOKENIZE_QR_STRING_MPANS = 'tokenize_qr_string_mpans';

    const BANK_TRANSFER_QUEUE = 'bank_transfer_queue';

    // experiment to enable webhooks on route gateway_payment_static_s2scallback_post/gateway_payment_static_s2scallback_get
    const ENABLE_WEBHOOKS = 'enable_webhooks';

    const PAYMENT_STATUS_PENDING_CALCULATION = 'payment_status_pending_calculation';

    const BULK_PAYOUTS_IMPROVEMENTS_ROLLOUT = 'bulk_payouts_improvements_rollout';

    // experiment to enable request logging
    const REQUEST_LOG = 'request_log';

    // experiment to enable whatsapp notifications and also refactoring notifications;
    const WHATSAPP_NOTIFICATIONS = 'whatsapp_notifications';

    // experiment to enable esign aadhar functionality
    const ESIGN_AADHAR_FUNCTIONALITY = 'esign_aadhar_functionality';

    // experiment to enable dedupe functionality
    const DEDUPE_FUNCTIONALITY = 'DEDUPE_FUNCTIONALITY';

    // experiment to enable whatsapp notifications for settlements
    const WHATSAPP_NOTIFICATIONS_SETTLEMENTS = 'whatsapp_notification_enablement';

    // release duplicate receipt check in refunds only for Google merchant as of now
    const DUPLICATE_RECEIPT_CHECK = 'duplicate_receipt_check';

    // Check if Offers is enabled for subscription
    const OFFER_ON_SUBSCRIPTION = 'offer_on_subscription';

    const SYSTEM_BASED_NEEDS_CLARIFICATION = 'system_based_needs_clarification';

    // experiment to enable self serving of auto kyc registered merchants
    const SELF_SERVE_AUTO_KYC = 'self_serve_auto_kyc';

    // some merchants require more attempts to fetch their whole statement. hence special attempt limit should be enabled for them.
    const BANKING_ACCOUNT_STATEMENT_SPECIAL_ATTEMPT_LIMIT = 'banking_account_statement_special_attempt_limit';

    const API_EMAILS_MAILGUN_DRIVER = 'api_emails_mailgun_driver';

    // This is to be used to block VA to VA payouts
    const RX_ALLOW_VA_TO_VA_PAYOUTS = 'rx_allow_va_to_va_payouts';

    const APPS_RISK_CHECK = 'apps_risk_check';

    const GSTIN_SELF_SERVE_V2 = 'gstin_self_serve_v2';

    // Disable tpv flow in fund loading for business banking merchants (Razorpay X) if required.
    const DISABLE_TPV_FLOW_FOR_BANKING_ACCOUNT_FUND_LOADING = 'disable_tpv_flow_for_banking_account_fund_loading';
}
