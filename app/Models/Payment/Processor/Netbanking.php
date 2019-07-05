<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Mode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Bank\Name;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Feature\Constants;
use RZP\Models\Terminal\Category;
use RZP\Models\Terminal\BankingType;

class Netbanking
{
    const BARB_C = 'BARB_C';
    const PUNB_C = 'PUNB_C';
    const LAVB_C = 'LAVB_C';
    const ICIC_C = 'ICIC_C';
    const UTIB_C = 'UTIB_C';
    const BKID_C = 'BKID_C';
    const IBKL_C = 'IBKL_C';
    const YESB_C = 'YESB_C';
    const ANDB_C = 'ANDB_C';
    const RATN_C = 'RATN_C';
    const SVCB_C = 'SVCB_C';
    const DLXB_C = 'DLXB_C';

    // These are the IFSC's that are to be used for these
    // banks even if we integrate them directly.
    // if we add new ifsc, please add it in RZP\Models\Payment\Processor\Upi
    const BARB_R = 'BARB_R';
    const PUNB_R = 'PUNB_R';
    const LAVB_R = 'LAVB_R';

    public static $defaultInconsistentBankCodesMapping = [
        IFSC::BARB => 'BARB_R',
        IFSC::PUNB => 'PUNB_R',
        IFSC::LAVB => 'LAVB_R',
    ];

    public static $inconsistentIfsc = [
        self::ANDB_C,
        self::BARB_C,
        self::BARB_R,
        self::BKID_C,
        self::DLXB_C,
        self::IBKL_C,
        self::ICIC_C,
        self::LAVB_C,
        self::LAVB_R,
        self::PUNB_C,
        self::PUNB_R,
        self::RATN_C,
        self::SVCB_C,
        self::UTIB_C,
        self::YESB_C,
    ];

    protected static $names = [
        self::ANDB_C => 'Andhra Bank - Corporate Banking',
        self::BARB_C => 'Bank of Baroda - Corporate Banking',
        self::BARB_R => 'Bank of Baroda - Retail Banking',
        self::BKID_C => 'Bank of India - Corporate Banking',
        self::DLXB_C => 'Dhanlaxmi Bank - Corporate Banking',
        self::IBKL_C => 'IDBI - Corporate Banking',
        self::ICIC_C => 'ICICI Bank - Corporate Banking',
        self::LAVB_C => 'Lakshmi Vilas Bank - Corporate Banking',
        self::LAVB_R => 'Lakshmi Vilas Bank - Retail Banking',
        self::PUNB_C => 'Punjab National Bank - Corporate Banking',
        self::PUNB_R => 'Punjab National Bank - Retail Banking',
        self::RATN_C => 'RBL Bank - Corporate Banking',
        self::SVCB_C => 'Shamrao Vithal Bank - Corporate Banking',
        self::UTIB_C => 'Axis Bank - Corporate Banking',
        self::YESB_C => 'Yes Bank - Corporate Banking',
    ];

    const ACCOUNT_NUMBER_LENGTHS = [
        IFSC::UTIB => 15,
        IFSC::FDRL => 14,
        IFSC::CSBK => 18,
    ];

    protected static $self = [
        IFSC::ICIC,
        IFSC::HDFC,
        IFSC::CORP,
        IFSC::UTIB,
        IFSC::KKBK,
        IFSC::AIRP,
        IFSC::FDRL,
        IFSC::IDFB,
        IFSC::RATN,
        IFSC::INDB,
        IFSC::ORBC,
        IFSC::CSBK,
        IFSC::ALLA,
        IFSC::CNRB,
        IFSC::CIUB,
        IFSC::ESFB,
        IFSC::SBIN,
        IFSC::VIJB,
        IFSC::YESB,
        IFSC::SIBL,
        self::PUNB_R,
        self::BARB_R,
    ];

    protected static $selfCorp = [
        self::ICIC_C,
        self::UTIB_C,
        self::BARB_C,
        self::PUNB_C,
    ];

    protected static $selfTPV = [
        IFSC::ICIC,
        IFSC::HDFC,
        IFSC::KKBK,
        IFSC::UTIB,
        IFSC::FDRL,
        IFSC::RATN,
        IFSC::INDB,
        IFSC::CSBK,
        IFSC::ALLA,
        IFSC::IDFB,
    ];

    protected static $gatewaySupportedBanks = [
        Gateway::BILLDESK => [
            'retail' => [
                // IFSC::FDRL,
                // IFSC::INDB,
                // IFSC::ORBC,
                // IFSC::RATN,
                IFSC::ABPB,
                IFSC::ANDB,
                IFSC::AUBL,
                IFSC::BACB,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKDN,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::COSB,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::ESAF,
                IFSC::IBKL,
                IFSC::IDIB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KCCB,
                IFSC::KJSB,
                IFSC::KVBL,
                IFSC::MAHB,
                IFSC::MSNU,
                IFSC::NESF,
                IFSC::NKGS,
                IFSC::PMCB,
                IFSC::PSIB,
                IFSC::SBBJ,
                IFSC::SBHY,
                IFSC::SBIN,
                IFSC::SBMY,
                IFSC::SBTR,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::STBP,
                IFSC::SURY,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TBSB,
                IFSC::TJSB,
                IFSC::TMBL,
                IFSC::TNSC,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::VARA,
                IFSC::VIJB,
                IFSC::YESB,
                IFSC::ZCBL,
                // self::BARB_R,
                self::ANDB_C,
                self::BARB_C,
                self::DLXB_C,
                self::IBKL_C,
                self::LAVB_C,
                self::LAVB_R,
                self::PUNB_C,
                self::PUNB_R,
                self::RATN_C,
                self::SVCB_C,
                self::YESB_C,
            ],
            'tpv' => [
                IFSC::ANDB,
                IFSC::CIUB,
                IFSC::CORP,
                IFSC::IBKL,
                // IFSC::INDB,
                IFSC::KVBL,
                self::LAVB_R,
                IFSC::UTIB,
                IFSC::BKID,
                IFSC::SBBJ,
                IFSC::SBHY,
                IFSC::SBIN,
                IFSC::SBMY,
                IFSC::STBP,
                IFSC::SBTR,
            ],
        ],
        Gateway::ATOM => [
            'retail' => [
                // IFSC::FDRL,
                // IFSC::INDB,
                // IFSC::ORBC,
                // IFSC::RATN,
                IFSC::ABNA,
                IFSC::ANDB,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::ESFB,
                IFSC::IBKL,
                IFSC::IDIB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::KVBL,
                IFSC::MAHB,
                IFSC::PMCB,
                IFSC::PSIB,
                IFSC::SBBJ,
                IFSC::SBHY,
                IFSC::SBIN,
                IFSC::SBMY,
                IFSC::SBTR,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::STBP,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::VIJB,
                IFSC::YESB,
                self::LAVB_R,
                self::PUNB_R,
            ],
            'tpv' => [
                IFSC::BKID,
                IFSC::CIUB,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::IBKL,
                IFSC::IDIB,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::KVBL,
                IFSC::MAHB,
                IFSC::SBIN,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::TMBL,
                IFSC::YESB,
                self::LAVB_R,
                self::BKID_C,
            ]
        ],
        Gateway::EBS => [
            'retail' => [
                IFSC::ANDB,
                IFSC::CBIN,
                IFSC::CNRB,
                IFSC::DLXB,
                // IFSC::FDRL,
                IFSC::IDIB,
                IFSC::IOBA,
                // IFSC::INDB,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::MAHB,
                // IFSC::ORBC,
                IFSC::PSIB,
                IFSC::SRCB,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::VIJB,
                IFSC::YESB,
                self::LAVB_R,
                self::PUNB_R,
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
            ],
        ],
        Gateway::PAYTM => [
            'retail' => [
                IFSC::CITI,
                IFSC::CIUB,
                // IFSC::CSBK,
                // IFSC::FDRL,
                IFSC::HDFC,
                IFSC::ICIC,
                IFSC::IDIB,
                // IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::KKBK,
                IFSC::MAHB,
                self::PUNB_R,
                IFSC::UBIN,
                IFSC::UTIB,
                IFSC::VIJB,
                IFSC::YESB,
            ],
        ],
        Gateway::NETBANKING_ICICI => [
            'retail' => [
                IFSC::ICIC,
            ],
            'corp' => [
                self::ICIC_C
            ],
            'tpv' => [
                IFSC::ICIC
            ]
        ],
        Gateway::NETBANKING_YESB => [
            'retail' => [
                IFSC::YESB,
            ],
            'tpv' => [
                IFSC::YESB
            ]
        ],
        Gateway::NETBANKING_AXIS => [
            'retail' => [
                IFSC::UTIB
            ],
            'corp' => [
                self::UTIB_C
            ],
            'tpv' => [
                IFSC::UTIB
            ]
        ],
        Gateway::NETBANKING_BOB => [
            'retail' => [
                self::BARB_R
            ],
            'corp' => [
                self::BARB_C
            ]
        ],
        Gateway::NETBANKING_SIB => [
            'retail' => [
                IFSC::SIBL
            ],
            'tpv' => [
                IFSC::SIBL
            ]
        ],
        Gateway::NETBANKING_IDFC => [
            'retail' => [
                IFSC::IDFB
            ],
            'tpv' => [
                IFSC::IDFB
            ]
        ],
        Gateway::NETBANKING_VIJAYA => [
            'retail' => [
                IFSC::VIJB
            ]
        ],
        Gateway::NETBANKING_HDFC => [
            'retail' => [
                IFSC::HDFC
            ],
            'tpv' => [
                IFSC::HDFC
            ]
        ],
        Gateway::NETBANKING_CORPORATION => [
            'retail' => [
                IFSC::CORP,
            ]
        ],
        Gateway::NETBANKING_CANARA => [
            'retail' => [
                IFSC::CNRB,
            ]
        ],
        Gateway::NETBANKING_EQUITAS => [
            'retail' => [
                IFSC::ESFB
            ]
        ],
        Gateway::NETBANKING_AIRTEL => [
            'retail' => [
                IFSC::AIRP,
            ]
        ],
        Gateway::NETBANKING_CUB => [
            'retail' => [
                IFSC::CIUB,
            ],
            'tpv' => [
                IFSC::CIUB
            ],
        ],
        Gateway::NETBANKING_FEDERAL => [
            'retail' => [
                IFSC::FDRL,
            ],
            'tpv' => [
                IFSC::FDRL
            ],
        ],
        Gateway::NETBANKING_INDUSIND => [
            'retail' => [
                IFSC::INDB,
            ],
            'tpv' => [
                IFSC::INDB,
            ],
        ],
        Gateway::NETBANKING_KOTAK => [
            'retail' => [
                IFSC::KKBK
            ],
            'tpv' => [
                IFSC::KKBK
            ],
        ],
        Gateway::NETBANKING_RBL => [
            'retail' => [
                IFSC::RATN,
            ],
            'tpv' => [
                IFSC::RATN,
            ]
        ],
        Gateway::NETBANKING_OBC => [
            'retail' => [
                IFSC::ORBC
            ]
        ],
        Gateway::NETBANKING_CSB => [
            'retail' => [
                IFSC::CSBK,
            ],
            'tpv' => [
                IFSC::CSBK,
            ],
        ],
        Gateway::NETBANKING_PNB => [
            'retail' => [
                self::PUNB_R,
            ],
            'corp' => [
                self::PUNB_C
            ]
        ],
        Gateway::NETBANKING_EQUITAS => [
            'retail' => [
                IFSC::ESFB,
            ]
        ],

        Gateway::NETBANKING_SBI => [
            'retail' => [
                IFSC::SBIN,
            ]
        ],

        Gateway::NETBANKING_ALLAHABAD => [
            'retail' => [
                IFSC::ALLA,
            ],
            'tpv' => [
                IFSC::ALLA,
            ],
        ],
    ];

   protected static $defaultDisabled = [];

   const DEFAULT_DISABLED_BANKS = [
        IFSC::ABPB,
        IFSC::AUBL,
        IFSC::BKDN,
        IFSC::BBKM,
        IFSC::BKID,
        IFSC::COSB,
        IFSC::DBSS,
        IFSC::JSBP,
        IFSC::NKGS,
        IFSC::SVCB,
        IFSC::SYNB,
        IFSC::TNSC,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::ABNA,
        IFSC::DBSS,
        IFSC::TJSB,
        IFSC::KJSB,
        IFSC::MSNU,
        IFSC::BDBL,
        IFSC::DLXB,
        IFSC::BACB,
        IFSC::KCCB,
        IFSC::TBSB,
        IFSC::SURY,
        IFSC::ESAF,
        IFSC::VARA,
        IFSC::NESF,
        IFSC::ZCBL,
        self::LAVB_C,
        self::UTIB_C,
        self::BKID_C,
        self::IBKL_C,
        self::YESB_C,
        self::ANDB_C,
        self::RATN_C,
        self::ANDB_C,
        self::DLXB_C,
        self::SVCB_C,
    ];

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::getAllBanks(), true));
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

    public static function getAllBanks(): array
    {
        //
        // Merge paytm and billdesk supported banks and remove
        // duplicate values
        //

        return array_values(array_unique(array_merge(
                                            self::$gatewaySupportedBanks[Gateway::PAYTM]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::BILLDESK]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::BILLDESK]['corp'] ?? [],
                                            self::$gatewaySupportedBanks[Gateway::EBS]['retail'],
                                            self::$self,
                                            self::$selfCorp)));
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
        return self::$gatewaySupportedBanks[Gateway::PAYTM]['retail'];
    }

    public static function getBilldeskSupportedBanks()
    {
        return array_merge(self::$gatewaySupportedBanks[Gateway::BILLDESK]['retail']);
    }

    public static function getEbsSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::EBS]['retail'];
    }

    public static function getAtomSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::ATOM]['retail'];
    }

    public static function getDirectlyNetbankingBanks()
    {
        return array_merge(self::$self, self::$selfCorp);
    }

    /**
     * Gets supported banks for a merchant.
     * Checks for TPV merchants and any bank disabled by category
     */
    public static function getSupportedBanks($merchant = null)
    {
        $banks = self::getSupportedBanksInLiveMode();

        // Remove corporate banks if feature is not enabled.
        if ((isset($merchant) === true) and
            ($merchant->isFeatureEnabled(Constants::CORPORATE_BANKS) === false))
        {
            $billdeskCorp = self::$gatewaySupportedBanks[Gateway::BILLDESK]['corp'] ?? [];
            $banks = array_diff($banks, $billdeskCorp, self::$selfCorp);
        }

        if ((isset($merchant) === true) and
            ($merchant->isTPVRequired() === true))
        {
            $banks = self::getSupportedBanksForTPV();
        }

        return array_values(array_unique($banks));
    }

    public static function removeDefaultDisableBanks(array $banks)
    {
        return array_diff($banks, self::getDefaultDisabledBanks());
    }

    public static function getSupportedBanksInLiveMode()
    {
        $billdeskCorp = self::$gatewaySupportedBanks[Gateway::BILLDESK]['corp'] ?? [];
        return array_values(array_unique(array_merge(
                                            self::$gatewaySupportedBanks[Gateway::BILLDESK]['retail'],
                                            $billdeskCorp,
                                            self::$gatewaySupportedBanks[Gateway::EBS]['retail'],
                                            self::$self,
                                            self::$selfCorp)));
    }

    public static function getSupportedBanksForTPV()
    {
        return array_values(array_unique(array_merge(
                    self::$gatewaySupportedBanks[Gateway::BILLDESK]['tpv'],
                    self::$selfTPV,
                    self::$gatewaySupportedBanks[Gateway::ATOM]['tpv'])));
    }

    public static function isBankSupportedByGateway($bank, $gateway, $isTPV = false)
    {
        if ($isTPV === true)
        {
            return self::isBankSupportedByGatewayForTPV($bank, $gateway);
        }

        $functionName = 'is'. title_case($gateway) . 'SupportedBank';

        return self::$functionName($bank);
    }

    public static function isBankSupportedByGatewayForTPV($bank, $gateway)
    {
        // Direct gateways are handled seperately
        $tpvBanks = self::$gatewaySupportedBanks[$gateway]['tpv'] ?? [];
        return in_array($bank, $tpvBanks, true) === true;
    }

    public static function isPaytmSupportedBank($bank)
    {
        return in_array($bank, self::getPaytmSupportedBanks(), true) === true;
    }

    public static function isEbsSupportedBank($bank)
    {
        return in_array($bank, self::getEbsSupportedBanks(), true) === true;
    }

    public static function isBilldeskSupportedBank($bank)
    {
        return in_array($bank, self::getBilldeskSupportedBanks(), true) === true;
    }

    public static function isAtomSupportedBank($bank)
    {
        return in_array($bank, self::getAtomSupportedBanks(), true) === true;
    }

    public static function isNetbankingBankDirectlySupported($bank)
    {
        return in_array($bank, self::getDirectlyNetbankingBanks(), true) === true;
    }

    public static function getAccountNumberLengths()
    {
        return self::ACCOUNT_NUMBER_LENGTHS;
    }

    public static function getExclusiveIssuersForGateway(string $gateway)
    {
        $otherGatewaySupportedBanks = self::$self;

        $gatewayExclusiveBanks = self::$gatewaySupportedBanks[$gateway]['retail'];

        foreach (Gateway::SHARED_NETBANKING_GATEWAYS_LIVE as $netbankingGateway)
        {
            if ($gateway !== $netbankingGateway)
            {
                $netbankingGatewayBanks = self::$gatewaySupportedBanks[$netbankingGateway]['retail'];
                $gatewayExclusiveBanks = array_diff($gatewayExclusiveBanks, $netbankingGatewayBanks);
            }
        }

        $gatewayExclusiveBanks = array_values(array_diff($gatewayExclusiveBanks, self::$self));

        return $gatewayExclusiveBanks;
    }

    public static function isIssuerExclusiveToGateway(string $issuer, string $gateway)
    {
        $gatewayExclusiveBanks = self::getExclusiveIssuersForGateway($gateway);

        return in_array($issuer, $gatewayExclusiveBanks, true) === true;
    }

    public static function isCorporateBank($bank)
    {
        $billdeskCorp = self::$gatewaySupportedBanks[Gateway::BILLDESK]['corp'] ?? [];
        $corpExclusiveBank = array_merge(self::$selfCorp, $billdeskCorp);

        return in_array($bank, $corpExclusiveBank, true) === true;
    }

    public static function getSupportedBanksForGateway(
        string $gateway,
        int $bankingType = BankingType::RETAIL_ONLY,
        int $tpvType = TpvType::NON_TPV_ONLY
    ): array
    {
        $banks = self::$gatewaySupportedBanks[$gateway]['retail'];

        switch ($bankingType)
        {
            case BankingType::CORPORATE_ONLY:
                $banks = self::$gatewaySupportedBanks[$gateway]['corp'] ?? [];

                break;

            case BankingType::BOTH:
                $corpBanks = self::$gatewaySupportedBanks[$gateway]['corp'] ?? [];
                $banks = array_values(array_unique(array_merge($banks, $corpBanks)));

                break;

            default:
                break;
        }

        switch ($tpvType)
        {
            case TpvType::TPV_ONLY:
                $banks = self::$gatewaySupportedBanks[$gateway]['tpv'] ?? [];

                break;

            case TpvType::BOTH_TPV_NON_TPV:
                $tpvBanks = self::$gatewaySupportedBanks[$gateway]['tpv'] ?? [];
                $banks = array_values(array_unique(array_merge($banks, $tpvBanks)));

                break;

            default:
                break;
        }

        return $banks;
    }
}
