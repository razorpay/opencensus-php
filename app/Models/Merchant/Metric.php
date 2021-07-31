<?php

namespace RZP\Models\Merchant;

final class Metric
{
    // ------------------------- Metrics -------------------------

    // ------ Counters ------

    /**
     * Method: Count
     * Dimensions: Partner_type
     */
    const PARTNER_MARKED_TOTAL      = 'partner_marked_total';
    const PARTNER_MARK_REQUEST      = 'partner_mark_request';
    const ADD_SUB_MERCHANT          = 'add_sub_merchant';
    const SUB_MERCHANT_ADD_TYPE     = 'sub_merchant_add_type';

    // General constants used for metrics
    const MARKETPLACE               = 'marketplace';
    const PARTNER                   = 'parnter';

    const COUPON_VALIDATE_TOTAL     = 'coupon_validate_total';
    const SIGNUP_COUPON_TOTAL       = 'signup_coupon_total';
    const SIGNUP_TOTAL              = 'signup_total';
    const PRE_EDIT_SIGNUP_TOTAL     = 'pre_edit_signup_total';

    const INTERNATIONAL_ACTIVATION  = 'international_activation';

    const UNREGISTERED_BUSINESS_DEFAULT_LIMIT_USED_TOTAL = 'unregistered_business_default_limit_used_total';


    const MERCHANT_ACTIVATION_STATE_TRANSITION = 'merchant_activation_state_transition';
    const MERCHANT_ACTIVATION                  = 'merchant_activation';
    const INTERNATIONAL_MERCHANT_ACTIVATION    = 'international_merchant_activation';

    //activation_flow_metrics constants
    const ACTIVATION_FLOW = 'activation_flow';
    const PREVIOUS_ACTIVATION_STATUS = 'previous_activation_status';
    const UPDATED_ACTIVATION_STATUS = 'updated_activation_status';

    const MERCHANT_RAZORPAYX_ACTIVATION_FAILED_TOTAL = 'merchant_razorpayx_activation_failed_total';

    const RAZORX_BULK_EVALUATE_TIME_MS = 'razorx_bulk_evaluate_time_ms';

    const SUBMERCHANT_TAGGING_FAILURE_TOTAL = 'submerchant_tagging_failure_total';
}
