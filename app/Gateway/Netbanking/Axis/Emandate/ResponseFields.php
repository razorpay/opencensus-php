<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class ResponseFields
{
    const VERSION         = 'VER';
    const CORP_ID         = 'CID';
    const TYPE            = 'TYP';

    //
    // They return customer specific token in this fields and we store the same
    // in Netbanking\Entity::SI_TOKEN
    //
    const CUSTOMER_REF_NO = 'CRN';
    const CURRENCY        = 'CNY';
    const REQUEST_ID      = 'RID';
    const AMOUNT          = 'AMT';
    const BANK_REF_NO     = 'BRN';
    const STATUS_CODE     = 'STC';
    const REMARKS         = 'RMK';
    const TRANS_REF_NO    = 'TRN';
    const TRANS_EXEC_TIME = 'TET';
    const PAYMENT_MODE    = 'PMD';
    const CHECKSUM        = 'CKS';

    const DATA            = 'i';

    // For successful registration, mandate number will be sent, otherwise 0 will be sent
    const MANDATE_NUMBER  = 'MDN';
}
