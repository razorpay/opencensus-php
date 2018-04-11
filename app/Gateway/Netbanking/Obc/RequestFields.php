<?php

namespace RZP\Gateway\Netbanking\Obc;

class RequestFields
{
    /**
     * Authorize parameters
     */
    const RETURN_URL      = 'RU';

    const CATEGORY_ID     = 'CATEGORY_ID';

    const QUERY_STRING    = 'QS';

    /**
     * Authorize query string parameters
     */
    const TRAN_CRN        = 'TRAN_CRN';

    const TXN_AMOUNT      = 'TXN_AMT';

    const PAYEE_ID        = 'PID';

    const PAY_REF_NUM     = 'PRN';

    const ITEM_CODE       = 'ITC';

    /**
     * Verify specific fields
     */
    const AMOUNT          = 'AMT';

    const CRN             = 'CRN';

    const BID             = 'BID';
}
