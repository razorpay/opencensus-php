<?php

namespace App\Metrics;

/**
 * List of application metric names
 */
class Constants
{
  // defaults
  const LABEL_DEFAULT_VALUE = 'other';

  // Counters
  const USER_LOGIN_COUNT            = 'user_login_count';
  const USER_LOGOUT_COUNT           = 'user_logout_count';
  const USER_UNLOCK_COUNT           = 'user_unlock_count';
  const USER_SIGNUP_COUNT           = 'user_signup_count';

  // Metric Lables
  const LOGIN_METHOD                = 'login_method';
  const LOGIN_ACTION                = 'login_action';
  const TWO_FA_DURING_SIGNUP        = 'two_fa_during_signup';

  // Metric Values
  const PASSWORD                    = 'password';
  const OAUTH                       = 'oauth';
  const TWO_FA_OTP_VERIFICATION     = 'two_fa_verificiation';
}
