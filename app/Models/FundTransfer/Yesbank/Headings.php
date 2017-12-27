<?php

namespace RZP\Models\FundTransfer\Yesbank;

class Headings
{
    const BENEFICIARY_NAME          = 'Beneficiary_Name';
    const IFSC_CODE                 = 'IFSC Code';
    const BENEFICIARY_ACC_NO        = 'Beneficiary_Acc_No';
    const BENEFICIARY_BANK          = 'Beneficiary_Bank';
    const AMOUNT                    = 'Amount';

    // Extra headings in response file
    const STATUS_OF_TRANSACTION     = 'Status Of transaction';
    const UTR_NUMBER                = 'UTR number';
    const REMARKS                   = 'Remarks';
    const DATE_TIME                 = 'DateTime';
    const CMS_REF_NO                = 'Cms. ref no.';
    const DUMMY                     = 'Dummy';

    protected static $requestFileheadings = [
        self::BENEFICIARY_NAME,
        self::IFSC_CODE,
        self::BENEFICIARY_ACC_NO,
        self::BENEFICIARY_BANK,
        self::AMOUNT,
    ];

    protected static $extraHeadingsInResponseFile = [
        self::STATUS_OF_TRANSACTION,
        self::UTR_NUMBER,
        self::REMARKS,
        self::DATE_TIME,
        self::CMS_REF_NO,
        self::DUMMY,
    ];

    public static function getRequestFileHeadings()
    {
        return static::$requestFileheadings;
    }

    public static function getResponseFileHeadings()
    {
        return array_merge(static::$requestFileheadings, static::$extraHeadingsInResponseFile);
    }
}