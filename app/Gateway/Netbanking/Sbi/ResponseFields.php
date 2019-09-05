<?php

namespace RZP\Gateway\Netbanking\Sbi;

class ResponseFields
{
    const BANK_REF_NO   = 'sbirefno';
    const AMOUNT        = 'amount';
    const REF_NO        = 'ref_no';
    const STATUS        = 'status';
    const STATUS_DESC   = 'desc';
    const PAYMENT_ID    = 'payment_id';
    const CHECKSUM      = 'checkSum';

    // emandate
    const MANDATE_SBI_REF         = 'SBI_Ref';
    const MANDATE_TXN_AMOUNT      = 'Txn_Amount';
    const MANDATE_PAYMENT_ID      = 'Payment_ID';
    const MANDATE_SBI_STATUS      = 'SBI_Status';
    const MANDATE_SBI_DESCRIPTION = 'SBI_Description';

    // encrypted data field
    const ENCDATA       = 'encdata';
}
