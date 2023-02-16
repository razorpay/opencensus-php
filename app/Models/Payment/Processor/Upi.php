<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\BANK;
use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;

/**
 * Use https://docs.google.com/spreadsheets/d/1ku_2PVJ2Jw72NxOGIVzVg5hKrYi22DErL3rDGtJ9T9U/edit#gid=0
 * to update the available IFSC
 * Class Upi
 * @package RZP\Models\Payment\Processor
 */
class Upi
{
    const ABHY = 'ABHY';
    const ABPB = 'ABPB';
    const ACBX = 'ACBX';
    const ACUX = 'ACUX';
    const ADBX = 'ADBX';
    const ADCC = 'ADCC';
    const AGVX = 'AGVX';
    const AIRP = 'AIRP';
    const ALLA = 'ALLA';
    const AMCB = 'AMCB';
    const AMDN = 'AMDN';
    const ANDB = 'ANDB';
    const APBL = 'APBL';
    const APGB = 'APGB';
    const APGV = 'APGV';
    const APMC = 'APMC';
    const ASBL = 'ASBL';
    const ASOX = 'ASOX';
    const AUBL = 'AUBL';
    const AUGX = 'AUGX';
    const AXIS = 'AXIS';   // for Axis UAT
    const BACB = 'BACB';
    const BARA = 'BARA';
    const BARB_R = 'BARB_R';
    const BASX = 'BASX';
    const BCBM = 'BCBM';
    const BDBL = 'BDBL';
    const BGBX = 'BGBX';
    const BGGX = 'BGGX';
    const BHUX = 'BHUX';
    const BKDN = 'BKDN';
    const BKDX = 'BKDX';
    const BKID = 'BKID';
    const BMPX = 'BMPX';
    const BNSB = 'BNSB';
    const BOFA = 'BOFA';
    const BRDX = 'BRDX';
    const BRGX = 'BRGX';
    const BUGX = 'BUGX';
    const CBIN = 'CBIN';
    const CCBL = 'CCBL';
    const CCBX = 'CCBX';
    const CGBX = 'CGBX';
    const CGGX = 'CGGX';
    const CITI = 'CITI';
    const CIUB = 'CIUB';
    const CLBL = 'CLBL';
    const CNRB = 'CNRB';
    const COAS = 'COAS';
    const COLX = 'COLX';
    const CORP = 'CORP';
    const COSB = 'COSB';
    const CRGB = 'CRGB';
    const CRUB = 'CRUB';
    const CSBK = 'CSBK';
    const CSBX = 'CSBX';
    const CTBX = 'CTBX';
    const DBSS = 'DBSS';
    const DCBL = 'DCBL';
    const DCUX = 'DCUX';
    const DEGX = 'DEGX';
    const DEUT = 'DEUT';
    const DGBX = 'DGBX';
    const DLXB = 'DLXB';
    const DNSB = 'DNSB';
    const ESFB = 'ESFB';
    const ESMF = 'ESMF';
    const FDRL = 'FDRL';
    const FGCB = 'FGCB';
    const FINO = 'FINO';
    const FSFB = 'FSFB';
    const GBCB = 'GBCD';
    const GCUX = 'GCUX';
    const GDCB = 'GDCB';
    const GSCB = 'GSCB';
    const HCBL = 'HCBL';
    const HDFC = 'HDFC';
    const HGBX = 'HGBX';
    const HMBX = 'HMBX';
    const HPSC = 'HPSC';
    const HSBC = 'HSBC';
    const HUTX = 'HUTX';
    const IBKL = 'IBKL';
    const ICIC = 'ICIC';
    const IDFB = 'IDFB';
    const IDIB = 'IDIB';
    const INDB = 'INDB';
    const IOBA = 'IOBA';
    const IPOS = 'IPOS';
    const IPSX = 'IPSX';
    const JAKA = 'JAKA';
    const JANA = 'JANA';
    const JIOP = 'JIOP';
    const JJSB = 'JJSB';
    const JMCX = 'JMCX';
    const JSBL = 'JSBL';
    const JSBP = 'JSBP';
    const JSFB = 'JSFB';
    const JVCX = 'JVCX';
    const KAIJ = 'KAIJ';
    const KARB = 'KARB';
    const KARX = 'KARX';
    const KBSX = 'KBSX';
    const KCCB = 'KCCB';
    const KDIX = 'KDIX';
    const KGRB = 'KGRB';
    const KGSX = 'KGSX';
    const KJSB = 'KJSB';
    const KKBK = 'KKBK';
    const KLGB = 'KLGB';
    const KMCB = 'KMCB';
    const KPCX = 'KPCX';
    const KSCB = 'KSCB';
    const KVBL = 'KVBL';
    const KVGB = 'KVGB';
    const LAVB_R = 'LAVB_R';
    const LDRX = 'LDRX';
    const MAHB = 'MAHB';
    const MAHG = 'MAHG';
    const MALX = 'MALX';
    const MBGX = 'MBGX';
    const MCBL = 'MCBL';
    const MDBK = 'MDBK';
    const MDCB = 'MDCB';
    const MDGX = 'MDGX';
    const MERX = 'MERX';
    const MGRB = 'MGRB';
    const MRBX = 'MRBX';
    const MRTX = 'MRTX';
    const MSBL = 'MSBL';
    const MSCI = 'MSCI';
    const MSLM = 'MSLM';
    const MSNU = 'MSNU';
    const MSSX = 'MSSX';
    const MUBL = 'MUBL';
    const MVIX = 'MVIX';
    const MZRX = 'MZRX';
    const NBMX = 'NBMX';
    const NESF = 'NESF';
    const NKGS = 'NKGS';
    const NNSB = 'NNSB';
    const NSPB = 'NSPB';
    const NTBL = 'NTBL';
    const ORBC = 'ORBC';
    const PALX = 'PALX';
    const PASX = 'PASX';
    const PCTX = 'PCTX';
    const PCUX = 'PCUX';
    const PDSX = 'PDSX';
    const PGBX = 'PGBX';
    const PJSB = 'PJSB';
    const PKGB = 'PKGB';
    const PMCB = 'PMCB';
    const PMEC = 'PMEC';
    const PRTH = 'PRTH';
    const PSIB = 'PSIB';
    const PTSX = 'PTSX';
    const PUGX = 'PUGX';
    const PUNB_R = 'PUNB_R';
    const PURX = 'PURX';
    const PYTM = 'PYTM';
    const RATN = 'RATN';
    const RJTX = 'RJTX';
    const RMGB = 'RMGB';
    const RNSB = 'RNSB';
    const RSBL = 'RSBL';
    const SACB = 'SACB';
    const SADX = 'SADX';
    const SAGX = 'SAGX';
    const SBIN = 'SBIN';
    const SBLS = 'SBLS';
    const SCBL = 'SCBL';
    const SCOB = 'SCOB';
    const SDCB = 'SDCB';
    const SDCE = 'SDCE';
    const SEWX = 'SEWX';
    const SGBA = 'SGBA';
    const SIBL = 'SIBL';
    const SIDC = 'SIDC';
    const SMCB = 'SMCB';
    const SNSX = 'SNSX';
    const SPCB = 'SPCB';
    const SPSX = 'SPSX';
    const SRCB = 'SRCB';
    const SRHX = 'SRHX';
    const SSDX = 'SSDX';
    const STCB = 'STCB';
    const STRX = 'STRX';
    const SUBX = 'SUBX';
    const SUNB = 'SUNB';
    const SURY = 'SURY';
    const SUTB = 'SUTB';
    const SUVX = 'SUVX';
    const SVAX = 'SVAX';
    const SVCB = 'SVCB';
    const SVCX = 'SVCX';
    const SYNB = 'SYNB';
    const TAMX = 'TAMX';
    const TBSB = 'TBSB';
    const TGBX = 'TGBX';
    const TJSB = 'TJSB';
    const TMBL = 'TMBL';
    const TMSX = 'TMSX';
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
    const UTKS = 'UTKS';
    const UUCB = 'UUCB';
    const UUCX = 'UUCX';
    const VARA = 'VARA';
    const VCCX = 'VCCX';
    const VCOB = 'VCOB';
    const VGBX = 'VGBX';
    const VIJB = 'VIJB';
    const VISX = 'VISX';
    const VSBL = 'VSBL';
    const VSCX = 'VSCX';
    const VVCX = 'VVCX';
    const VVSB = 'VVSB';
    const XJKG = 'XJKG';
    const YESB = 'YESB';
    const YLNX = 'YLNX';
    const ZRNB = 'ZRNB';

    protected static $supportedUpiBanks = [
        self::ABHY,
        self::ABPB,
        self::ACBX,
        self::ACUX,
        self::ADBX,
        self::ADCC,
        self::AGVX,
        self::AIRP,
        self::ALLA,
        self::AMCB,
        self::AMDN,
        self::ANDB,
        self::APBL,
        self::APGB,
        self::APGV,
        self::APMC,
        self::ASBL,
        self::ASOX,
        self::AUBL,
        self::AUGX,
        self::AXIS,   // for Axis UAT
        self::BACB,
        self::BARA,
        self::BARB_R,
        self::BASX,
        self::BCBM,
        self::BDBL,
        self::BGBX,
        self::BGGX,
        self::BHUX,
        self::BKDN,
        self::BKDX,
        self::BKID,
        self::BMPX,
        self::BNSB,
        self::BOFA,
        self::BRDX,
        self::BRGX,
        self::BUGX,
        self::CBIN,
        self::CCBX,
        self::CCBL,
        self::CGBX,
        self::CGGX,
        self::CITI,
        self::CIUB,
        self::CLBL,
        self::CNRB,
        self::COAS,
        self::COLX,
        self::CORP,
        self::COSB,
        self::CRGB,
        self::CRUB,
        self::CSBK,
        self::CSBX,
        self::CTBX,
        self::DBSS,
        self::DCBL,
        self::DCUX,
        self::DEGX,
        self::DEUT,
        self::DGBX,
        self::DLXB,
        self::DNSB,
        self::ESFB,
        self::ESMF,
        self::FDRL,
        self::FGCB,
        self::FINO,
        self::FSFB,
        self::GBCB,
        self::GCUX,
        self::GDCB,
        self::GSCB,
        self::HCBL,
        self::HDFC,
        self::HGBX,
        self::HMBX,
        self::HPSC,
        self::HSBC,
        self::HUTX,
        self::IBKL,
        self::ICIC,
        self::IDFB,
        self::IDIB,
        self::INDB,
        self::IOBA,
        self::IPOS,
        self::IPSX,
        self::JAKA,
        self::JANA,
        self::JIOP,
        self::JJSB,
        self::JMCX,
        self::JSBP,
        self::JSBL,
        self::JSFB,
        self::JVCX,
        self::KAIJ,
        self::KARB,
        self::KARX,
        self::KBSX,
        self::KCCB,
        self::KDIX,
        self::KGRB,
        self::KGSX,
        self::KJSB,
        self::KKBK,
        self::KLGB,
        self::KMCB,
        self::KSCB,
        self::KPCX,
        self::KVBL,
        self::KVGB,
        self::LAVB_R,
        self::LDRX,
        self::MAHB,
        self::MAHG,
        self::MALX,
        self::MBGX,
        self::MCBL,
        self::MDBK,
        self::MDCB,
        self::MDGX,
        self::MERX,
        self::MGRB,
        self::MRBX,
        self::MRTX,
        self::MSBL,
        self::MSCI,
        self::MSLM,
        self::MSNU,
        self::MSSX,
        self::MUBL,
        self::MVIX,
        self::MZRX,
        self::NBMX,
        self::NESF,
        self::NKGS,
        self::NNSB,
        self::NSPB,
        self::NTBL,
        self::ORBC,
        self::PALX,
        self::PASX,
        self::PCTX,
        self::PCUX,
        self::PDSX,
        self::PGBX,
        self::PJSB,
        self::PKGB,
        self::PMCB,
        self::PMEC,
        self::PRTH,
        self::PSIB,
        self::PTSX,
        self::PUGX,
        self::PUNB_R,
        self::PURX,
        self::PYTM,
        self::RATN,
        self::RJTX,
        self::RMGB,
        self::RNSB,
        self::RSBL,
        self::SACB,
        self::SADX,
        self::SAGX,
        self::SBIN,
        self::SBLS,
        self::SCBL,
        self::SCOB,
        self::SDCB,
        self::SDCE,
        self::SEWX,
        self::SGBA,
        self::SIBL,
        self::SIDC,
        self::SMCB,
        self::SNSX,
        self::SPCB,
        self::SPSX,
        self::SRCB,
        self::SRHX,
        self::SSDX,
        self::STCB,
        self::STRX,
        self::SUBX,
        self::SUNB,
        self::SURY,
        self::SUTB,
        self::SUVX,
        self::SVAX,
        self::SVCB,
        self::SVCX,
        self::SYNB,
        self::TAMX,
        self::TBSB,
        self::TGBX,
        self::TJSB,
        self::TMBL,
        self::TMSX,
        self::TSAB,
        self::TUDX,
        self::TUMX,
        self::UBIN,
        self::UCBA,
        self::UJVN,
        self::UMSX,
        self::UTBI,
        self::UTKS,
        self::UTGX,
        self::UTIB,
        self::UUCB,
        self::UUCX,
        self::VARA,
        self::VCCX,
        self::VCOB,
        self::VGBX,
        self::VIJB,
        self::VISX,
        self::VSBL,
        self::VSCX,
        self::VVCX,
        self::VVSB,
        self::XJKG,
        self::YESB,
        self::YLNX,
        self::ZRNB,
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
