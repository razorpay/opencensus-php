<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Bank\IFSC;
use RZP\Models\Bank\Name;
use RZP\Constants\Mode;

class Netbanking
{
    const BARB_C = 'BARB_C';
    const BARB_R = 'BARB_R';
    const PUNB_C = 'PUNB_C';
    const PUNB_R = 'PUNB_R';
    const LAVB_C = 'LAVB_C';
    const LAVB_R = 'LAVB_R';

    protected static $names = array(
        self::BARB_C => 'Bank of Baroda - Corporate Banking',
        self::BARB_R => 'Bank of Baroda - Retail Banking',
        self::PUNB_C => 'Punjab National Bank - Corporate Banking',
        self::PUNB_R => 'Punjab National Bank - Retail Banking',
        self::LAVB_C => 'Lakshmi Vilas Bank - Corporate Banking',
        self::LAVB_R => 'Lakshmi Vilas Bank - Retail Banking',
    );

    protected static $self = array(
        IFSC::HDFC);

    /**
     * Additional net-banking banks that we are in the process of integrating
     * @var array
     */
    protected static $selfInTest = array(
        IFSC::KKBK);

    protected static $paytm = array(
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CSBK,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::KKBK,
        IFSC::MAHB,
        IFSC::PUNB,
        IFSC::UBIN,
        IFSC::UTIB,
        IFSC::VIJB,
        IFSC::VYSA,
        IFSC::YESB,
    );

    protected static $billdesk = array(
        IFSC::ABNA,
        IFSC::ALLA,
        IFSC::ANDB,
        IFSC::BBKM,
        IFSC::BKDN,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CIUB,
        IFSC::CNRB,
        IFSC::CORP,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::DCBL,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::JSBP,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::KVBL,
        IFSC::MAHB,
        IFSC::NKGS,
        IFSC::ORBC,
        IFSC::PMCB,
        IFSC::PSIB,
        IFSC::RATN,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::SBTR,
        IFSC::SCBL,
        IFSC::SIBL,
        IFSC::SRCB,
        IFSC::SVCB,
        IFSC::SYNB,
        IFSC::TMBL,
        IFSC::TNSC,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UTBI,
        IFSC::UTIB,
        IFSC::VIJB,
        IFSC::VYSA,
        IFSC::YESB,
        Netbanking::BARB_C,
        Netbanking::BARB_R,
        Netbanking::PUNB_C,
        Netbanking::PUNB_R,
        Netbanking::LAVB_C,
        Netbanking::LAVB_R,
    );

    protected static $billdeskTPV = array(
        IFSC::ALLA,
        IFSC::ANDB,
        IFSC::CIUB,
        IFSC::CORP,
        IFSC::IBKL,
        IFSC::INDB,
        IFSC::KVBL,
        Netbanking::LAVB_R,
        IFSC::ICIC,
        IFSC::UTIB,
        IFSC::BKID,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::SBTR,
    );

    protected static $sbiepay = array(
        IFSC::SBTR,
        IFSC::CSBK,
        IFSC::JAKA,
        IFSC::MAHB,
        IFSC::DEUT,
        IFSC::VIJB,
        IFSC::PSIB,
        IFSC::SIBL,
        IFSC::BKID,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::UTBI,
        IFSC::IDIB,
        IFSC::CIUB,
        IFSC::DLXB,
        IFSC::ICIC,
        IFSC::YESB,
        IFSC::KVBL,
        IFSC::FDRL,
        IFSC::ORBC,
        IFSC::CORP,
        IFSC::INDB,
        IFSC::HDFC,
        IFSC::BBKM,
        IFSC::KARB,
        IFSC::ANDB,
        IFSC::CNRB,
        IFSC::RATN,
        IFSC::UBIN,
        IFSC::CBIN,
        IFSC::PUNB,
        IFSC::IOBA,
        IFSC::SBIN,
        IFSC::VYSA,
        IFSC::IBKL,
        IFSC::BKDN,
        IFSC::DCBL,
        IFSC::TMBL,
        IFSC::SYNB,
        IFSC::CITI,
        IFSC::LAVB);

    protected static $atom = array(
        IFSC::UTIB,
        IFSC::BKID,
        IFSC::MAHB,
        IFSC::CNRB,
        IFSC::CSBK,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CORP,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IBKL,
        IFSC::IDIB,
        IFSC::IOBA,
        IFSC::INDB,
        IFSC::JAKA,
        IFSC::KARB,
        IFSC::KVBL,
        IFSC::KKBK,
        IFSC::LAVB,
        IFSC::SIBL,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::SBTR,
        IFSC::UCBA,
        IFSC::UBIN,
        IFSC::VIJB,
        IFSC::YESB,
    );

    protected static $ebs = array(
        IFSC::ANDB,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CIUB,
        IFSC::CNRB,
        IFSC::CORP,
        IFSC::CSBK,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::MAHB,
        IFSC::ORBC,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::SRCB,
        IFSC::STBP,
        IFSC::SBTR,
        IFSC::UTIB,
        IFSC::UBIN,
        IFSC::UTBI,
        IFSC::VYSA,
        IFSC::VIJB,
        IFSC::YESB,
        Netbanking::PUNB_R,
    );

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::getAllBanks()));
    }

    public static function findUnsupportedBanks($banks)
    {
        return array_diff($banks, self::getAllBanks());
    }

    public static function getAllBanks()
    {
        //
        // Merge paytm and billdesk supported banks and remove
        // duplicate values
        //
        return array_unique(array_merge(self::$paytm, self::$billdesk, self::$ebs, [IFSC::KKBK]));
    }

    public static function getDisabledBanks($banks)
    {
        return array_diff(self::getAllBanks(), $banks);
    }

    public static function getEnabledBanks()
    {
        return self::getAllBanks();
    }

    public static function getNames($codes)
    {
        $names = Name::getNames($codes);

        $names = array_merge(
                    $names,
                    array_intersect_key(
                        self::$names,
                        array_flip($codes)));

        asort($names);

        return $names;
    }

    public static function getName($code)
    {
        if (defined(__CLASS__ . '::' . $code))
        {
            return self::$names[$code];
        }

        return Name::getName($code);
    }

    public static function getPaytmSupportedBanks()
    {
        return self::$paytm;
    }

    public static function getBilldeskSupportedBanks()
    {
        return self::$billdesk;
    }

    public static function getEbsSupportedBanks()
    {
        return self::$ebs;
    }

    public static function getSupportedBanks($mode = Mode::LIVE, $isTPVRequired = false)
    {
        $banks = self::getSupportedBanksInLiveMode();

        if ($mode === Mode::TEST)
        {
            $banks = self::getSupportedBanksInLiveMode();

            $banks = array_merge($banks, self::$selfInTest);
            $banks = array_merge($banks, self::$sbiepay);
        }

        if ($isTPVRequired)
        {
            $banks = self::$billdeskTPV;
        }

        return array_unique($banks);
    }

    public static function getSupportedBanksInLiveMode()
    {
        return array_unique(array_merge(self::$billdesk, self::$ebs, self::$self));
    }

    public static function getSbiepaySupportedBanks()
    {
        return self::$sbiepay;
    }

    public static function getSupportedBanksForTPV()
    {
        return self::$billdeskTPV;
    }

    public static function isBankSupportedByGateway($bank, $gateway)
    {
        return in_array($bank, self::$$gateway);
    }

    public static function isPaytmSupportedBank($bank)
    {
        return in_array($bank, self::$paytm);
    }

    public static function isEbsSupportedBank($bank)
    {
        return in_array($bank, self::$ebs);
    }

    public static function isBilldeskSupportedBank($bank)
    {
        return in_array($bank, self::$billdesk);
    }

    public static function isSbiepaySupportedBank($bank)
    {
        return in_array($bank, self::$sbiepay);
    }
}
