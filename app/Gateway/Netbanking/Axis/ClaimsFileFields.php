<?php

namespace RZP\Gateway\Netbanking\Axis;

class ClaimsFileFields
{
    const SERIAL_NUMBER            = 'Sr. No';
    const PAYEE_ID                 = 'PayeeId'; // pid
    const PAYEE_NAME               = 'PayeeName'; // RAZORPAY
    const BANK_ID                  = 'BID';
    const ITEM_CODE                = 'ITC';
    const PAYMENT_REFERENCE_NUMBER = 'PRN';
    const AMOUNT                   = 'Amount';
    const DATETIME                 = 'DateTime';
}
