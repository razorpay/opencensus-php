<?php

namespace App\Trace;

use EE\Exception\InvalidArgumentException;

class TraceCode
{
    const AWS_INSTANCE_DATA_RECORD_FAILURE          = 'AWS_INSTANCE_DATA_RECORD_FAILURE';
    const AWS_INSTANCE_DATA_WRITE_FAILURE           = 'AWS_INSTANCE_DATA_WRITE_FAILURE';
    const AWS_INSTANCE_DATA_READ_FAILURE            = 'AWS_INSTANCE_DATA_READ_FAILURE';
    const USER_CONTEXT_LOG                          = 'USER_CONTEXT_LOG';

    const DASHBOARD_INTEGRATION_ERROR               = 'DASHBOARD_INTEGRATION_ERROR';
    const QUEUE_JOB_FAILURE                         = 'QUEUE_JOB_FAILURE';
    const USER_REGISTER_LOGIN_ATTEMPT               = 'USER_REGISTER_LOGIN_ATTEMPT';
    const ERROR_EXCEPTION                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                           = 'MISC_TRACE_CODE';
    const SLACK_QUERY_RESPONSE                      = 'SLACK_QUERY_RESPONSE';
    const USER_REGISTER_OAUTH_PROVIDER_ERROR        = 'USER_REGISTER_OAUTH_PROVIDER_ERROR';
    const CAPTCHA_DISABLE_INVALID_PAYLOAD_ERROR     = 'CAPTCHA_DISABLE_INVALID_PAYLOAD_ERROR';

    const ADMIN_ACTION_SLACK_LOG                    = 'ADMIN_ACTION_SLACK_LOG';
    const SLACK_QUERY_LOG                           = 'SLACK_QUERY_LOG';
    const SLACK_DATA_EXPORT_LOG                     = 'SLACK_DATA_EXPORT_LOG';

    // Request made to API has failed
    const API_REQUEST_FAILURE                       = 'API_REQUEST_FAILURE';

    const API_SLOW_RESPONSE_CALL                    = 'API_SLOW_RESPONSE_CALL';
    const API_RESPONSE_METRIC                       = 'API_RESPONSE_METRIC';

    const ADMIN_LOGIN_DEBUG                         = 'ADMIN_LOGIN_DEBUG';

    const USER_LOGIN                                = 'USER_LOGIN';
    const USER_LOGIN_DURATION                       = 'USER_LOGIN_DURATION';
    const SEND_LOGIN_OTP_DURATION                   = 'SEND_LOGIN_OTP_DURATION';
    const SEND_USER_VERIFY_OTP_DURATION             = 'SEND_USER_VERIFY_OTP_DURATION';
    const VERIFY_LOGIN_OTP_DURATION                 = 'VERIFY_LOGIN_OTP_DURATION';
    const VERIFY_VERIFICATION_OTP_DURATION          = 'VERIFY_VERIFICATION_OTP_DURATION';
    const USER_LOGIN_KEYS                           = 'USER_LOGIN_KEYS';
    const USER_OAUTH_LOGIN                          = 'USER_OAUTH_LOGIN';
    const USER_LOGIN_FAILURE                        = 'USER_LOGIN_FAILURE';
    const USER_VERIFICATION_FAILURE                 = 'USER_VERIFICATION_FAILURE';
    const SWITCH_MERCHANT                           = 'SWITCH_MERCHANT';
    const USER_LOGOUT                               = 'USER_LOGOUT';
    const USER_RETRIEVE_CREDS                       = 'USER_RETRIEVE_CREDS';
    const ENABLE_INSTANT_ACTIVATIONS                = 'ENABLE_INSTANT_ACTIVATIONS';

    const USERS_LOGOUT_BY_ROUTE                     = 'USERS_LOGOUT_BY_ROUTE';
    const USER_FETCH_BROWSER_DETAILS_FAILURE        = 'USER_FETCH_BROWSER_DETAILS_FAILURE';
    const INVALIDATE_OTHER_ACTIVE_SESSIONS          = 'INVALIDATE_OTHER_ACTIVE_SESSIONS';

    const USER_UNAUTHORIZED                         = 'USER_UNAUTHORIZED';
    const USER_UNAUTHORIZED_EXCEPTION               = 'USER_UNAUTHORIZED_EXCEPTION';

    const USER_UNAUTHORIZED_GENERIC_EXCEPTION       = 'USER_UNAUTHORIZED_GENERIC_EXCEPTION';

    const GENERIC_ROUTE_PATH                        = 'GENERIC_ROUTE_PATH';

    const ADMIN_LOGGED_IN_AS_MERCHANT               = 'ADMIN_LOGGED_IN_AS_MERCHANT';

    const BULK_RAZORX_CALL_FAILED                   = 'BULK_RAZORX_CALL_FAILED';

    const ADMIN_LOGIN                               = 'ADMIN_LOGIN';
    const ADMIN_LOGOUT                              = 'ADMIN_LOGOUT';
    const ADMIN_AS_MERCHANT                         = 'ADMIN_AS_MERCHANT';

    const ORG_LOGO_UPLOAD                           = 'ORG_LOGO_UPLOAD';

    const MISMATCHED_VERIFY_TOKEN                   = 'MISMATCHED_VERIFY_TOKEN';

    const DEBUG_MERCHANT_TRUTHY_VALUE               = 'DEBUG_MERCHANT_TRUTHY_VALUE';

    const SPLITZ_EVALUATE_FAILED                    = 'SPLITZ_EVALUATE_FAILED';

    const SPLITZ_BULK_EVALUATE_FAILED               = 'SPLITZ_BULK_EVALUATE_FAILED';

    const PUSH_METRICS_FAILED                       = 'PUSH_METRICS_FAILED';

    const ORG_FEATURES_CACHE_MISS                   = 'ORG_FEATURES_CACHE_MISS';

    const ADMIN_LOGOUT_ON_INACTIVITY                = 'ADMIN_LOGOUT_ON_INACTIVITY';

    const ADMIN_RAW_API_CALL                        = 'ADMIN_RAW_API_CALL';
    const BLOCKED_DUE_TO_SBB_622                    = 'BLOCKED_DUE_TO_SBB_622';

    const GET_USER_FROM_API                         = 'GET_USER_FROM_API';
    const GET_USER_DURATION                         = 'GET_USER_DURATION';

    const PUSHED_HUBSPOT_EVENT_TO_API               = 'PUSHED_HUBSPOT_EVENT_TO_API';

    const PUSHED_HUBSPOT_EVENT_TO_API_FAILED        = 'PUSHED_HUBSPOT_EVENT_TO_API_FAILED';

    const PUSHED_SALESFORCE_LEAD_TO_API_FAILED      = 'PUSHED_SALESFORCE_LEAD_TO_API_FAILED';

    const PUSHED_SALESFORCE_LEAD_TO_API             = 'PUSHED_SALESFORCE_LEAD_TO_API';

    const GRAPH_REQUEST_OPERATION_WITH_USER_ID      = 'GRAPH_REQUEST_OPERATION_WITH_USER_ID';

    // User API tracecodes
    const GET_USER_ROUTE_INFO                    = 'GET_USER_ROUTE_INFO';
    const GET_MERCHANT_DETAILS_ROUTE_INFO        = 'GET_MERCHANT_DETAILS_ROUTE_INFO';
    const GET_TAGS_ROUTE_INFO                    = 'GET_TAGS_ROUTE_INFO';
    const GET_FEATURES_ROUTE_INFO                = 'GET_FEATURES_ROUTE_INFO';
    const GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO = 'GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO';
    const GET_RAZORX_EXPERIMENTS_ROUTE_INFO      = 'GET_RAZORX_EXPERIMENTS_ROUTE_INFO';
    const GET_SPLITZ_EXPERIMENTS_ROUTE_INFO      = 'GET_SPLITZ_EXPERIMENTS_ROUTE_INFO';
    const GET_CAMPAIGNS_ROUTE_INFO               = 'GET_CAMPAIGNS_ROUTE_INFO';
    const GET_PARTNER_CONFIGS_ROUTE_INFO         = 'GET_PARTNER_CONFIGS_ROUTE_INFO';
    const LEAD_TO_SALESFORCE_ROUTE_INFO          = 'LEAD_TO_SALESFORCE_ROUTE_INFO';
    const GET_PARTNER_INTENT_ROUTE_INFO          = 'GET_PARTNER_INTENT_ROUTE_INFO';
    const GET_PAYOUT_ROUTE_INFO                  = 'GET_PAYOUT_ROUTE_INFO';
    const PRODUCT_SWITCH_ROUTE_INFO              = 'PRODUCT_SWITCH_ROUTE_INFO';
    const FIRE_EVENT_TO_HUBSPOT_ROUTE_INFO       = 'FIRE_EVENT_TO_HUBSPOT_ROUTE_INFO';

    const MERCHANT_EXPERIMENTS                   = 'MERCHANT_EXPERIMENTS';

    protected static $messages = array(
        self::ERROR_EXCEPTION                       => 'Unhandled critical exception occured',
        self::MISC_TRACE_CODE                       => 'Miscellaneous trace code',
        self::SLACK_QUERY_RESPONSE                  => 'Slack Query Response Log'
    );

    /**
     * Translate event code to message
     *
     * @param  string $code
     *
     * @return string
     */
    public static function getMessage($code)
    {
        if (isset(self::$messages[$code]) === false)
        {
            // throw new InvalidArgumentException('Message for $code not defined');
            return null;
        }

        return self::$messages[$code];
    }

    public static function checkCode($code)
    {
        if (! defined(__NAMESPACE__."\TraceCode::$code"))
        {
            throw new InvalidArgumentException(
                __NAMESPACE__.'\TraceCode::'.$code.'not defined');
        }
    }
}
