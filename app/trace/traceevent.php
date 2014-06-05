<?php

namespace Trace;

class TraceEvent
{
    const NEW_TRANSACTION_REQUEST           = 'NEW_TRANSACTION_REQUEST';
    const TRANSACTION_CREATED               = 'TRANSACTION_CREATED';
    const TRANSACTION_CREATE_FAILED         = 'TRANSACTION_CREATE_FAILED';
    const TRANSACTION_AUTHED                = 'TRANSACTION_AUTHED';
    const TRANSACTION_FAILED                = 'TRANSACTION_FAILED';
    const TRANSACTION_REFUNDED              = 'TRANSACTION_REFUNDED';
    // const TRANSACTION_REFUND_FAILED         = 'TRANSACTION_REFUND_FAILED';
    const TRANSACTION_CAPTURED              = 'TRANSACTION_CAPTURED';
    // const TRANSACTION_CAPTURE_FAILED        = 'TRANSACTION_CAPTURE_FAILED';
    const TRANSACTION_EXCEPTION             = 'TRANSACTION_EXCEPTION';

    protected static $message = array(
        self::NEW_TRANSACTION_REQUEST       => 'Request for new transaction received',
        self::TRANSACTION_CREATED           => 'New transaction created',
        self::TRANSACTION_CREATE_FAILED     => 'Transaction creation failed',
        self::TRANSACTION_AUTHED            => 'Transaction authenticated successfully',
        self::TRANSACTION_FAILED            => 'Transaction failed',
        self::TRANSACTION_REFUNDED          => 'Transaction refunded successfully',
        self::TRANSACTION_CAPTURED          => 'Transaction captured successfully',
        self::TRANSACTION_EXCEPTION         => 'Transaction exception occured'
    );

    /**
     * Translate event code to message
     *
     * @param $event event code
     * @return 
     */
    public static function translateEvent($event)
    {
        $eventMessage = self::$message[$event];

        return $eventMessage;
    }
}