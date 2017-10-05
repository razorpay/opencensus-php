<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class RequestFields
{
    const VERSION         = 'VER';
    const CORP_ID         = 'CID';
    const TYPE            = 'TYP';
    const REQUEST_ID      = 'RID';
    const CUSTOMER_REF_NO = 'CRN';
    const CURRENCY        = 'CNY';
    const AMOUNT          = 'AMT';
    const RETURN_URL      = 'RTU';
    const PRE_POP_INFO    = 'PPI';
    const RESERVE_FIELD_1 = 'RE1';
    const RESERVE_FIELD_2 = 'RE2';
    const RESERVE_FIELD_3 = 'RE3';
    const RESERVE_FIELD_4 = 'RE4';
    const RESERVE_FIELD_5 = 'RE5';
    const CHECKSUM        = 'CKS';

    const DATA            = 'i';
}
