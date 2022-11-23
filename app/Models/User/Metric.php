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
    const LOGIN_2FA_CORRECT_PASSWORD          = 'login_2fa_correct_password';
    const LOGIN_2FA_INCORRECT_OTP             = 'login_2fa_incorrect_otp';
    const LOGIN_2FA_INCORRECT_PASSWORD        = 'login_2fa_incorrect_password';
    const LOGIN_2FA_MAX_WRONG_OTP_ATTEMPTS    = 'login_2fa_max_wrong_otp_attempts';
    const LOGIN_USER_2FA_ENABLED              = 'login_user_2fa_enabled';
    const LOGIN_USER_2FA_NOT_SETUP            = 'login_user_2fa_not_setup';
    const USER_2FA_NOT_SETUP                  = 'user_2fa_not_setup';
    const USER_2FA_LOCKED                     = 'user_2fa_locked';
    const USER_ACCESS_CRITICAL_ROUTE          = 'user_access_critical_route';
    const LOGIN_ORG_ENFORCED_2FA_SUCCESS      = 'login_org_enforced_2fa_success';
    const USER_LOGIN_COUNT                    = 'user_login_count';
    const USER_EMAIL_ALREADY_VERIFIED         = 'user_email_already_verified';
    const USER_EMAIL_NOT_VERIFIED             = 'user_email_not_verified';
    const USER_MOBILE_ALREADY_VERIFIED        = 'user_mobile_already_verified';
    const VERIFY_USER_MOBILE                  = 'verify_user_mobile';
    const VERIFY_USER_EMAIL                   = 'verify_user_email';
    const USER_MOBILE_NOT_VERIFIED            = 'user_mobile_not_verified';
    const USER_EMAIL_OTP_SENT                 = 'user_email_otp_sent';
    const USER_SMS_OTP_SENT                   = 'user_sms_otp_sent';
    const USER_EMAIL_OTP_SEND_FAILED          = 'user_email_otp_send_failed';
    const USER_SMS_OTP_SEND_FAILED            = 'user_sms_otp_send_failed';
    const NO_ACCOUNTS_ASSOCIATED              = 'no_accounts_associated';
    const MULTIPLE_ACCOUNTS_ASSOCIATED        = 'multiple_accounts_associated';
    const VERIFY_LOGIN_INCORRECT_OTP          = 'verify_login_incorrect_otp';
    const USER_NOT_AUTHENTICATED              = 'user_not_authenticated';

    const DASHBOARD_SWITCH_SUCCESS_TOTAL      = 'dashboard_switch_success_total';
    const DASHBOARD_SWITCH_FAILURE_TOTAL      = 'dashboard_switch_failure_total';
    const USER_SIGNUP                         = 'user_signup';
    const VERIFY_SIGNUP_INCORRECT_OTP         = 'verify_signup_incorrect_otp';

    const CAPTCHA_VALIDATION_DURATION         = 'captcha_validation_duration';
}
