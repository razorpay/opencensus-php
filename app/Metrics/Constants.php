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
  const METRIC_COUNTER_HTTP_REQUESTS_DOWNSTREAM         = 'http_requests_downstream';
  const METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM     = 'http_requests_api_downstream';
  const METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION  = 'http_requests_api_downstream_duration';
  const METRIC_COUNTER_HTTP_REQUESTS                    = 'http_requests';
  const METRIC_HISTOGRAM_HTTP_REQUESTS_DURATION         = 'http_requests_duration';
  const METRIC_USER_PAGE_RENDER                         = 'user_page_render';
  const METRIC_HISTOGRAM_USER_PAGE_RENDER               = 'user_page_render_duration';
  const USER_LOGIN_COUNT                                = 'user_login_count';
  const USER_VERIFY_COUNT                               = 'user_verify_count';
  const USER_LOGOUT_COUNT                               = 'user_logout_count';
  const USER_SIGNUP_COUNT                               = 'user_signup_count';
  const USER_LOGIN_FAIL_COUNT                           = 'user_login_fail_count';
  const USER_LOGIN_VERIFY_OTP_FAIL_COUNT                = 'user_login_verify_otp_fail_count';
  const TWO_FA_PASSWORD_VERIFICATION_FAILED_COUNT       = 'two_fa_password_verification_failed_count';
  const USER_SIGNUP_FAIL_COUNT                          = 'user_signup_fail_count';
  const USER_SIGNUP_SUCCESS_COUNT                       = 'user_signup_success_count';
  const USER_SIGNUP_VERIFY_OTP_FAIL_COUNT               = 'user_signup_verify_otp_fail_count';
  const LOGIN_OTP_FAILED                                = 'login_otp_failed';
  const SIGNUP_OTP_FAILED                               = 'signup_otp_failed';
  const USER_REGISTER_REQUEST_WITHOUT_CAPTCHA_COUNT     = 'user_register_request_without_captcha_count';
  const USER_REGISTER_REQUEST_WITH_OAUTH_PROVIDER_COUNT = 'user_register_request_with_oauth_provider_count';
  const USER_LOGIN_REQUEST_WITH_OAUTH_PROVIDER_COUNT    = 'user_login_request_with_oauth_provider_count';
  const PRODUCT                                         = 'product';
  const PLATFORM                                        = 'platform';
  const SIGNUP_SOURCE                                   = 'signup_source';
  const REQUEST_SOURCE                                  = 'request_source';
  const USER_SIGNUP_DURATION                            = 'user_signup_duration';
  const SEND_SIGNUP_OTP_TRIGGERED_COUNT                 = 'send_signup_otp_triggered_count';
  const SEND_SIGNUP_OTP_FAILED_COUNT                    = 'send_signup_otp_failed_count';
  const SEND_SIGNUP_OTP_SUCCESS_COUNT                   = 'send_signup_otp_success_count';
  const SEND_SIGNUP_OTP_DURATION                        = 'send_signup_otp_duration';
  const VERIFY_SIGNUP_OTP_TRIGGERED_COUNT               = 'verify_signup_otp_trigerred_count';
  const VERIFY_SIGNUP_OTP_SUCCESS_COUNT                 = 'verify_signup_otp_success_count';
  const VERIFY_SIGNUP_OTP_FAILED_COUNT                  = 'verify_signup_otp_failed_count';
  const VERIFY_SIGNUP_OTP_DURATION                      = 'verify_signup_otp_duration';
  const USER_LOGIN_TRIGGERED_COUNT                      = 'user_login_triggered_count';
  const USER_SIGNUP_TRIGGERED_COUNT                     = 'user_signup_triggered_count';
  const USER_LOGIN_SUCCESS_COUNT                        = 'user_login_success_count';
  const USER_LOGIN_FAILED_COUNT                         = 'user_login_failed_count';
  const USER_LOGIN_DURATION                             = 'user_login_duration';
  const SEND_LOGIN_OTP_TRIGGERED_COUNT                  = 'send_login_otp_triggered_count';
  const VERIFY_LOGIN_OTP_TRIGGERED_COUNT                = 'verify_login_otp_trigerred_count';
  const SEND_LOGIN_OTP_FAILED_COUNT                     = 'send_login_otp_failed_count';
  const SEND_LOGIN_OTP_SUCCESS_COUNT                    = 'send_login_otp_success_count';
  const SEND_LOGIN_OTP_DURATION                         = 'send_login_otp_duration';
  const VERIFY_LOGIN_OTP_SUCCESS_COUNT                  = 'verify_login_otp_success_count';
  const VERIFY_LOGIN_OTP_FAILED_COUNT                   = 'verify_login_otp_failed_count';
  const VERIFY_LOGIN_OTP_DURATION                       = 'verify_login_otp_duration';
  const USER_SIGNUP_FAILED_COUNT                        = 'user_signup_failed_count';
  const OTP_LOGIN_2FA_PASSWORD_SUCCESS_COUNT            = 'otp_login_2fa_password_success_count';
  const OTP_LOGIN_2FA_PASSWORD_FAILED_COUNT             = 'otp_login_2fa_password_failed_count';
  const OTP_LOGIN_2FA_PASSWORD_DURATION                 = 'otp_login_2fa_password_duration';
  const PASSWORD_LOGIN_2FA_OTP_SUCCESS_COUNT            = 'password_login_2fa_otp_success_count';
  const PASSWORD_LOGIN_2FA_OTP_DURATION                 = 'password_login_2fa_otp_duration';
  const PASSWORD_LOGIN_2FA_OTP_FAILED_COUNT             = 'password_login_2fa_otp_failed_count';
  const USER_OAUTH_LOGIN_SUCCESS_COUNT                  = 'user_oauth_login_success_count';
  const USER_OAUTH_LOGIN_DURATION                       = 'user_oauth_login_duration';
  const USER_OAUTH_LOGIN_FAILED_COUNT                   = 'user_oauth_login_failed_count';
  const USER_OAUTH_SIGNUP_SUCCESS_COUNT                 = 'user_oauth_signup_success_count';
  const USER_OAUTH_SIGNUP_DURATION                      = 'user_oauth_signup_duration';
  const USER_OAUTH_SIGNUP_FAILED_COUNT                  = 'user_oauth_signup_failed_count';
  const API_CIRCUIT_BREAKER_STATE_COUNT                 = 'api_circuit_breaker_state';
  const API_CIRCUIT_BREAKER_REQUEST_RESULT_COUNT        = 'api_circuit_breaker_request_result';
  const INVALID_PASSPORT_FOR_OAUTH_ROUTE                = 'invalid_passport_for_oauth_route';
  const INVALID_PASSPORT_FOR_MOBILE_OAUTH_ROUTE         = 'invalid_passport_for_mobile_oauth_route';
  const PASSPORT_MISSING_FOR_OAUTH_ROUTE                = 'passport_missing_for_oauth_route';
  const SPLITZ_EXPERIMENT_DASHBOARD_CACHE_HIT           = 'splitz_experiment_dashboard_cache_hit';
  const SPLITZ_EXPERIMENT_DASHBOARD_CACHE_MISS          = 'splitz_experiment_dashboard_cache_miss';

  // Metric Lables
  const LOGIN_METHOD                = 'login_method';
  const LOGIN_ACTION                = 'login_action';
  const TWO_FA_DURING_SIGNUP        = 'two_fa_during_signup';

  // Signup method: otp/password/oauth
  const SIGNUP_METHOD               = 'signup_method';

  // Signup mode: email/password
  const SIGNUP_MEDIUM               = 'signup_medium';

  // Login mode: email/password
  const LOGIN_MEDIUM                = 'login_medium';

  // Metric labels - HTTP_REQUESTS_DOWNSTREAM
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_STATUS       = 'status';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_IS_SUCCESS   = 'is_success';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_CONTROLLER   = 'controller';
  const LABEL_HTTP_REQUESTS_DOWNSTREAM_ROUTE        = 'route';

  // Metric labels - HTTP_REQUESTS
  const LABEL_HTTP_REQUESTS_METHOD                  = 'method';
  const LABEL_HTTP_REQUESTS_ROUTE                   = 'route';
  const LABEL_HTTP_REQUESTS_STATUS                  = 'status';
  const LABEL_HTTP_REQUESTS_CONTROLLER              = 'controller';
  const LABEL_HTTP_REQUESTS_PRODUCT                 = 'product';
  const LABEL_HTTP_REQUESTS_ORIGIN                  = 'origin';
  const LABEL_HTTP_REQUESTS_DOMAIN                  = 'domain';
  const LABEL_HTTP_REQUESTS_GRAPHQL_CLIENT          = 'graphql_client';
  
  const LABEL_DASHBOARD_CBS                         = 'chunked_based_streaming';
  const LABEL_DASHBOARD_CONCURRENT_API_CALL         = 'concurrent_api_call';
  
  const LABEL_RZP_TEAM                              = 'rzp_team';

  // Metric labels - HTTP_REQUESTS_API_DOWNSTREAM
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_STATUS            = 'status';
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_METHOD  = 'dashboard_method';
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_ROUTE   = 'dashboard_route';
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_PRODUCT           = 'product';
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_API_ROUTE_NAME    = 'api_route_name';
  const LABEL_HTTP_REQUESTS_API_DOWNSTREAM_API_RESPONSE_TIME = 'response_time';

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

  // API Circuit Breaker Related dimensions and labels
  const CIRCUIT_STATE               = 'circuit_state';
  const UNKNOWN_ROUTE               = 'unknown_route';
  const REQUEST_RESULT              = 'result';
  const REQUEST_FAILURE             = 'failure';
  const REQUEST_SUCCESS             = 'success';
}
