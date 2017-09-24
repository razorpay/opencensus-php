<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class ResponseFields
{
    const VERSION         = 'VER';
    const CORP_ID         = 'CID';
    const TYPE            = 'TYP';
    const CUSTOMER_REF_NO = 'CRN';
    const CURRENCY        = 'CNY';
    const AMOUNT          = 'AMT';
    const BANK_REF_NO     = 'BRN';
    const STATUS_CODE     = 'STC';
    const REMARKS         = 'RMK';
    const TRANS_REF_NO    = 'TRN';
    const TRANS_EXEC_TIME = 'TET';
    const PAYMENT_MODE    = 'PMD';
    const CHECKSUM        = 'CKS';
}