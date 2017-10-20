<?php

namespace RZP\Gateway\Upi\Sbi;

class Constants
{
    /**
     * Used in Additional Info fields in each API request
     */
    const NOT_APPLICABLE   = 'NA';

    const EXPIRY_TIME      = '1110';
    const TRANSACTION_NOTE = 'Collect from ';
    const REFUND_REMARKS   = 'Refund transaction';

    // TODO: See if it makes sense to move the constants below to their own classes

    /**
     * Used in refund API - always P2P
     */
    const PAYMENT_TYPE     = 'P2P';

    const REFUND           = 'Refund';
}