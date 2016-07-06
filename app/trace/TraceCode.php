<?php

namespace App\Trace;

use EE\Exception\InvalidArgumentException;

class TraceCode
{
    const AWS_INSTANCE_DATA_RECORD_FAILURE          = 'AWS_INSTANCE_DATA_RECORD_FAILURE';
    const AWS_INSTANCE_DATA_WRITE_FAILURE           = 'AWS_INSTANCE_DATA_WRITE_FAILURE';
    const AWS_INSTANCE_DATA_READ_FAILURE            = 'AWS_INSTANCE_DATA_READ_FAILURE';

    const DASHBOARD_INTEGRATION_ERROR               = 'DASHBOARD_INTEGRATION_ERROR';
    const QUEUE_JOB_FAILURE                         = 'QUEUE_JOB_FAILURE';

    const ERROR_EXCEPTION                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                           = 'MISC_TRACE_CODE';
    const SLACK_QUERY_RESPONSE                      = 'SLACK_QUERY_RESPONSE';

    protected static $messages = array(
        self::ERROR_EXCEPTION                       => 'Unhandled critical exception occured',
        self::MISC_TRACE_CODE                       => 'Miscellaneous trace code',
        self::SLACK_QUERY_RESPONSE                  => 'Slack Query Response Log'
    );

    /**
     * Translate event code to message
     *
     * @param $eventCode event code
     * @return
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
