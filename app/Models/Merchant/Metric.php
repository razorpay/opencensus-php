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
    const PARTNER_MARKED_TOTAL  = 'partner_marked_total';
    const PARTNER_MARK_REQUEST  = 'partner_mark_request';
    const ADD_SUB_MERCHANT      = 'add_sub_merchant';
    const SUB_MERCHANT_ADD_TYPE = 'sub_merchant_add_type';

    // General constants used for metrics
    const MARKETPLACE           = 'marketplace';
    const PARTNER               = 'parnter';

    const COUPON_VALIDATE_TOTAL  = 'coupon_validate_total';
    const SIGNUP_COUPON_TOTAL    = 'signup_coupon_total';
    const SIGNUP_TOTAL           = 'signup_total';
    const PRE_EDIT_SIGNUP_TOTAL  = 'pre_edit_signup_total';

}
