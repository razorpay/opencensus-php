<?php

namespace RZP\Reconciliator\NetbankingNsdl;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const REC_ID                = 'rec_id';
    const CHANNELID             = 'channelid';
    const APPID                 = 'appid';
    const PARTNERID             = 'partnerid';
    const PGTXNID               = 'pgtxnid';
    const MOBILENO              = 'mobileno';
    const EMAILID               = 'emailid';
    const ACCOUNTNO             = 'accountno';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const REMARKS               = 'remarks';
    const RESPONSEURL           = 'responseurl';
    const REQBYTYPE             = 'reqbytype';
    const REQBYID               = 'reqbyid';
    const TXNDATE               = 'txndate';
    const PAYMODE               = 'paymode';
    const ADDINFO1              = 'addinfo1';
    const ADDINFO2              = 'addinfo2';
    const ADDINFO3              = 'addinfo3';
    const ADDINFO4              = 'addinfo4';
    const ADDINFO5              = 'addinfo5';
    const CRE_DT                = 'r_cre_dt';
    const STATUS                = 'status';
    const RESPONSEMSG           = 'responsemsg';
    const BANKREFNO             = 'bankrefno';
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
