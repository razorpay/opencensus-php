<?php

namespace RZP\Gateway\Netbanking\Axis;

class RefundFileFields
{
    const SERIAL_NO             = 'Sr. No';
    const PAYEE_ID              = 'Payee Id'; // pid
    const PAYEE_NAME            = 'Payee name'; // RAZORPAY
    const BANK_ID               = 'BID';
    const ITEM_CODE             = 'ITC';
    const PAYMENT_REFERENCE_NO  = 'PRN';
    const AMOUNT                = 'AMOUNT';
    const DATETIME              = 'DATETIME';
    const REFUND_AMOUNT         = 'REFUND Amount';
}
