<?php

namespace RZP\Gateway\Netbanking\Base;

class Recurring
{
    /**
     * Number of years from now to set for end_date.
     * For charge at will payments, we don't off hand
     * how long the merchant wants the subscription to go on
     */
    const MAX_END_DATE_FROM_NOW = 10;

    /**
     * Maximum amount that we should set the e-mandate
     * registration for. We cannot do recurring charge
     * for more than this amount.
     *
     * Note: The amount is is paise.
     */
    const MAX_AMOUNT            = 10000000;
}
