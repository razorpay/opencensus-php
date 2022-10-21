<?php

namespace RZP\Models\Card;

class Issuer
{
    const ALLA = 'ALLA';    // Allahabad Bank
    const ANDB = 'ANDB';
    const AXIS = 'AXIS';
    const BARB = 'BARB';
    const BOFA = 'BOFA';    // Bank of America
    const CITI = 'CITI';
    const HDFC = 'HDFC';
    const IBKL = 'IBKL';    // IDBI Bank
    const ICIC = 'ICIC';
    const KKBK = 'KKBK';    // Kotak Mahindra
    const PUNB = 'PUNB';
    const SBIN = 'SBIN';
    const UTIB = 'UTIB';
    const INDB = 'INDB';
    const SCBL = 'SCBL';    //SCBL can be the issuer of Amex cards as well and Standard Chartered cards
    const HSBC = 'HSBC';
    const CNRB = 'CNRB';
    const UBIN = 'UBIN';
    const RATN = 'RATN';
    const YESB = 'YESB';
    const IOBA = 'IOBA';
    const SYNB = 'SYNB';
    const CORP = 'CORP';
    const BKID = 'BKID';
    const MAHB = 'MAHB';    // Bank of Maharashtra
    const DCBL = 'DCBL';    // Development Credit Bank Ltd
    const IDFB = 'IDFB';    // IDFC Bank Limited
    const ORBC = 'ORBC';    // Oriental Bank of Commerce
    const FDRL = 'FDRL';    // The Federal Bank Ltd
    const SIBL = 'SIBL';    // The South Indian Bank Ltd
    const CBIN = 'CBIN';    // Central Bank of India
    const IDIB = 'IDIB';    // Indian Bank
    const JAKA = 'JAKA';    // Jammu And Kashmir Bank Ltd
    const LAVB = 'LAVB';    // Laxmi Vilas Bank Ltd
    const AUBL = 'AUBL';    // AU small finance bank
    const STCB = 'STCB';

    protected static $issuers = [
        self::ALLA,
        self::ANDB,
        self::AXIS,
        self::BARB,
        self::BOFA,
        self::CITI,
        self::HDFC,
        self::IBKL,
        self::ICIC,
        self::KKBK,
        self::PUNB,
        self::SBIN,
        self::UTIB,
        self::INDB,
        self::SCBL,
        self::HSBC,
        self::CNRB,
        self::UBIN,
        self::RATN,
        self::YESB,
        self::IOBA,
        self::SYNB,
        self::CORP,
        self::BKID,
        self::BKID,
        self::MAHB,
        self::DCBL,
        self::IDFB,
        self::ORBC,
        self::FDRL,
        self::SIBL,
        self::CBIN,
        self::IDIB,
        self::JAKA,
        self::LAVB,
        self::AUBL,
    ];

    protected static $onecardIssuers = [
        self::STCB,
        self::BARB,
        self::IDFB,
        self::FDRL,
        self::SIBL,
    ];

    public static function getAllIssuers():array
    {
        return self::$issuers;
    }

    public static function getAllOnecardIssuers():array
    {
        return self::$onecardIssuers;
    }
}
