<?php

namespace RZP\Gateway\Netbanking\Base;

class Recurring
{
    /**
     * Number of years from now to set for end_date.
     * In charge at will, we don't know how many years
     * is the subscription required for.
     */
    const MAX_END_DATE_FROM_NOW = 10;

    /**
     * Maximum amount that we should get the e-mandate
     * registration for. We cannot do recurring charge
     * for more than this amount.
     *
     * Note: The amount is is paise.
     */
    const MAX_AMOUNT            = 10000000;
}
