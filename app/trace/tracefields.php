<?php

namespace Trace;

use Trace\TraceEvent;

class TraceFields
{
    protected static $fields = array(
        TraceEvent::NEW_TRANSACTION_REQUEST       => array(
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