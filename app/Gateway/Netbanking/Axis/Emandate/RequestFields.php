<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class RequestFields
{
    const VERSION         = 'VER';
    const CORP_ID         = 'CID';
    const TYPE            = 'TYP';

    // To identify the payment request, we send the payment ID in this field.
    const REQUEST_ID      = 'RID';

    // To uniquely identify a customer, we send the token id for the customer here.
    const CUSTOMER_REF_NO = 'CRN';

    // Required for the payment verification request
    const BANK_REF_NO     = 'BRN';

    const CURRENCY        = 'CNY';
    const AMOUNT          = 'AMT';
    const RETURN_URL      = 'RTU';

    //
    // These are a set of values separated by the pipe symbol.
    // Format: TRANSACTION ID|AMOUNT TYPE|FREQUENCY|ACCOUNT NUMBER|SCHEDULE DATE|EXPIRY DATE|AMOUNT
    // Here, AMOUNT TYPE is not used by them and we can pass whichever value we want in this.
    // We're sending the value 'max' in it.
    //
    const PRE_POP_INFO    = 'PPI';

    //
    // We can pass MN(dont allow modification) or MY(allow modification) in this field
    // For all reserve fields other than RE1, we send empty string
    //
    const RESERVE_FIELD_1 = 'RE1';
    const RESERVE_FIELD_2 = 'RE2';
    const RESERVE_FIELD_3 = 'RE3';
    const RESERVE_FIELD_4 = 'RE4';
    const RESERVE_FIELD_5 = 'RE5';
    const CHECKSUM        = 'CKS';

    // Encrypted data goes in this field
    const DATA            = 'i';
}
