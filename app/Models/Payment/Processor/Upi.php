<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\BANK;
use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;

class Upi
{
    const ABHY = 'ABHY';
    const ABPB = 'ABPB';
    const ACBX = 'ACBX';
    const ADBX = 'ADBX';
    const AGVX = 'AGVX';
    const AIRP = 'AIRP';
    const ALLA = 'ALLA';
    const AMCB = 'AMCB';
    const ANDB = 'ANDB';
    const APBL = 'APBL';
    const APGB = 'APGB';
    const APGV = 'APGV';
    const APMC = 'APMC';
    const ASBL = 'ASBL';
    const AUBL = 'AUBL';
    const AUGX = 'AUGX';
    const AXIS = 'AXIS';   // for Axis UAT
    const BACB = 'BACB';
    const BARB_R = 'BARB_R';
    const BCBM = 'BCBM';
    const BDBL = 'BDBL';
    const BGBX = 'BGBX';
    const BGGX = 'BGGX';
    const BHUX = 'BHUX';
    const BKDN = 'BKDN';
    const BKID = 'BKID';
    const BRDX = 'BRDX';
    const BRGX = 'BRGX';
    const BUGX = 'BUGX';
    const CBIN = 'CBIN';
    const CGBX = 'CGBX';
    const CGGX = 'CGGX';
    const CITI = 'CITI';
    const CIUB = 'CIUB';
    const CNRB = 'CNRB';
    const COLX = 'COLX';
    const CORP = 'CORP';
    const COSB = 'COSB';
    const CSBK = 'CSBK';
    const DBSS = 'DBSS';
    const DCBL = 'DCBL';
    const DEGX = 'DEGX';
    const DEUT = 'DEUT';
    const DGBX = 'DGBX';
    const DLXB = 'DLXB';
    const DNSB = 'DNSB';
    const ESFB = 'ESFB';
    const ESMF = 'ESMF';
    const FDRL = 'FDRL';
    const FINO = 'FINO';
    const FSFB = 'FSFB';
    const GSCB = 'GSCB';
    const HCBL = 'HCBL';
    const HDFC = 'HDFC';
    const HGBX = 'HGBX';
    const HMBX = 'HMBX';
    const HSBC = 'HSBC';
    const HUTX = 'HUTX';
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
    const KDIX = 'KDIX';
    const KGRB = 'KGRB';
    const KGSX = 'KGSX';
    const KJSB = 'KJSB';
    const KKBK = 'KKBK';
    const KLGB = 'KLGB';
    const KVBL = 'KVBL';
    const KVGB = 'KVGB';
    const LAVB_R = 'LAVB_R';
    const LDRX = 'LDRX';
    const MAHB = 'MAHB';
    const MAHG = 'MAHG';
    const MBGX = 'MBGX';
    const MCBL = 'MCBL';
    const MDGX = 'MDGX';
    const MERX = 'MERX';
    const MGRB = 'MGRB';
    const MRBX = 'MRBX';
    const MRTX = 'MRTX';
    const MSBL = 'MSBL';
    const MSCI = 'MSCI';
    const MSLM = 'MSLM';
    const MSNU = 'MSNU';
    const MUBL = 'MUBL';
    const MZRX = 'MZRX';
    const NKGS = 'NKGS';
    const NSPB = 'NSPB';
    const NTBL = 'NTBL';
    const ORBC = 'ORBC';
    const PASX = 'PASX';
    const PGBX = 'PGBX';
    const PJSB = 'PJSB';
    const PKGB = 'PKGB';
    const PMCB = 'PMCB';
    const PRTH = 'PRTH';
    const PSIB = 'PSIB';
    const PUGX = 'PUGX';
    const PUNB_R = 'PUNB_R';
    const PURX = 'PURX';
    const PYTM = 'PYTM';
    const RATN = 'RATN';
    const RMGB = 'RMGB';
    const RNSB = 'RNSB';
    const SAGX = 'SAGX';
    const SBIN = 'SBIN';
    const SCBL = 'SCBL';
    const SCOB = 'SCOB';
    const SIBL = 'SIBL';
    const SPCB = 'SPCB';
    const SRCB = 'SRCB';
    const SSDX = 'SSDX';
    const SUBX = 'SUBX';
    const SURY = 'SURY';
    const SUTB = 'SUTB';
    const SUVX = 'SUVX';
    const SVCB = 'SVCB';
    const SYNB = 'SYNB';
    const TBSB = 'TBSB';
    const TGBX = 'TGBX';
    const TJSB = 'TJSB';
    const TMBL = 'TMBL';
    const TSAB = 'TSAB';
    const TUDX = 'TUDX';
    const TUMX = 'TUMX';
    const UBIN = 'UBIN';
    const UCBA = 'UCBA';
    const UJVN = 'UJVN';
    const UMSX = 'UMSX';
    const UTBI = 'UTBI';
    const UTGX = 'UTGX';
    const UTIB = 'UTIB';
    const VARA = 'VARA';
    const VCOB = 'VCOB';
    const VGBX = 'VGBX';
    const VIJB = 'VIJB';
    const VSBL = 'VSBL';
    const VVSB = 'VVSB';
    const XJKG = 'XJKG';
    const YESB = 'YESB';

    protected static $supportedUpiBanks = [
        self::ABHY,
        self::ABPB,
        self::ACBX,
        self::ADBX,
        self::AGVX,
        self::AIRP,
        self::ALLA,
        self::AMCB,
        self::ANDB,
        self::APBL,
        self::APGB,
        self::APGV,
        self::APMC,
        self::ASBL,
        self::AUBL,
        self::AUGX,
        self::AXIS,   // for Axis UAT
        self::BACB,
        self::BARB_R,
        self::BCBM,
        self::BDBL,
        self::BGBX,
        self::BGGX,
        self::BHUX,
        self::BKDN,
        self::BKID,
        self::BRDX,
        self::BRGX,
        self::BUGX,
        self::CBIN,
        self::CGBX,
        self::CGGX,
        self::CITI,
        self::CIUB,
        self::CNRB,
        self::COLX,
        self::CORP,
        self::COSB,
        self::CSBK,
        self::DBSS,
        self::DCBL,
        self::DEGX,
        self::DEUT,
        self::DGBX,
        self::DLXB,
        self::DNSB,
        self::ESFB,
        self::ESMF,
        self::FDRL,
        self::FINO,
        self::FSFB,
        self::GSCB,
        self::HCBL,
        self::HDFC,
        self::HGBX,
        self::HMBX,
        self::HSBC,
        self::HUTX,
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
        self::KDIX,
        self::KGRB,
        self::KGSX,
        self::KJSB,
        self::KKBK,
        self::KLGB,
        self::KVBL,
        self::KVGB,
        self::LAVB_R,
        self::LDRX,
        self::MAHB,
        self::MAHG,
        self::MBGX,
        self::MCBL,
        self::MDGX,
        self::MERX,
        self::MGRB,
        self::MRBX,
        self::MRTX,
        self::MSBL,
        self::MSCI,
        self::MSLM,
        self::MSNU,
        self::MUBL,
        self::MZRX,
        self::NKGS,
        self::NSPB,
        self::NTBL,
        self::ORBC,
        self::PASX,
        self::PGBX,
        self::PJSB,
        self::PKGB,
        self::PMCB,
        self::PRTH,
        self::PSIB,
        self::PUGX,
        self::PUNB_R,
        self::PURX,
        self::PYTM,
        self::RATN,
        self::RMGB,
        self::RNSB,
        self::SAGX,
        self::SBIN,
        self::SCBL,
        self::SCOB,
        self::SIBL,
        self::SPCB,
        self::SRCB,
        self::SSDX,
        self::SUBX,
        self::SURY,
        self::SUTB,
        self::SUVX,
        self::SVCB,
        self::SYNB,
        self::TBSB,
        self::TGBX,
        self::TJSB,
        self::TMBL,
        self::TSAB,
        self::TUDX,
        self::TUMX,
        self::UBIN,
        self::UCBA,
        self::UJVN,
        self::UMSX,
        self::UTBI,
        self::UTGX,
        self::UTIB,
        self::VARA,
        self::VCOB,
        self::VGBX,
        self::VIJB,
        self::VSBL,
        self::VVSB,
        self::XJKG,
        self::YESB,
    ];

    public static function exists($bank)
    {
        return defined(__CLASS__ . '::' . strtoupper($bank));
    }

    public static $defaultInconsistentBankCodesMapping = [
        BANK::BARB => 'BARB_R',
        BANK::PUNB => 'PUNB_R',
        BANK::LAVB => 'LAVB_R',
    ];

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
