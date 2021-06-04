<?php

namespace RZP\Gateway\Mozart\NetbankingNsdl;

class RefundFields
{
    const TRANSACTION_DATE         = 'origtxndate';
    const CHANNELID                = 'channelid';
    const PARTNERID                = 'partnerid';
    const PGTXNID                  = 'pgtxnid';
    const REFUNDAMOUNT             = 'refundamount';
    const REFUNDTXNDATE            = 'refundtxndate';
    const CURRENCY                 = 'currency';
    const BANKREFNO                = 'bankrefno';
    const REMARKS                  = 'remarks';

    const REFUND_FIELDS = [
        self::TRANSACTION_DATE,
        self::CHANNELID,
        self::PARTNERID,
        self::PGTXNID,
        self::REFUNDAMOUNT,
        self::REFUNDTXNDATE,
        self::CURRENCY,
        self::BANKREFNO,
        self::REMARKS
    ];
}
