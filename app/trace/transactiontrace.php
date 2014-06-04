<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class TransactionTrace extends Trace
{
    // string code for events
    const NEW_TRANSACTION_REQUEST           = 'NEW_TRANSACTION_REQUEST';
    const TRANSACTION_CREATED               = 'TRANSACTION_CREATED';
    const TRANSACTION_CREATE_FAILED         = 'TRANSACTION_CREATE_FAILED';
    const TRANSACTION_AUTHED                = 'TRANSACTION_AUTHED';
    const TRANSACTION_FAILED                = 'TRANSACTION_FAILED';
    const TRANSACTION_REFUNDED              = 'TRANSACTION_REFUNDED';
    const TRANSACTION_REFUND_FAILED         = 'TRANSACTION_REFUND_FAILED';
    const TRANSACTION_CAPTURED              = 'TRANSACTION_CAPTURED';
    const TRANSACTION_CAPTURE_FAILED        = 'TRANSACTION_CAPTURE_FAILED';
    const TRANSACTION_EXCEPTION             = 'TRANSACTION_EXCEPTION';

    protected $component = 'transaction';

    protected static $compulsoryFields = array(
        'message',);

    protected static $fields = array(
        self::NEW_TRANSACTION_REQUEST       => array(
            'amount',
            'currency',
            'hold'
        ),
        self::TRANSACTION_CREATED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),
        self::TRANSACTION_AUTHED          => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'hold'
        ),
        self::TRANSACTION_FAILED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),
        self::TRANSACTION_CREATE_FAILED      => array(

        ),
        self::TRANSACTION_REFUNDED           => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),
        self::TRANSACTION_REFUND_FAILED    => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),
        self::TRANSACTION_CAPTURED         => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status'
        ),
        self::TRANSACTION_CAPTURE_FAILED    => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),
        self::TRANSACTION_EXCEPTION    => array(
            'id',
            'amount',
            'currency',
            'livemode',
            'status',
            'error'
        ),
    );
}