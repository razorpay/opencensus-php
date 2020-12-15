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

    const ADMIN_ACTION_SLACK_LOG                    = 'ADMIN_ACTION_SLACK_LOG';
    const SLACK_QUERY_LOG                           = 'SLACK_QUERY_LOG';
    const SLACK_DATA_EXPORT_LOG                     = 'SLACK_DATA_EXPORT_LOG';

    // Request made to API has failed
    const API_REQUEST_FAILURE                       = 'API_REQUEST_FAILURE';

    const USER_LOGIN                                = 'USER_LOGIN';
    const USER_LOGIN_KEYS                           = 'USER_LOGIN_KEYS';
    const USER_OAUTH_LOGIN                          = 'USER_OAUTH_LOGIN';
    const USER_LOGIN_FAILURE                        = 'USER_LOGIN_FAILURE';
    const SWITCH_MERCHANT                           = 'SWITCH_MERCHANT';
    const USER_LOGOUT                               = 'USER_LOGOUT';
    const USER_UNLOCK_REQUEST                       = 'USER_UNLOCK_REQUEST';
    const USER_UNLOCK_RESPONSE                      = 'USER_UNLOCK_RESPONSE';
    const USER_RETRIEVE_CREDS                       = 'USER_RETRIEVE_CREDS';
    const USER_OAUTH_UNLOCK_RESPONSE                = 'USER_OAUTH_UNLOCK_RESPONSE';
    const ENABLE_INSTANT_ACTIVATIONS                = 'ENABLE_INSTANT_ACTIVATIONS';
    const GOOGLE_OAUTH_SIGN_IN_FAILURE              = 'GOOGLE_OAUTH_SIGN_IN_FAILURE';


    const USER_UNAUTHORIZED                         = 'USER_UNAUTHORIZED';
    const USER_UNAUTHORIZED_EXCEPTION               = 'USER_UNAUTHORIZED_EXCEPTION';

    const BULK_RAZORX_CALL_FAILED                   = 'BULK_RAZORX_CALL_FAILED';

    const ADMIN_LOGIN                               = 'ADMIN_LOGIN';
    const ADMIN_LOGOUT                              = 'ADMIN_LOGOUT';
    const ADMIN_AS_MERCHANT                         = 'ADMIN_AS_MERCHANT';

    const MISMATCHED_VERIFY_TOKEN                   = 'MISMATCHED_VERIFY_TOKEN';


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
