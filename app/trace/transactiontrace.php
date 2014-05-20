<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class TransactionTrace extends Trace
{
    // string code for events
    const NEW_TRANSACTION = 'NEW_TRANSACTION';
    const GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_HDFC_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const MERCHANT_ID_MISMATCH = 'MERCHANT_ID_MISMATCH';
    const REFUND_SUCCESSFUL = 'REFUND_SUCCESSFUL';
    const INVALID_TRANSACTION_ID = 'INVALID_TRANSACTION_ID';

    protected $component = 'transaction';

    protected static $compulsoryFields = array(
        'message',
        'transaction_id');

    protected static $fields = array(
        self::NEW_TRANSACTION => array(
            'status'),
        self::REFUND_SUCCESSFUL => array(
            'status',
            'previous_status'),
        );
}