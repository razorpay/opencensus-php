<?php

namespace RZP\Gateway\Netbanking\Base;

class BankingType
{
    /**
     * Retail banking type
     * @var string
     */
    const RETAIL    = 'retail';

    /**
     * Corporate banking type
     * @var string
     */
    const CORPORATE = 'corporate';

    /**
     * E-Mandate banking type
     * @var string
     */
    const EMANDATE  = 'emandate';

    /**
     * Recurring banking type
     * @var string
     */
    const RECURRING = 'recurring';
}
