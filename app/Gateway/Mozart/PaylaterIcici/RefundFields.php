<?php

namespace RZP\Gateway\Mozart\PaylaterIcici;

class RefundFields
{
    const SERIAL_NO          = 'Sr No';
    const PAYEE_ID           = 'Payee_id';
    const SPID               = 'SPID';
    const BANK_REFERENCE_ID  = 'Bank Reference No.';
    const TRANSACTION_DATE   = 'Transaction Date';
    const TRANSACTION_AMOUNT = 'Transaction Amount';
    const REFUND_AMOUNT      = 'Refund Amount';
    const TRANSACTION_ID     = 'Transaction Id';
    const REFUND_MODE        = 'Reversal/Cancellation';
    const REMARKS            = 'Remarks';

    const REFUND_FIELDS = [
        self::SERIAL_NO,
        self::PAYEE_ID,
        self::SPID,
        self::BANK_REFERENCE_ID,
        self::TRANSACTION_DATE,
        self::TRANSACTION_AMOUNT,
        self::REFUND_AMOUNT,
        self::TRANSACTION_ID,
        self::REFUND_MODE,
        self::REMARKS,
    ];
}
