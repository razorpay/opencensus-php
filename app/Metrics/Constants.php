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
  const METRIC_COUNTER_HTTP_REQUESTS_DOWNSTREAM    = 'http_requests_downstream';
  const METRIC_COUNTER_HTTP_REQUESTS               = 'http_requests';
  const METRIC_HISTOGRAM_HTTP_REQUESTS_DURATION    = 'http_requests_duration';
  const USER_LOGIN_COUNT            = 'user_login_count';
  const USER_VERIFY_COUNT           = 'user_verify_count';
  const USER_LOGOUT_COUNT           = 'user_logout_count';
  const USER_SIGNUP_COUNT           = 'user_signup_count';

  // Metric Lables
  const LOGIN_METHOD                = 'login_method';
  const LOGIN_ACTION                = 'login_action';
  const TWO_FA_DURING_SIGNUP        = 'two_fa_during_signup';

  // Signup method: otp/password/oauth
  const SIGNUP_METHOD               = 'signup_method';

  // Signup mode: email/password
  const SIGNUP_MEDIUM               = 'signup_mode';

  // Login mode: email/password
  const LOGIN_MEDIUM                = 'signup_mode';

  // Metric labels - HTTP_REQUESTS_DOWNSTREAM
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_STATUS       = 'status';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_IS_SUCCESS   = 'is_success';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_CONTROLLER   = 'controller';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_ROUTE        = 'route';
  const LABEL_HTTP_REQUESTS_PRODUCT                 = 'product';

  // Metric labels - HTTP_REQUESTS
  const LABEL_HTTP_REQUESTS_METHOD                  = 'method';
  const LABEL_HTTP_REQUESTS_ROUTE                   = 'route';
  const LABEL_HTTP_REQUESTS_STATUS                  = 'status';
  const LABEL_HTTP_REQUESTS_CONTROLLER              = 'controller';

  /* Possible Login actions */
  //when user sigin after enterin OTP
  const TWO_FA_OTP_VERIFICATION         = 'two_fa_verificiation';
  const TWO_FA_PASSWORD_VERIFICATION    = 'two_fa_password_verificiation';

   //when user does a normal login
  const NORMAL_LOGIN                = 'normal_login';
  const OTP_LOGIN                   = 'otp_login';
  const OTP_SIGNUP                  = 'otp_signup';

  /** Possible Login/Signup methods **/
  const PASSWORD                    = 'password';
  const OTP                         = 'otp';
   //currently only one oauth provider - google
  const OAUTH                       = 'oauth';

  /** Possible Login/Signup modes **/
  const EMAIL                       = 'email';
  const CONTACT_MOBILE              = 'contact_mobile';

  // Event trigger count
  const EVENT_COUNT_ONE             = 1;


  // products
  const PRIMARY                     = 'primary';
  const BANKING                     = 'banking';
}
