<?php

namespace RZP\Gateway\Upi\Sbi;

class Constants
{
    /**
     * Used in Additional Info fields in each API request
     */
    const NOT_APPLICABLE   = 'NA';

    /**
     * Collect request expiry time in minutes
     */
    const EXPIRY_TIME      = '5';

    const TRANSACTION_NOTE = 'Collect from ';

    /**
     * Used in refund API - always P2P
     */
    const PAYMENT_TYPE     = 'P2P';

    const REFUND           = 'Refund';
}
