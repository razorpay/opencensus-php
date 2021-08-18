<?php

namespace RZP\Models\User;

final class Metric
{
    // ------------------------- Metrics -------------------------

    // ------ Counters ------

    /**
     * Method: Count
     */
    const LOGIN_2FA_SUCCESS                   = 'login_2fa_success';
    const LOCKED_USER_LOGIN                   = 'locked_user_login';
    const LOGIN_2FA_CORRECT_OTP               = 'login_2fa_correct_otp';
    const LOGIN_2FA_INCORRECT_OTP             = 'login_2fa_incorrect_otp';
    const LOGIN_2FA_MAX_WRONG_OTP_ATTEMPTS    = 'login_2fa_max_wrong_otp_attempts';
    const LOGIN_USER_2FA_ENABLED              = 'login_user_2fa_enabled';
    const LOGIN_USER_2FA_NOT_SETUP            = 'login_user_2fa_not_setup';
    const USER_2FA_NOT_SETUP                  = 'user_2fa_not_setup';
    const USER_2FA_LOCKED                     = 'user_2fa_locked';
    const USER_ACCESS_CRITICAL_ROUTE          = 'user_access_critical_route';

    const DASHBOARD_SWITCH_SUCCESS_TOTAL = 'dashboard_switch_success_total';
    const DASHBOARD_SWITCH_FAILURE_TOTAL = 'dashboard_switch_failure_total';
}
