<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class AuthFields
{
    // Request
    const SUCCESS_URL               = 'SU';
    const FAILURE_URL               = 'FU';
    const SERVICE                   = 'service';
    const DATE                      = 'DATE';
    const AMOUNT                    = 'AMT';
    const CURRENCY                  = 'CUR';
    const MERCHANT_SERVICE_CODE     = 'MER_SERVICE';
    const END_MERCHANT_ID           = 'END_MID';
    const CUSTOMER_MOBILE           = 'CUST_MOBILE';
    const CUSTOMER_EMAIL            = 'CUST_EMAIL';

    // Response
    const STATUS                    = 'STATUS';
    const CODE                      = 'CODE';
    const MSG                       = 'MSG';
    const TRANSACTION_DATE          = 'TRAN_DATE';
    const TRANSACTION_ID            = 'TRAN_ID';
    const TRANSACTION_AMOUNT        = 'TRAN_AMT';
    const TRANSACTION_CURRENCY      = 'TRAN_CUR';

    // Common
    const MERCHANT_ID               = 'MID';
    const TRANSACTION_REFERENCE_NO  = 'TXN_REF_NO';
    const HASH                      = 'HASH';
}
