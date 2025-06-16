<?php

namespace RZP\Models\Merchant\Escalations;

class Metric
{
    const NEW_XPRESS_ESCALATIONS_SUCCESS_TOTAL = 'new_xpress_escalations_success_total';
    const NEW_XPRESS_ESCALATIONS_FAIL_TOTAL    = 'new_xpress_escalations_fail_total';
    const ESCALATION_ATTEMPT_SUCCESS           = 'escalation_attempt_success';

    const ESCALATION_ATTEMPT_SKIP_COUNT           = 'escalation_attempt_skip_count';

    const ESCALATION_ATTEMPT_FAIL_COUNT           = 'escalation_attempt_fail_count';
    const PAYMENT_ESCALATION_SUCCESS_TOTAL     = 'payment_escalation_success_total';
    const PAYMENT_ESCALATION_FAIL_TOTAL        = 'payment_escalation_fail_total';
    const BANKING_ORG_PAYMENT_ESCALATION_SUCCESS_TOTAL     = 'payment_escalation_success_total';
    const BANKING_ORG_PAYMENT_ESCALATION_FAIL_TOTAL        = 'payment_escalation_fail_total';

    const MTU_COUPON_APPLY_FAILURE_COUNT = 'mtu_coupon_apply_fail_count';
}
