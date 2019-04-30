<?php

namespace RZP\Gateway\Netbanking\Federal;

class ResponseFields
{
    const ITEM_CODE       = 'ITC';
    const PAYMENT_ID      = 'PRN';
    const AMOUNT          = 'AMT';
    const BANK_PAYMENT_ID = 'BID';
    const PAID            = 'PAID';
    const STATE_FLAG      = 'STATFLG';
    const PAYEE_ID        = 'PID';
    const STATUS          = 'status';
    const VERIFY_BODY     = 'BODY';
    const HASH            = 'RESP_HASH';
}
