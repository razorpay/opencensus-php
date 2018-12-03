<?php

namespace RZP\Gateway\Upi\Sbi;

class Constants
{
    /**
     * Used in Additional Info fields in each API request
     */
    const NOT_APPLICABLE   = 'NA';

    const TRANSACTION_NOTE = 'Collect from Razorpay';

    /**
     * Used in refund API - always P2P
     */
    const PAYMENT_TYPE     = 'P2P';

    const REFUND           = 'Refund';

    /**
     * For VPA validation request
     */
    const VA_REQUEST_TYPE  = 'T';
}
