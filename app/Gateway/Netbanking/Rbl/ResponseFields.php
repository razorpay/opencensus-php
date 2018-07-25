<?php

namespace RZP\Gateway\Netbanking\Rbl;

class ResponseFields
{
    const STATUS             = 'STATUS';
    const BANK_REFERENCE     = 'REFNO';
    const MERCHANT_REFERENCE = 'PRN';


    //verify response fields
    const CURRENCY           = 'TRANSACTION_CURRENCY_ARRAY';
    const ENTRY_STATUS       = 'ENTRY_STATUS';
    const REFERENCE_ID       = 'REFERENCE_ID_ARRAY';
    const TRANSACTION_STATUS = 'RetrieveTransactionStatus';
    const STATUS_RECORD      = 'RetrieveTransactionStatus_REC';
    const AMOUNT             = 'ENTRY_AMOUNT_ARRAY';
}
