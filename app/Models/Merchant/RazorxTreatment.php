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

    // promotional pricing plan for onboarding submerchants
    const SUBMERCHANT_PROMOTIONAL_PRICING_PLAN = 'submerchant_promotional_pricing_plan';

    // restrict user to one role per merchant and product
    const RESTRICT_USER_TO_ONE_ROLE_PER_MERCHANT_AND_PRODUCT = 'restrict_user_to_one_role_per_merchant_and_product';

    // Forces ICICI channel when we get channel as yesbank
    const FORCE_ICICI_OVER_YESBANK_FOR_PAYOUTS = 'force_icici_over_yesbank_for_payouts';

    // Allow UPI Payouts via ICICI (VPA)
    const RAZORPAY_X_ALLOW_UPI_PAYOUTS_VIA_ICICI_TO_VPA = 'razorpay_x_allow_upi_payouts_via_icici_to_vpa';

    // Allow NEFT Payouts via ICICI (Card)
    const RAZORPAY_X_ALLOW_NEFT_PAYOUTS_VIA_ICICI_TO_CARD = 'razorpay_x_allow_neft_payouts_via_icici_to_card';

    // Allow UPI Payouts via ICICI (Card)
    const RAZORPAY_X_ALLOW_UPI_PAYOUTS_VIA_ICICI_TO_CARD = 'razorpay_x_allow_upi_payouts_via_icici_to_card';

    const RAZORPAY_X_ENABLE_YESBANK_PAYOUTS = 'razorpay_x_enable_yesbank_payouts';

    // This is to enable the settlements to go via new settlement service
    const SETTLEMENT_SERVICE_RAMP = 'settlement_service_ramp';

    // allow pre_signup data to send to salesforce
    const PRE_SIGNUP_DETAILS_TO_SALESFORCE = 'pre_signup_details_to_salesforce';

    // allow Banking Merchant to self-serve in onboarding process
    const X_MERCHANT_SELF_SERVE_ONBOARDING = 'x_merchant_self_serve_onboarding';

    // allow partner_type data push to salesforce
    const PARTNER_TYPE_TO_SALESFORCE = 'PARTNER_TYPE_TO_SALESFORCE';

    // Check whether payout to amex cards is supported for a merchant ot not.
    const PAYOUT_TO_AMEX_CARDS = 'payout_to_amex_cards';

    // Decides if should forward passport(jwt) received from edge to subscriptions service.
    const FORWARD_PASSPORT_TO_SUBSCRIPTIONS = 'forward_passport_to_subscriptions';

    // Experiment for 2FA on critical actions
    const VALIDATE_USER_2FA_STATUS = 'validate_user_2fa_status';

    // Block external transaction webhooks for RBL CA
    const BLOCK_EXTERNAL_TRANSACTION_CREATED_WEBHOOK_RBL = 'block_external_transaction_created_webhook_rbl';

    // Check whether trimming is allowed for merchant or not.
    const TRIM_SPACE_FOR_MERCHANT = 'trim_space_for_merchant';

    const VIRTUAL_VPA_ICICI = 'virtual_vpa_icici';

    const PAYMENT_TRANSFER_ASYNC = 'payment_transfer_async';

    const EMANDATE_NONZERO_AMOUNT = 'emandate_nonzero_amount';

    // Added to test support of prepaid cards payouts for test merchants.
    // TODO: Remove experiment once testing concludes.
    const PAYOUT_TO_PREPAID_CARDS = 'payout_to_prepaid_cards';

    // To decide whether a merchant without specific Instant Refunds pricing - will have the old default pricing or
    // the new default pricing applied
    const INSTANT_REFUNDS_DEFAULT_PRICING_V2 = 'instant_refunds_default_pricing_v2';

    // Check whether Payout Link Service is up or not.
    // If the feature is mapped to a merchant, the Service is down for that merchant
    const RX_IS_PAYOUT_LINK_SERVICE_DOWN = 'rx_payout_links_inactive';

    // Decides if routes should go to Payout Link MicroService or API
    // If the feature is mapped to a merchant, the routes will go to MicroService
    const RX_PAYOUT_LINK_MICROSERVICE = 'rx_payout_links_ms';

    const BATCH_SERVICE_REFUND_MIGRATION     = 'batch_service_refund_migration';

    const CREDCASE_DUAL_WRITE_ENABLED = 'credcase_dual_write_enabled';

    // New driver of ses to be used for some templates. Following flag
    // helps in ramping up to ses for those templates
    const API_EMAIL_SES_DRIVER = 'api_email_ses_driver';

    // Check whether skip_workflow_payout_specific feature is allowed for merchant or not.
    const SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE = 'skip_workflow_payout_specific_feature';

    const PUBLIC_KEY_SIGNATURE_GENERATION = 'public_key_signature_generation';

    const API_EMAIL_DRIVER_CHANGE_DYNAMICALLY = 'api_email_driver_set_dynamically';

    const API_EMAIL_SHOULD_SET_TO_DEFAULT_DRIVER = 'api_email_should_set_default_driver';

    // experiment for merchant with trim migration completed
    const TRIM_MIGRATION_COMPLETED = 'trim_migration_completed';
}
