<?php

namespace RZP\Gateway\Atom;

class AuthResponseFields
{
    const GATEWAY_PAYMENT_ID     = 'mmp_txn';
    const TRANSACTION_ID         = 'mer_txn';
    const AMOUNT                 = 'amt';
    const SURCHARGE              = 'surcharge';
    const PRODUCT_ID             = 'prod';
    const DATE                   = 'date';
    const BANK_TRANSACTION_ID    = 'bank_txn';
    const STATUS_CODE            = 'f_code';
    const CLIENT_CODE            = 'clientcode';
    const BANK_NAME              = 'bank_name';
    const DISCRIMINATOR          = 'discriminator';
    const SIGNATURE              = 'signature';
}
