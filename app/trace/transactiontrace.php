<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class TransactionTrace extends Trace
{
    // string code for events
    const REQUEST_FOR_NEW_TRANSACTION = 'REQUEST_FOR_NEW_TRANSACTION';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const NEW_TRANSACTION = 'NEW_TRANSACTION';
    const REFUND_SUCCESSFUL = 'REFUND_SUCCESSFUL';
    const INVALID_TRANSACTION_ID = 'INVALID_TRANSACTION_ID';

    protected $component = 'transaction';

    protected static $compulsoryFields = array(
        'message',);

    protected static $fields = array(
        self::REQUEST_FOR_NEW_TRANSACTION => array(),
        self::CARD_NOT_PROVIDED => array(),
        self::NEW_TRANSACTION => array(
            'transaction_id',
            'status'),
        self::REFUND_SUCCESSFUL => array(
            'transaction_id',
            'status',
            'previous_status'),
        self::INVALID_TRANSACTION_ID => array(
            'transaction_id'),
        );
}