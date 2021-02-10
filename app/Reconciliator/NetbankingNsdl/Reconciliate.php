<?php

namespace RZP\Reconciliator\NetbankingNsdl;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const REC_ID                = 'REC_ID';
    const CHANNELID             = 'CHANNELID';
    const APPID                 = 'APPID';
    const PARTNERID             = 'PARTNERID';
    const PGTXNID               = 'PGTXNID';
    const MOBILENO              = 'MOBILENO';
    const EMAILID               = 'EMAILID';
    const ACCOUNTNO             = 'ACCOUNTNO';
    const AMOUNT                = 'AMOUNT';
    const CURRENCY              = 'CURRENCY';
    const REMARKS               = 'REMARKS';
    const RESPONSEURL           = 'RESPONSEURL';
    const REQBYTYPE             = 'REQBYTYPE';
    const REQBYID               = 'REQBYID';
    const TXNDATE               = 'TXNDATE';
    const PAYMODE               = 'PAYMODE';
    const ADDINFO1              = 'ADDINFO1';
    const ADDINFO2              = 'ADDINFO2';
    const ADDINFO3              = 'ADDINFO3';
    const ADDINFO4              = 'ADDINFO4';
    const ADDINFO5              = 'ADDINFO5';
    const CRE_DT                = 'R_CRE_DT';
    const STATUS                = 'STATUS';
    const RESPONSEMSG           = 'RESPONSEMSG';
    const BANKREFNO             = 'BANKREFNO';
    const SUCCESS_STATUS        = 'S';

    protected $columnHeaders = [
        self::REC_ID,
        self::CHANNELID,
        self::APPID,
        self::PARTNERID,
        self::PGTXNID,
        self::MOBILENO,
        self::EMAILID,
        self::ACCOUNTNO,
        self::AMOUNT,
        self::CURRENCY,
        self::REMARKS,
        self::RESPONSEURL,
        self::REQBYTYPE,
        self::REQBYID,
        self::TXNDATE,
        self::PAYMODE,
        self::ADDINFO1,
        self::ADDINFO2,
        self::ADDINFO3,
        self::ADDINFO4,
        self::ADDINFO5,
        self::CRE_DT,
        self::STATUS,
        self::PAYMODE,
        self::BANKREFNO,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    public function getDelimiter()
    {
        return ',';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
