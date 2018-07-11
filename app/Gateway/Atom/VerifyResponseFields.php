<?php

namespace RZP\Gateway\Atom;

class VerifyResponseFields
{
    const MERCHANT_ID            = 'MerchantID';
    const TRANSACTION_ID         = 'MerchantTxnID';
    const AMOUNT                 = 'AMT';
    const STATUS                 = 'VERIFIED';
    const BANK_TRANSACTION_ID    = 'BID';
    const BANK_NAME              = 'bankname';
    const GATEWAY_TRANSACTION_ID = 'atomtxnId';
    const MODE                   = 'discriminator';
    const SURCHARGE              = 'surcharge';
}
