<?php

namespace RZP\Gateway\Upi\Sbi;

class Constants
{
    const NOT_APPLICABLE   = 'NA';
    const EXPIRY_TIME      = '1110';
    const TRANSACTION_NOTE = 'Collect from ';
    const REFUND_REMARKS   = 'Refund transaction';

    // TODO: See if it makes sense to move the constants below to their own classes
    const PAYMENT_TYPE     = 'P2P';
    const REFUND           = 'Refund';
}