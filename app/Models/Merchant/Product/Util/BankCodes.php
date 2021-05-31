<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant\Product\Config;

class BankCodes
{
    const ABPB = 'abpb';

    const AIRP = 'airp';

    const ALLA = 'alla';

    const ANDB = 'andb';

    const ANDB_C = 'andb_c';

    const AUBL = 'aubl';

    const BACB = 'bacb';

    const BARB = 'barb';

    const BARB_C = 'barb_c';

    const BARB_R = 'barb_r';

    const BBKM ='bbkm';

    const BDBL = 'bdbl';

    const BKDN = 'bkdn';

    const BKID = 'bkid';

    const BKID_C = 'bkid_c';

    const CBIN = 'cbin';

    const CITI = 'citi';

    const CIUB = 'ciub';

    const CNRB = 'cnrb';

    const CORP = 'corp';

    const COSB = 'cosb';

    const CSBK = 'csbk';

    const DBSS = 'dbss';

    const DCBL ='dcbl';

    const DEUT = 'deut';

    const DLXB = 'dlxb';

    const DLXB_C = 'dlxb_c';

    const ESAF = 'esaf';

    const ESFB = 'esfb';

    const FDRL = 'fdrl';

    const HDFC = 'hdfc';

    const IBKL = 'ibkl';

    const IBKL_C = 'ibkl_c';

    const ICIC = 'icic';

    const ICIC_C = 'icic_c';

    const IDFB = 'idfb';

    const IDIB = 'idib';

    const INDB = 'indb';

    const IOBA = 'ioba';

    const JAKA = 'jaka';

    const JSBP = 'jsbp';

    const JSFB = 'jsfb';

    const KARB = 'karb';

    const KCCB = 'kccb';

    const KJSB = 'kjsb';

    const KKBK = 'kkbk';

    const KVBL = 'kvbl';

    const LAVB = 'lavb';

    const LAVB_C = 'lavb_c';

    const LAVB_R = 'lavb_r';

    const MAHB = 'mahb';

    const MSNU ='msnu';

    const NESF = 'nesf';

    const NKGS = 'nkgs';

    const ORBC = 'orbc';

    const PSIB = 'psib';

    const PUNB = 'punb';

    const PUNB_C = 'punb_c';

    const PUNB_R = 'punb_r';

    const RATN = 'ratn';

    const RATN_C = 'ratn_c';

    const SBBJ = 'sbbj';

    const SBHY ='sbhy';

    const SBIN = 'sbin';

    const SBMY = 'sbmy';

    const SBTR = 'sbtr';

    const SCBL = 'scbl';

    const SIBL = 'sibl';

    const SRCB = 'srcb';

    const STBP = 'stbp';

    const SURY = 'sury';

    const SVCB = 'svcb';

    const SVCB_C = 'svcb_c';

    const SYNB = 'synb';

    const TBSB = 'tbsb';

    const TJSB = 'tjsb';

    const TMBL = 'tmbl';

    const TNSC = 'tnsc';

    const UBIN = 'ubin';

    const UCBA = 'ucba';

    const UTBI = 'utbi';

    const UTIB ='utib';

    const UTIB_C = 'utib_c';

    const VARA = 'vara';

    const VIJB = 'vijb';

    const YESB = 'yesb';

    const YESB_C = 'yesb_c';

    const ZCBL = 'zcbl';

    const CORPORATE = [
        self::ANDB => self::ANDB_C,
        self::BARB => self::BARB_C,
        self::BKID => self::BKID_C,
        self::DLXB => self::DLXB_C,
        self::IBKL => self::IBKL_C,
        self::ICIC => self::ICIC_C,
        self::LAVB => self::LAVB_C,
        self::PUNB => self::PUNB_C,
        self::RATN => self::RATN_C,
        self::SVCB => self::SVCB_C,
        self::UTIB => self::UTIB_C,
        self::YESB => self::YESB_C
    ];

    const BANKS = [
        self::ABPB,
        self::AIRP,
        self::ALLA,
        self::ANDB,
        self::AUBL,
        self::BACB,
        self::BARB,
        self::BBKM,
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
        self::ESAF,
        self::ESFB,
        self::FDRL,
        self::HDFC,
        self::IBKL,
        self::ICIC,
        self::IDFB,
        self::IDIB,
        self::INDB,
        self::IOBA,
        self::JAKA,
        self::JSBP,
        self::JSFB,
        self::KARB,
        self::KCCB,
        self::KJSB,
        self::KKBK,
        self::KVBL,
        self::LAVB,
        self::MAHB,
        self::MSNU,
        self::NESF,
        self::NKGS,
        self::ORBC,
        self::PSIB,
        self::PUNB,
        self::RATN,
        self::SBBJ,
        self::SBHY,
        self::SBIN,
        self::SBMY,
        self::SBTR,
        self::SCBL,
        self::SIBL,
        self::SRCB,
        self::STBP,
        self::SURY,
        self::SVCB,
        self::SYNB,
        self::TBSB,
        self::TJSB,
        self::TMBL,
        self::TNSC,
        self::UBIN,
        self::UCBA,
        self::UTBI,
        self::UTIB,
        self::VARA,
        self::VIJB,
        self::YESB,
        self::ZCBL
    ];

    const RETAIL = [
        self::BARB => self::BARB_R,
        self::LAVB => self::LAVB_R,
        self::PUNB => self::PUNB_R
    ];



    public static function getBankcodesFromInstruments(array $instruments)
    {
        $bankcodes = [];

        $retail = array_flip(self::RETAIL);

        $corporate = array_flip(self::CORPORATE);

        foreach ($instruments as $instrument) {
            array_push($bankcodes, self::getBankCodeFromInstrument($instrument, $retail, $corporate));
        }

        return $bankcodes;
    }

    private static function getBankCodeFromInstrument(string $instrument, $retail, $corporate)
    {
        $bankCode = '';
        if(in_array($instrument, self::BANKS, true) === true)
        {
            $bankCode = $instrument;
        }

        if(array_key_exists($instrument, $retail) === true)
        {
            return $retail[$instrument];
        }
        else if(array_key_exists($instrument, $corporate))
        {
            return $corporate[$instrument];
        }

        return  $instrument;

    }

    public function getInstrumentFromBankcode(string $bankCode, string $type)
    {
        if(in_array($bankCode, self::BANKS, true) === false)
        {
            return '';
        }

        $instrument = $bankCode;
        if($type == Constants::RETAIL && array_key_exists($bankCode, self::RETAIL))
        {
            return self::RETAIL[$bankCode];
        }
        else if($type == Constants::CORPORATE && array_key_exists($bankCode, self::CORPORATE))
        {
            return self::CORPORATE[$bankCode];
        }

        return  $instrument;
    }
}
