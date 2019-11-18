<?php

namespace RZP\Models\Gateway\Terminal;

final class Metric
{
    // ------------------------- Metrics -------------------------
    // ------ Counters ------
    /**
     * Method: Count
     */
    const TERMINAL_ONBOARDING_CREATE_FAILED       = 'terminal_onboarding_create_failed';
    const TERMINAL_ONBOARDING_INTERNAL_ERROR      = 'terminal_onboarding_internal_error';
    const TERMINAL_ONBOARDING_PSP_GATEWAY_ERROR   = 'terminal_onboarding_psp_gateway_error'; // error from gateway
}