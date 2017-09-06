<?php

namespace RZP\Gateway\Netbanking\Base;

class Recurring
{
    /**
     * Number of years from now to set for end_date.
     * For charge at will payments, we don't off hand
     * how long the merchant wants the subscription to go on
     */
    const MAX_END_YEARS = 10;
}
