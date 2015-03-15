<?php

namespace Trace;

use Trace\TraceCode;
use Models\Payment\Entity as Payment;

class TraceFields
{
    protected static $fields = array(
        TraceCode::ERROR_EXCEPTION => array(
            'class',
            'message',
            'stack',
            'code',
            'data',
        ),

        TraceCode::DASHBOARD_INTEGRATION_ERROR => array(
            'body',
            'transaction',
            'mode',
        ),

    );

    /**
     * Return fields for a trace event
     *
     * @param $eventCode event code
     */
    public static function getFields($traceCode)
    {
        if (isset(self::$fields[$traceCode]) === false)
            return [];

        return self::$fields[$traceCode];
    }

    public static function checkFields($code, $fields)
    {
        $requiredFields = self::getFields($code);

        $missingFields = array_diff($requiredFields, $fields);

        if (count($missingFields) > 0)
        {
            // @todo: Finalize the fields to be logged and remote the extra ones.
            // Currently all log messages have invalid fields defined.
            // throw new Exception\InvalidArgumentException(
            //     implode(',', $missingFields) . ' are missing from trace record');
        }

    }
}