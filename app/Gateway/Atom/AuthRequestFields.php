<?php

namespace RZP\Gateway\Atom;

class AuthRequestFields
{
    const LOGIN                      = 'login';
    const PASSWORD                   = 'pass';
    const TRANSACTION_TYPE           = 'ttype';
    const PRODUCT_ID                 = 'prodid';
    const AMOUNT                     = 'amt';
    const TRANSACTION_CURRENCY       = 'txncurr';
    const TRANSACTION_SERVICE_CHARGE = 'txnscamt';
    const CLIENT_CODE                = 'clientcode';
    const TRANSACTION_ID             = 'txnid';
    const DATE                       = 'date';
    const CUSTOMER_ACCOUNT           = 'custacc';
    const RETURN_URL                 = 'ru';
    const SIGNATURE                  = 'signature';
    // bank_id is not a field in integration doc but keeping it based on sample request provided
    const BANK_ID                    = 'bankid';
    const UDF9                       = 'udf9';
}
