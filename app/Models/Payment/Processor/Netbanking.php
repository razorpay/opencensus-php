<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\Bank;
use RZP\Models\Bank\IFSC;
use RZP\Models\Bank\Name;
use RZP\Models\Merchant\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Feature\Constants;
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
    const KKBK_C = 'KKBK_C';
    const IDIB_C = 'IDIB_C';
    const HDFC_C = 'HDFC_C';
    const AUBL_C = 'AUBL_C';

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
        self::KKBK_C,
        self::HDFC_C,
    ];

    protected static $names = [
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
        self::SVCB_C => 'SVC Co-Operative Bank Ltd. - Corporate Banking',
        self::UTIB_C => 'Axis Bank - Corporate Banking',
        self::YESB_C => 'Yes Bank - Corporate Banking',
        self::KKBK_C => 'Kotak Mahindra Bank - Corporate Banking',
        self::IDIB_C => 'Indian Bank - Corporate Banking',
        self::HDFC_C => 'HDFC Bank - Corporate Banking',
        self::AUBL_C => 'AU Small Finance Bank - Corporate Banking',
        IFSC::ORBC   => 'PNB (Erstwhile-Oriental Bank of Commerce)',
        IFSC::UTBI   => 'PNB (Erstwhile-United Bank of India)',
        IFSC::CORP   => 'Union Bank of India (Erstwhile Corporation Bank)',
        IFSC::ALLA   => 'Indian Bank (Erstwhile Allahabad Bank)',
        IFSC::VIJB   => 'Bank of Baroda - Retail Banking (Erstwhile Vijaya Bank)',
        IFSC::HSBC   => 'HSBC',
        IFSC::SVCB   => 'SVC Co-Operative Bank Ltd.',
    ];

    const ACCOUNT_NUMBER_LENGTHS = [
        IFSC::UTIB => 15,
        IFSC::FDRL => 14,
        IFSC::CSBK => 18,
    ];

    protected static $self = [
        IFSC::ICIC,
        IFSC::HDFC,
        IFSC::UTIB,
        IFSC::KKBK,
        IFSC::AIRP,
        IFSC::FDRL,
        IFSC::IDFB,
        IFSC::RATN,
        IFSC::UBIN,
        IFSC::SCBL,
        IFSC::JAKA,
        IFSC::INDB,
        IFSC::CSBK,
        IFSC::ALLA,     // due to bank merger, will be routed through IDIB direct integration
        IFSC::CNRB,
        IFSC::CIUB,
        IFSC::IDIB,
        IFSC::ESFB,
        IFSC::SBIN,
        IFSC::CBIN,
        IFSC::YESB,
        IFSC::IBKL,
        IFSC::SIBL,
        IFSC::KVBL,
        IFSC::SVCB,
        self::PUNB_R,
        self::BARB_R,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::SBTR,
        IFSC::JSFB,
        IFSC::IOBA,
        IFSC::FSFB,
        IFSC::DCBL,
        IFSC::SYNB,   // due to bank merger, will be routed through CNRB direct integration
        IFSC::CORP,   // due to bank merger, will be routed through UBIN direct integration
        IFSC::AUBL,
        IFSC::DLXB,
        IFSC::NSPB,
        IFSC::VIJB,
        IFSC::BDBL,
        IFSC::SRCB,
        IFSC::UCBA,
        IFSC::UJVN,
        IFSC::TMBL,
        IFSC::KARB,
        IFSC::DBSS,
        self::LAVB_R,
    ];

    protected static $selfCorp = [
        self::ICIC_C,
        self::UTIB_C,
        self::BARB_C,
        self::PUNB_C,
        self::KKBK_C,
        self::IDIB_C,
        self::RATN_C,
        self::HDFC_C,
        self::AUBL_C,
    ];

    protected static $selfCorporateMakerCheckerFlow = [
        self::ICIC_C,
        self::UTIB_C,
        self::BARB_C,
        self::KKBK_C,
        self::RATN_C,
        self::HDFC_C,
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
        IFSC::SIBL,
        IFSC::YESB,
        IFSC::CIUB,
        IFSC::IDIB,
        IFSC::CBIN,
        IFSC::SBIN,
        self::BARB_R,
        IFSC::SVCB,
        IFSC::IBKL,
        IFSC::JAKA,
        IFSC::IOBA,
        IFSC::ESFB,
        IFSC::UBIN,
        IFSC::SCBL,
        IFSC::CORP,     // due to bank merger, will be routed through UBIN direct integration
        IFSC::AUBL,
        IFSC::DLXB,
        self::PUNB_R,
        IFSC::BDBL,
        IFSC::UCBA,
        IFSC::CNRB,
        IFSC::DCBL,
        IFSC::SRCB,
        IFSC::UCBA,
        IFSC::UJVN,
        IFSC::TMBL,
        IFSC::KARB,
        IFSC::DBSS,
        self::LAVB_R,
        IFSC::SRCB,
    ];

    protected static $defaultGatewayDisabledBanks = [
        Gateway::BILLDESK => [
            'retail' => [
                IFSC::ALLA,
                IFSC::CORP,
                IFSC::CSBK,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::HSBC,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::INDB,
                IFSC::KKBK,
                IFSC::ORBC,
                IFSC::RATN,
                IFSC::UTIB,
                self::BARB_R,
            ],
            'tpv' => [
                IFSC::HDFC,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::INDB,
                IFSC::KKBK,
                IFSC::SIBL,
            ],
        ],
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    protected static $gatewaySupportedBanks = [
        Gateway::BILLDESK => [
            'retail' => [
                IFSC::ALLA,
                IFSC::AUBL,
                IFSC::BACB,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                /*As CORP is now redirected to UBIN. For this, We are Removing CORP
                from billdesk tpv as billdesk doesn't support UBIN for tpv. That's why no redirection
                can be done for billdesk tpv of CORP to UBIN. Eventhough we are redirecting
                the retail from billdesk and payu. As in these both gateways request of CORP
                can be redirect to UBIN.
                */
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::ESAF,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::HSBC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KCCB,
                IFSC::KJSB,
                IFSC::KKBK,
                IFSC::KVBL,
                IFSC::MAHB,
                IFSC::MSNU,
                IFSC::NESF,
                IFSC::NKGS,
                IFSC::ORBC,
                IFSC::PSIB,
                IFSC::RATN,
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
                IFSC::UTIB,
                IFSC::VARA,
                IFSC::YESB,
                IFSC::ZCBL,
                self::BARB_C,
                self::BARB_R,
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
                IFSC::BKID,
                IFSC::CIUB,
                /*As CORP is now redirected to UBIN. For this, We are Removing CORP
                from billdesk tpv as billdesk doesn't support UBIN for tpv. That's why no redirection
                can be done for billdesk tpv of CORP to UBIN.
                */
             // IFSC::CORP,
                IFSC::HDFC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::INDB,
                IFSC::KKBK,
                IFSC::KVBL,
                IFSC::SBBJ,
                IFSC::SBHY,
                IFSC::SBIN,
                IFSC::SBMY,
                IFSC::SBTR,
                IFSC::SIBL,
                IFSC::STBP,
                IFSC::UTIB,
                IFSC::SRCB,
                self::LAVB_R,
            ],
        ],
        Gateway::ATOM => [
            'retail' => [
                // IFSC::FDRL,
                // IFSC::INDB,
                // IFSC::ORBC,
                // IFSC::RATN,
                IFSC::ABNA,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
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
                IFSC::YESB,
                IFSC::HDFC,
                IFSC::ICIC,
                IFSC::UTIB,
                IFSC::KKBK,
                self::LAVB_R,
                self::PUNB_R,
                self::KKBK_C,
                self::HDFC_C,
                self::ICIC_C,
            ],
            'tpv' => [
                IFSC::ALLA,
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
                IFSC::HDFC,
                IFSC::ICIC,
                IFSC::UTIB,
                IFSC::KKBK,
                self::LAVB_R,
                self::BKID_C,
                self::PUNB_R,
            ]
        ],
        Gateway::EBS => [
            'retail' => [
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
                IFSC::AIRP,
                IFSC::ABNA,
                IFSC::ALLA,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JSBP,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_R,
                IFSC::RATN,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                IFSC::VIJB,
                IFSC::YESB,
            ],
        ],
        Gateway::PAYU => [
            'retail' => [
                IFSC::AIRP,
                IFSC::ALLA,
                IFSC::ANDB,
                self::BARB_C,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::IBKL,
                self::IBKL_C,
                IFSC::ICIC,
                self::ICIC_C,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_C,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                self::UTIB_C,
                IFSC::YESB,
            ]
        ],
        Gateway::CASHFREE=>[
            'retail'=>[
                IFSC::AUBL,
                self::BARB_C,
                self::BARB_R,
                IFSC::BDBL,
                IFSC::BKID,
                self::BKID_C,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                self::DLXB_C,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::HSBC,
                IFSC::IBKL,
                IFSC::ICIC,
                self::ICIC_C,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_C,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::RATN,
                self::RATN_C,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::SVCB,
                self::SVCB_C,
                IFSC::TMBL,
                IFSC::TNSC,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTIB,
                self::UTIB_C,
                IFSC::YESB,
                self::YESB_C,
            ]
        ],
        Gateway::ZAAKPAY => [
            'retail' => [
                IFSC::AIRP,
                IFSC::ALLA,
                IFSC::ANDB,
                self::BARB_C,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_C,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::RATN,
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
                IFSC::TMBL,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                IFSC::YESB,
            ],
        ],
        Gateway::INGENICO => [
            'retail' => [
                IFSC::ANDB,
                self::BARB_R,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CSBK,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_R,
                IFSC::RATN,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                IFSC::VIJB,
                IFSC::YESB,
            ],
        ],
        Gateway::CCAVENUE => [
            'retail' => [
                IFSC::AIRP,
                IFSC::ANDB,
                IFSC::AUBL,
                self::BARB_C,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::NKGS,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::RATN,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::SURY,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                IFSC::VIJB,
                IFSC::YESB,
            ],
        ],
        Gateway::BILLDESK_OPTIMIZER => [
            'retail' => [
                IFSC::ALLA,
                IFSC::AUBL,
                self::BARB_C,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DBSS,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                self::DLXB_C,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::HSBC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_C,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::NKGS,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::RATN,
                self::RATN_C,
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
                self::SVCB_C,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::TNSC,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
                IFSC::UTIB,
                IFSC::YESB,
                self::YESB_C,
            ],
        ],
        Gateway::OPTIMIZER_RAZORPAY => [
            'retail' => [
                IFSC::AIRP,
                IFSC::ANDB,
                IFSC::AUBL,
                self::BARB_C,
                self::BARB_R,
                IFSC::BBKM,
                IFSC::BDBL,
                IFSC::BKID,
                IFSC::CBIN,
                IFSC::CIUB,
                IFSC::CNRB,
                IFSC::CORP,
                IFSC::COSB,
                IFSC::CSBK,
                IFSC::DCBL,
                IFSC::DEUT,
                IFSC::DLXB,
                IFSC::ESFB,
                IFSC::FDRL,
                IFSC::HDFC,
                IFSC::IBKL,
                IFSC::ICIC,
                IFSC::IDFB,
                IFSC::IDIB,
                IFSC::INDB,
                IFSC::IOBA,
                IFSC::JAKA,
                IFSC::JSBP,
                IFSC::KARB,
                IFSC::KKBK,
                IFSC::KVBL,
                self::LAVB_R,
                IFSC::MAHB,
                IFSC::NKGS,
                IFSC::ORBC,
                IFSC::PSIB,
                self::PUNB_C,
                self::PUNB_R,
                IFSC::RATN,
                IFSC::SBIN,
                IFSC::SCBL,
                IFSC::SIBL,
                IFSC::SRCB,
                IFSC::SURY,
                IFSC::SVCB,
                IFSC::SYNB,
                IFSC::TMBL,
                IFSC::UBIN,
                IFSC::UCBA,
                IFSC::UTBI,
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
                self::BARB_R,
                IFSC::VIJB,
            ],
            'corp' => [
                self::BARB_C
            ],
            'tpv' => [
                self::BARB_R,
                IFSC::VIJB,
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
        Gateway::NETBANKING_UBI => [
            'retail' => [
                IFSC::UBIN,
                IFSC::CORP,
            ],
            'tpv'   => [
                IFSC::UBIN,
                IFSC::CORP,
            ],
        ],
        Gateway::NETBANKING_SCB => [
            'retail' => [
                IFSC::SCBL,
            ],
            'tpv'   => [
                IFSC::SCBL,
            ],
        ],
        Gateway::NETBANKING_JKB => [
            'retail' => [
                IFSC::JAKA,
            ],
            'tpv' => [
                IFSC::JAKA,
            ]
        ],
        Gateway::NETBANKING_CBI => [
            'retail' => [
                IFSC::CBIN
            ],
            'tpv' => [
                IFSC::CBIN
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
        Gateway::NETBANKING_HDFC => [
            'retail' => [
                IFSC::HDFC
            ],
            'tpv' => [
                IFSC::HDFC
            ],
            'corp' => [
                self::HDFC_C,
            ]
        ],
        Gateway::NETBANKING_CANARA => [
            'retail' => [
                IFSC::CNRB,
                IFSC::SYNB,
            ],
            'tpv'   => [
                IFSC::CNRB,
                IFSC::SYNB,
            ],
        ],
        Gateway::NETBANKING_EQUITAS => [
            'retail' => [
                IFSC::ESFB
            ],
            'tpv'    => [
                IFSC::ESFB
            ],
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
        Gateway::NETBANKING_IBK => [
            'retail' => [
                IFSC::IDIB,
                IFSC::ALLA,
            ],
            'tpv' => [
                IFSC::IDIB,
                IFSC::ALLA,
            ],
            'corp' => [
                self::IDIB_C,
            ]
        ],
        Gateway::NETBANKING_IDBI => [
            'retail' => [
                IFSC::IBKL,
            ],
            'tpv' => [
                IFSC::IBKL,
            ]
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
            'corp' => [
                self::KKBK_C
            ],
        ],
        Gateway::NETBANKING_RBL => [
            'retail' => [
                IFSC::RATN,
            ],
            'tpv' => [
                IFSC::RATN,
            ],
            'corp' => [
                self::RATN_C
            ]
        ],
        Gateway::NETBANKING_SARASWAT => [
            'retail' => [
                IFSC::SRCB,
            ],
            'tpv' => [
                IFSC::SRCB,
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
                IFSC::ORBC,
                IFSC::UTBI,
            ],
            'tpv' => [
                self::PUNB_R,
            ],
            'corp' => [
                self::PUNB_C
            ]
        ],
        Gateway::NETBANKING_SBI => [
            'retail' => [
                IFSC::SBIN,
                IFSC::SBBJ,
                IFSC::SBHY,
                IFSC::SBMY,
                IFSC::STBP,
                IFSC::SBTR,
            ],
            'tpv' => [
                IFSC::SBIN
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
        Gateway::NETBANKING_KVB =>  [
            'retail'    =>  [
                IFSC::KVBL,
            ],
            'tpv'   => [
                IFSC::KVBL,
            ],
        ],
        Gateway::NETBANKING_SVC => [
            'retail' => [
                IFSC::SVCB,
            ],
            'tpv' => [
                IFSC::SVCB,
            ]
        ],
        Gateway::NETBANKING_JSB =>  [
            'retail'    =>  [
                IFSC::JSFB,
            ]
        ],
        Gateway::NETBANKING_IOB => [
            'retail' =>  [
                IFSC::IOBA
            ],
            'tpv' => [
                IFSC::IOBA,
            ],
        ],
         Gateway::NETBANKING_FSB => [
            'retail'    =>  [
                IFSC::FSFB,
            ]
        ],
        Gateway::NETBANKING_DCB => [
            'retail' => [
                IFSC::DCBL,
            ],
            'tpv' => [
                IFSC::DCBL,
            ]
        ],
        Gateway::NETBANKING_AUSF => [
            'retail' => [
                IFSC::AUBL,
            ],
            'tpv' => [
                IFSC::AUBL,
            ],
            'corp'=> [
                self::AUBL_C,
            ]
        ],
        Gateway::NETBANKING_DLB => [
            'retail' => [
                IFSC::DLXB,
            ],
            'tpv' => [
                IFSC::DLXB,
            ]
        ],
        Gateway::NETBANKING_NSDL => [
            'retail' => [
                IFSC::NSPB,
            ]
        ],
        Gateway::NETBANKING_BDBL => [
            'retail' => [
                IFSC::BDBL,
            ],
            'tpv'    => [
                IFSC::BDBL,
            ]
        ],
        Gateway::NETBANKING_SARASWAT => [
            'retail' => [
                IFSC::SRCB,
            ],
            'tpv' => [
                IFSC::SRCB,
            ]
        ],
        Gateway::NETBANKING_UCO => [
            'retail' => [
                IFSC::UCBA,
            ],
            'tpv' => [
                IFSC::UCBA,
            ]
        ],
        Gateway::NETBANKING_UJJIVAN => [
            'retail' => [
                IFSC::UJVN,
            ],
            'tpv' => [
                IFSC::UJVN,
            ]
        ],
        Gateway::NETBANKING_TMB => [
            'retail' => [
                IFSC::TMBL,
            ],
            'tpv'    => [
                IFSC::TMBL,
            ]
        ],
        Gateway::NETBANKING_KARNATAKA => [
            'retail' => [
                IFSC::KARB,
            ],
            'tpv'    => [
                IFSC::KARB,
            ]
        ],
        Gateway::NETBANKING_DBS => [
            'retail' => [
                IFSC::DBSS,
                self::LAVB_R,
            ],
            'tpv'    => [
                IFSC::DBSS,
                self::LAVB_R,
            ]
        ]
    ];

    protected static $defaultDisabled = [
    ];

    const DEFAULT_DISABLED_BANKS = [
        IFSC::BBKM,
        IFSC::BKID,
        IFSC::COSB,
        IFSC::DBSS,
        IFSC::JSBP,
        IFSC::NKGS,
        IFSC::SYNB,
        IFSC::TNSC,
        IFSC::ABNA,
        IFSC::TJSB,
        IFSC::KJSB,
        IFSC::MSNU,
        IFSC::BDBL,
        IFSC::BACB,
        IFSC::KCCB,
        IFSC::TBSB,
        IFSC::SURY,
        IFSC::ESAF,
        IFSC::VARA,
        IFSC::NESF,
        IFSC::ZCBL,
        IFSC::CIUB,
        IFSC::FDRL,
        IFSC::HSBC,
        IFSC::HDFC,
        IFSC::SBIN,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBMY,
        IFSC::SBTR,
        IFSC::STBP,
        IFSC::ICIC,
        IFSC::SCBL,
        IFSC::UTIB,
        self::LAVB_C,
        self::UTIB_C,
        self::IBKL_C,
        self::YESB_C,
        self::RATN_C,
        self::DLXB_C,
        self::SVCB_C,
        self::BARB_C,
        self::PUNB_C,
        self::ICIC_C,
        self::KKBK_C,
        self::HDFC_C,
        self::AUBL_C,
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
        // Merge billdesk, atom, ebs and paytm supported banks and remove
        // duplicate values
        //

        return array_values(array_unique(array_merge(
                                            self::$gatewaySupportedBanks[Gateway::BILLDESK]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::ATOM]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::EBS]['corp'] ?? [],
                                            self::$gatewaySupportedBanks[Gateway::PAYTM]['retail'],
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

    public static function getPayuSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::PAYU]['retail'];
    }
    public static function getCashfreeSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::CASHFREE]['retail'];
    }

    public static function getCcavenueSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::CCAVENUE]['retail'];
    }

    public static function getZaakpaySupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::ZAAKPAY]['retail'];
    }

    public static function getIngenicoSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::INGENICO]['retail'];
    }

    public static function getBilldeskOptimizerSupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::BILLDESK_OPTIMIZER]['retail'];
    }

    public static function getOptimizerRazorpaySupportedBanks()
    {
        return self::$gatewaySupportedBanks[Gateway::OPTIMIZER_RAZORPAY]['retail'];
    }

    public static function getDirectlyNetbankingBanks()
    {
        return array_merge(self::$self, self::$selfCorp);
    }

    /**
     * Gets supported banks for a merchant.
     * Checks for TPV merchants and any bank disabled by category
     * @param Entity $merchant
     * @return array
     */
    public static function getSupportedBanks($merchant = null)
    {
        $banks = self::getSupportedBanksInLiveMode();

        // Remove corporate banks if feature is not enabled.
        if ((isset($merchant) === true) and
            ($merchant->isFeatureEnabled(Constants::CORPORATE_BANKS) === false))
        {
            $banks = array_diff($banks, self::$selfCorp);
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
        return array_values(array_unique(array_merge(
                                            self::$gatewaySupportedBanks[Gateway::BILLDESK]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::ATOM]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::EBS]['retail'],
                                            self::$gatewaySupportedBanks[Gateway::PAYTM]['retail'],
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

    public static function isPayuSupportedBank($bank)
    {
        return in_array($bank, self::getPayuSupportedBanks(), true) === true;
    }

    public static function isCashfreeSupportedBank($bank)
    {
        return in_array($bank, self::getCashfreeSupportedBanks(), true) === true;
    }

    public static function isCcavenueSupportedBank($bank)
    {
        return in_array($bank, self::getCcavenueSupportedBanks(), true) === true;
    }

    public static function isZaakpaySupportedBank($bank)
    {
        return in_array($bank, self::getZaakpaySupportedBanks(), true) === true;
    }

    public static function isIngenicoSupportedBank($bank)
    {
        return in_array($bank, self::getIngenicoSupportedBanks(), true) === true;
    }

    public static function isBilldesk_OptimizerSupportedBank($bank)
    {
        return in_array($bank, self::getBilldeskOptimizerSupportedBanks(), true) === true;
    }

    public static function isOptimizer_RazorpaySupportedBank($bank)
    {
        return in_array($bank, self::getOptimizerRazorpaySupportedBanks(), true) === true;
    }

    public static function isNetbankingBankDirectlySupported($bank): bool
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

    public static function isCorporateBank($bank): bool
    {
        return in_array($bank, self::$selfCorp, true) === true;
    }

    public static function isCorporateMakerCheckerBank($bank): bool
    {
        return in_array($bank, self::$selfCorporateMakerCheckerFlow, true) === true;
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

    public static function getDefaultDisabledBanksForGateway(
        string $gateway,
        int $bankingType = BankingType::RETAIL_ONLY,
        int $tpvType = TpvType::NON_TPV_ONLY
    ): array
    {
        $gatewayObject = self::$defaultGatewayDisabledBanks[$gateway] ?? [];

        if (empty($gatewayObject) === true)
        {
            return [];
        }

        $banks = $gatewayObject['retail'];

        switch ($bankingType)
        {
            case BankingType::CORPORATE_ONLY:
                $banks = $gatewayObject['corp'] ?? [];

                break;

            case BankingType::BOTH:
                $corpBanks = $gatewayObject['corp'] ?? [];
                $banks = array_values(array_unique(array_merge($banks, $corpBanks)));

                break;

            default:
                break;
        }

        switch ($tpvType)
        {
            case TpvType::TPV_ONLY:
                $banks = $gatewayObject['tpv'] ?? [];

                break;

            case TpvType::BOTH_TPV_NON_TPV:
                $tpvBanks = $gatewayObject['tpv'] ?? [];
                $banks = array_values(array_unique(array_merge($banks, $tpvBanks)));

                break;

            default:
                break;
        }

        return $banks;
    }

    public static function getGatewaySupportedBankList(): array
    {
        return self::$gatewaySupportedBanks;
    }

    public static function isNbRearchBank(string $bank): bool
    {
        $rearchBanks = [
            Bank::YESB,
            Bank::DBSS,
            Bank::UJVN,
            Bank::ICIC
        ];

        return in_array($bank, $rearchBanks, true);
    }

    public static function banksRoutedAlwaysThroughNbRearch(string $bank): bool
    {
        $rearchBanks = [];

        return in_array($bank, $rearchBanks, true);
    }
}
