<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Mode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Bank\Name;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal\Category;

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

    const ACCOUNT_NUMBER_LENGTHS = [
        IFSC::UTIB => 15,
        IFSC::FDRL => 14,
    ];

    protected static $self = [
        IFSC::ICIC,
        IFSC::HDFC,
        IFSC::CORP,
        IFSC::UTIB,
        IFSC::KKBK,
        IFSC::AIRP,
        IFSC::FDRL,
        IFSC::RATN,
        IFSC::INDB,
        IFSC::PUNB,
    ];

    protected static $selfTPV = [
        IFSC::ICIC,
        IFSC::HDFC,
        IFSC::KKBK,
        IFSC::UTIB,
        IFSC::FDRL,
        IFSC::RATN,
     // IFSC::INDB,
    ];

    protected static $paytm = array(
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CSBK,
        // IFSC::FDRL,
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
        IFSC::YESB,
    );

    protected static $paytmTPV = [];

    protected static $billdesk = array(
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
        IFSC::DBSS,
        IFSC::IDFB,
        // IFSC::FDRL,
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
        // IFSC::FDRL,
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
        // IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        // IFSC::SBMY,
        IFSC::STBP,
        // IFSC::SBTR,
        IFSC::UCBA,
        IFSC::UBIN,
        IFSC::VIJB,
        IFSC::YESB,
    );

    protected static $atomTPV = [];

    protected static $ebs = array(
        IFSC::ANDB,
        IFSC::CBIN,
        IFSC::CNRB,
        IFSC::CORP,
        IFSC::CSBK,
        IFSC::DLXB,
        // IFSC::FDRL,
        IFSC::IDIB,
        IFSC::IOBA,
        IFSC::INDB,
        IFSC::JAKA,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::MAHB,
        IFSC::ORBC,
        IFSC::PSIB,
        IFSC::SRCB,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UTBI,
        IFSC::VIJB,
        IFSC::YESB,
        Netbanking::LAVB_R,
        Netbanking::PUNB_R,

        IFSC::UTIB,
        IFSC::BKID,
        IFSC::CIUB,

        /*
        IFSC::ICIC,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::SBTR,
        IFSC::HDFC,
        */
    );

    protected static $defaultDisabled = [
        IFSC::AIRP,
        IFSC::PUNB
    ];

    protected static $ebsTPV = [];

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::getAllBanks()));
    }

    /**
     * Returns any unsupported bank from the passed list
     * @param $banks
     * @return array
     */
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
        return array_unique(array_merge(self::$paytm, self::$billdesk, self::$ebs, self::$self));
    }

    public static function enableDefaultBanks(array $banks)
    {
        self::$defaultDisabled = array_diff(self::$defaultDisabled, $banks);

        return true;
    }

    public static function getDefaultDisabledBanks()
    {
        return self::$defaultDisabled;
    }

    public static function getDisabledBanks(array $enabled)
    {
        return array_diff(self::getAllBanks(), $enabled);
    }

    public static function getEnabledBanks(array $disabled = [])
    {
        return array_diff(self::getAllBanks(), $disabled);
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

    public static function getDirectlyNetbankingBanks()
    {
        return self::$self;
    }

    /**
     * Gets supported banks for a merchant.
     * Checks for TPV merchants and any bank disabled by category
     */
    public static function getSupportedBanks($merchant = null)
    {
        $banks = self::getSupportedBanksInLiveMode();

        if ((isset($merchant) === true) and
            ($merchant->isTPVRequired() === true))
        {
            $banks = self::getSupportedBanksForTPV();
        }

        return array_unique($banks);
    }

    public static function removeDefaultDisableBanks(array $banks)
    {
        return array_diff($banks, self::getDefaultDisabledBanks());
    }

    public static function getSupportedBanksInLiveMode()
    {
        return array_unique(array_merge(self::$billdesk, self::$ebs, self::$self));
    }

    public static function getSupportedBanksForTPV()
    {
        return array_unique(array_merge(self::$billdeskTPV, self::$selfTPV));
    }

    public static function isBankSupportedByGateway($bank, $gateway, $isTPV = false)
    {
        if ($isTPV === true)
        {
            return self::isBankSupportedByGatewayForTPV($bank, $gateway);
        }

        return in_array($bank, self::$$gateway);
    }

    public static function isBankSupportedByGatewayForTPV($bank, $gateway)
    {
        // Direct gateways are handled seperately
        return in_array($bank, self::${$gateway.'TPV'});
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

    public static function getAccountNumberLengths()
    {
        return self::ACCOUNT_NUMBER_LENGTHS;
    }

    public static function getExclusiveIssuersForGateway(string $gateway)
    {
        $otherGatewaySupportedBanks = self::$self;

        $gatewayExclusiveBanks = self::$$gateway;

        foreach (Gateway::SHARED_NETBANKING_GATEWAYS_LIVE as $netbankingGateway)
        {
            if ($gateway !== $netbankingGateway)
            {
                $gatewayExclusiveBanks = array_diff($gatewayExclusiveBanks, self::$$netbankingGateway);
            }
        }

        $gatewayExclusiveBanks = array_values(array_diff($gatewayExclusiveBanks, self::$self));

        return $gatewayExclusiveBanks;
    }

    public static function isIssuerExclusiveToGateway(string $issuer, string $gateway)
    {
        $gatewayExclusiveBanks = self::getExclusiveIssuersForGateway($gateway);

        return in_array($issuer, $gatewayExclusiveBanks, true);
    }
}
