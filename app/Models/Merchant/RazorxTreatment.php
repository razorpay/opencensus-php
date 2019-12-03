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

    const TRANSFERS_VIA_ORDER = 'transfers_via_order';

    const RENDERING_PREFERENCES_PAYMENT_LINKS = 'rendering_preferences_payment_links';

    // Decides if Instant Refunds Mode decisioning call should go to scrooge during refund creation flow
    const INSTANT_REFUND_MODES = 'instant_refunds_modes';

    // Decides if fund account and contact creation should have duplicate checks
    const X_CONTACT_AND_FUND_ACCOUNT_CREATION = 'x_contact_and_fund_account_creation';

    // Decides what payload to return in the payouts webhook
    const PAYOUTS_WEBHOOK_FILTER = 'payouts_webhook_filter';
}
