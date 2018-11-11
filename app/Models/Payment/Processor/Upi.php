<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;

class Upi
{
    const ABPB = 'ABPB';
    const AIRP = 'AIRP';
    const ALLA = 'ALLA';
    const ANDB = 'ANDB';
    const APBL = 'APBL';
    const APGB = 'APGB';
    const APGV = 'APGV';
    const APMC = 'APMC';
    const ASBL = 'ASBL';
    const BACB = 'BACB';
    const BARB = 'BARB';
    const BDBL = 'BDBL';
    const BKDN = 'BKDN';
    const BKID = 'BKID';
    const CBIN = 'CBIN';
    const CITI = 'CITI';
    const CIUB = 'CIUB';
    const CNRB = 'CNRB';
    const CORP = 'CORP';
    const COSB = 'COSB';
    const CSBK = 'CSBK';
    const DBSS = 'DBSS';
    const DCBL = 'DCBL';
    const DEUT = 'DEUT';
    const DLXB = 'DLXB';
    const DNSB = 'DNSB';
    const ESFB = 'ESFB';
    const ESMF = 'ESMF';
    const FDRL = 'FDRL';
    const FINO = 'FINO';
    const GSCB = 'GSCB';
    const HCBL = 'HCBL';
    const HDFC = 'HDFC';
    const HSBC = 'HSBC';
    const IBKL = 'IBKL';
    const ICIC = 'ICIC';
    const IDFB = 'IDFB';
    const IDIB = 'IDIB';
    const INDB = 'INDB';
    const IOBA = 'IOBA';
    const IPOS = 'IPOS';
    const JAKA = 'JAKA';
    const JIOP = 'JIOP';
    const JJSB = 'JJSB';
    const JSBP = 'JSBP';
    const JSFB = 'JSFB';
    const KAIJ = 'KAIJ';
    const KARB = 'KARB';
    const KJSB = 'KJSB';
    const KKBK = 'KKBK';
    const KLGB = 'KLGB';
    const KVBL = 'KVBL';
    const KVGB = 'KVGB';
    const LAVB = 'LAVB';
    const MAHB = 'MAHB';
    const MAHG = 'MAHG';
    const MCBL = 'MCBL';
    const MSCI = 'MSCI';
    const MSNU = 'MSNU';
    const NKGS = 'NKGS';
    const ORBC = 'ORBC';
    const PJSB = 'PJSB';
    const PKGB = 'PKGB';
    const PMCB = 'PMCB';
    const PRTH = 'PRTH';
    const PSIB = 'PSIB';
    const PUNB = 'PUNB';
    const PYTM = 'PYTM';
    const RATN = 'RATN';
    const RNSB = 'RNSB';
    const SBIN = 'SBIN';
    const SCBL = 'SCBL';
    const SIBL = 'SIBL';
    const SPCB = 'SPCB';
    const SRCB = 'SRCB';
    const SUTB = 'SUTB';
    const SVCB = 'SVCB';
    const SYNB = 'SYNB';
    const TBSB = 'TBSB';
    const TJSB = 'TJSB';
    const TMBL = 'TMBL';
    const TSAB = 'TSAB';
    const UBIN = 'UBIN';
    const UCBA = 'UCBA';
    const UJVN = 'UJVN';
    const UTBI = 'UTBI';
    const UTIB = 'UTIB';
    const VARA = 'VARA';
    const VIJB = 'VIJB';
    const VSBL = 'VSBL';
    const VVSB = 'VVSB';
    const YESB = 'YESB';


    protected static $supportedUpiBanks = [
        self::ABPB,
        self::AIRP,
        self::ALLA,
        self::ANDB,
        self::APBL,
        self::APGB,
        self::APGV,
        self::APMC,
        self::ASBL,
        self::BACB,
        self::BARB,
        self::BDBL,
        self::BKDN,
        self::BKID,
        self::CBIN,
        self::CITI,
        self::CIUB,
        self::CNRB,
        self::CORP,
        self::COSB,
        self::CSBK,
        self::DBSS,
        self::DCBL,
        self::DEUT,
        self::DLXB,
        self::DNSB,
        self::ESFB,
        self::ESMF,
        self::FDRL,
        self::FINO,
        self::GSCB,
        self::HCBL,
        self::HDFC,
        self::HSBC,
        self::IBKL,
        self::ICIC,
        self::IDFB,
        self::IDIB,
        self::INDB,
        self::IOBA,
        self::IPOS,
        self::JAKA,
        self::JIOP,
        self::JJSB,
        self::JSBP,
        self::JSFB,
        self::KAIJ,
        self::KARB,
        self::KJSB,
        self::KKBK,
        self::KLGB,
        self::KVBL,
        self::KVGB,
        self::LAVB,
        self::MAHB,
        self::MAHG,
        self::MCBL,
        self::MSCI,
        self::MSNU,
        self::NKGS,
        self::ORBC,
        self::PJSB,
        self::PKGB,
        self::PMCB,
        self::PRTH,
        self::PSIB,
        self::PUNB,
        self::PYTM,
        self::RATN,
        self::RNSB,
        self::SBIN,
        self::SCBL,
        self::SIBL,
        self::SPCB,
        self::SRCB,
        self::SUTB,
        self::SVCB,
        self::SYNB,
        self::TBSB,
        self::TJSB,
        self::TMBL,
        self::TSAB,
        self::UBIN,
        self::UCBA,
        self::UJVN,
        self::UTBI,
        self::UTIB,
        self::VARA,
        self::VIJB,
        self::VSBL,
        self::VVSB,
        self::YESB,
    ];

    public static function exists($bank)
    {
        return defined(__CLASS__ . '::' . strtoupper($bank));
    }

    /**
     * Returns a key value map array,
     * where each key represents a bank in IFSC code format,
     * and value represents the full name of the bank
     *
     * @return array
     */
    public static function getFullBankNamesMap()
    {
        $upiBanks = Payment\Gateway::$upiToGatewayMap;

        $bankNameMap = [];

        foreach ($upiBanks as $bank => $gateway)
        {
            $bankNameMap[$bank] = IFSC::getBankName($bank);
        }

        return $bankNameMap;
    }

    public static function isSupportedUpiBank($bank)
    {
        return (in_array($bank, self::getAllUpiBanks(), true) === true);
    }

    public static function getAllUpiBanks()
    {
        return self::$supportedUpiBanks;
    }
}
