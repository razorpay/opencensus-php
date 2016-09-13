<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

/**
 * Airtel Specific date formats are stored here.
 */
class DateFormat
{
    const REQUEST_DATE_FORMAT     = 'mdYHis';
    const TRAN_DATE_FORMAT        = 'dmYHis';
    const FDC_TXN_DATE_FORMAT     = 'mdYHis';
    const NEW_FDC_TXN_DATE_FORMAT = 'd/M/Y H:i';
    const INR                     = 'INR';
}
