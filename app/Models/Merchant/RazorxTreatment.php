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

    const NON_REGISTERED_ONBOARDING = 'non_registered_onboarding';

    // Decides if increased cap for allowed line items in invoice to be used for merchant's invoice.
    const INV_INCREASED_LINE_ITEMS_CAP = 'inv_increased_line_items_cap';

    const CHANGE_QUEUE_BATCH_INVOICE = 'change_queue_batch_invoice';

    // Decides if api's webhook event should be dispatched via stork.
    const WEBHOOK_EVENT_VIA_STORK = 'webhook_event_via_stork';

    const SECOND_FACTOR_AUTH_PROJECT_EXP   = 'second_factor_auth_project';
    const SECOND_FACTOR_AUTH_LOGIN_EXP     = 'second_factor_auth_login';

    const SELLER_APP_PL_BATCH_UPLOAD_EXPERIMENT = 'sellerapp_PL_batch_upload';

    const RENDERING_PREFERENCES_PAYMENT_LINKS = 'rendering_preferences_payment_links';

    // Decides if fund account and contact creation should have duplicate checks
    const X_CONTACT_AND_FUND_ACCOUNT_CREATION = 'x_contact_and_fund_account_creation';

    // Decides what payload to return in the payouts webhook
    const PAYOUTS_WEBHOOK_FILTER = 'payouts_webhook_filter';

    // Decides if payout.created webhook should be fired for the merchant
    const PAYOUTS_CREATED_WEBHOOK = 'payouts_created_webhook';

    //Decides to hit KYC Service or Mozart for KYC verification
    const KYC_SERVICE_VERIFICATION     = 'kyc_service_verification';
    const POI_KYC_SERVICE_VERIFICATION = 'poi_kyc_service_verification';
    const POA_KYC_SERVICE_VERIFICATION = 'poa_kyc_service_verification';

    // Decides if the Settlement UX changes are displayed to the merchant
    const SETTLEMENT_UX_REVAMP = 'settlement_ux_revamp';

    const USE_UFH_FILE_STORE = 'use_ufh_file_store';

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

    //process updating the settlements for transfer asycn
    const TRANSFERS_SETTLEMENTS_QUEUE = 'transfer_settlements_queue';

    // allow pre_signup data to send to salesforce
    const PRE_SIGNUP_DETAILS_TO_SALESFORCE = 'pre_signup_details_to_salesforce';

    // Check whether payout to amex cards is supported for a merchant ot not.
    const PAYOUT_TO_AMEX_CARDS = 'payout_to_amex_cards';
}
