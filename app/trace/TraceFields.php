<?php

namespace Trace;

use Trace\TraceCode;

class TraceFields
{
    protected static $fields = array(
        TraceCode::TRANSACTION_NEW_REQUEST => array(
            'amount',
            'currency',
            'hold'
        ),

        TraceCode::TRANSACTION_CREATED => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),

        TraceCode::TRANSACTION_AUTH_SUCCESS => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),

        TraceCode::TRANSACTION_CREATE_FAILED => array(),

        TraceCode::TRANSACTION_FAILED => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),

        TraceCode::TRANSACTION_AUTH_FAILURE => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),

        TraceCode::TRANSACTION_REFUND_FAILURE => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),

        TraceCode::TRANSACTION_CAPTURE_FAILURE => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),

        TraceCode::TRANSACTION_REFUND_SUCCESS => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),

        TraceCode::TRANSACTION_CAPTURE_SUCCESS => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),

        TraceCode::TRANSACTION_EXCEPTION => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),


        TraceCode::GATEWAY_ENROLL_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLL_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLL_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_SUPPORT_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_SUPPORT_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_SUPPORT_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_EXCEPTION => array(
            'file'
        ),
    );

    /**
     * Return fields for a trace event
     *
     * @param $eventCode event code
     */
    public static function getFields($eventCode)
    {
        $fields = self::$fields[$eventCode];

        return $fields;
    }

    public static function checkFields($code, $fields)
    {
        $requiredFields = self::getFields($code);

        $missingFields = array_diff($requiredFields, $fields);

        if (count($missingFields) > 0)
        {
            // @todo: Finalize the fields to be logged and remote the extra ones.
            // Currently all log messages have invalid fields defined.
            // throw new \InvalidArgumentException(
            //     implode(',', $missingFields) . ' are missing from trace record');
        }

    }
}