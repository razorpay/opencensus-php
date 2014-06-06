<?php

namespace Trace;

use Trace\TraceEvent;

class TraceFields
{
    protected static $fields = array(
        TraceEvent::TRANSACTION_NEW_REQUEST       => array(
            'amount',
            'currency',
            'hold'
        ),
        TraceEvent::TRANSACTION_CREATED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),
        TraceEvent::TRANSACTION_AUTHED          => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),
        TraceEvent::TRANSACTION_CREATE_FAILED      => array(

        ),
        TraceEvent::TRANSACTION_FAILED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),
        TraceEvent::TRANSACTION_REFUNDED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),
        TraceEvent::TRANSACTION_CAPTURED         => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),
        TraceEvent::TRANSACTION_EXCEPTION    => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),


        TraceEvent::GATEWAY_ENROLL_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_ENROLL_RESPONSE => array(
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_ENROLL_ERROR => array(
            'type',
            'data',
            'error'
        ),
        TraceEvent::GATEWAY_NOT_ENROLLED_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_NOT_ENROLLED_RESPONSE => array(
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_NOT_ENROLLED_ERROR => array(
            'type',
            'data',
            'error'
        ),
        TraceEvent::GATEWAY_ENROLLED_AUTH_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_ENROLLED_AUTH_RESPONSE => array(
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_ENROLLED_AUTH_ERROR => array(
            'type',
            'data',
            'error'
        ),
        TraceEvent::GATEWAY_SUPPORT_REQUEST => array(
            'url',
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_SUPPORT_RESPONSE => array(
            'type',
            'data'
        ),
        TraceEvent::GATEWAY_SUPPORT_ERROR => array(
            'type',
            'data',
            'error'
        ),
        TraceEvent::GATEWAY_EXCEPTION => array(
            'file'
        ),
    );

    /**
     * Return fields for a trace event
     *
     * @param $eventCode event code
     */
    public static function get($eventCode)
    {
        $fields = self::$fields[$eventCode];

        return $fields;
    }
}