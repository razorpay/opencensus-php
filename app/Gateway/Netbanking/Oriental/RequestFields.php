<?php

namespace RZP\Gateway\Netbanking\Oriental;

class RequestFields
{
    /**
     * Authorize parameters
     */
    const RETURN_URL      = 'RU';
    const CATEGORY_ID     = 'CATEGORY_ID';

    /**
     * This parameter is expected to be in encrypted format if
     * CATEGORY_ID is set in the request parameters.
     * @see https://drive.google.com/drive/u/0/folders/1A5ULegmYTyv3yVgAD33wwi6wQZk50Nmt
     */
    const QUERY_STRING    = 'QS';

    /**
     * Authorize query string parameters
     */
    const TRAN_CRN        = 'TRAN_CRN';
    const TXN_AMOUNT      = 'TXN_AMT';
    const PAYEE_ID        = 'PID';
    const PAY_REF_NUM     = 'PRN';
    const ITEM_CODE       = 'ITC';
}
