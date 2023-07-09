<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;

class ProviderCode
{
    /**
     * This is based on data from
     * @cashlessconsumer
     *
     * See http://bit.ly/UPIApps
     * for a spreadsheet
     *
     * These are all the providers we know of
     * This appears after @ in the VPA
     *
     * You can run php artisan upi:verify_providers
     * on the command line to check against
     * the spreadsheet
     *
     * Helpful link: https://www.npci.org.in/what-we-do/upi/3rd-party-apps
     */
    const ABFSPAY            = 'abfspay';
    const AIRTEL             = 'airtel';
    const AIRTELPAYMENTSBANK = 'airtelpaymentsbank';
    const ALBK               = 'albk';
    const ALLAHABADBANK      = 'allahabadbank';
    const ALLBANK            = 'allbank';
    const ANDB               = 'andb';
    const APB                = 'apb';
    const APL                = 'apl';
    const YAPL               = 'yapl';
    const AUBANK             = 'aubank';
    const AXIS               = 'axis';
    const AXISBANK           = 'axisbank';
    const AXISGO             = 'axisgo';
    const AXISB              = 'axisb';
    CONST AXL                = 'axl';
    const BANDHAN            = 'bandhan';
    const BARODAMPAY         = 'barodampay';
    const BARODAPAY          = 'barodapay';
    const BIRLA              = 'birla';
    const BOB                = 'bob';
    const BOI                = 'boi';
    const CBIN               = 'cbin';
    const CBOI               = 'cboi';
    const CENTRALBANK        = 'centralbank';
    const CITI               = 'citi';
    const CITIBANK           = 'citibank';
    const CITIGOLD           = 'citigold';
    const CMSIDFC            = 'cmsidfc';
    const CNRB               = 'cnrb';
    const CSBCASH            = 'csbcash';
    const CSBPAY             = 'csbpay';
    const CUB                = 'cub';
    const DB                 = 'db';
    const DBS                = 'dbs';
    const DCB                = 'dcb';
    const DCBBANK            = 'dcbbank';
    const DENABANK           = 'denabank';
    const DLB                = 'dlb';
    const EAZYPAY            = 'eazypay';
    const EQUITAS            = 'equitas';
    const EZEEPAY            = 'ezeepay';
    const FBL                = 'fbl';
    const FBPE               = 'fbpe';
    const FEDERAL            = 'federal';
    const FINOBANK           = 'finobank';
    const FREECHARGE         = 'freecharge';
    const HDFCBANK           = 'hdfcbank';
    const HDFCBANKJD         = 'hdfcbankjd';
    const HSBC               = 'hsbc';
    const IBL                = 'ibl';
    const ICICI              = 'icici';
    const ICICIPAY           = 'icicipay';
    const ICICIBANK          = 'icicibank';
    const IDBI               = 'idbi';
    const IDBIBANK           = 'idbibank';
    const IDFC               = 'idfc';
    const IDFCBANK           = 'idfcbank';
    const IDFCFIRST          = 'idfcfirst';
    const IDFCNETC           = 'idfcnetc';
    const IDFCFIRSTBANK      = 'idfcfirstbank';
    const IKWIK              = 'ikwik';
    const IMOBILE            = 'imobile';
    const INDBANK            = 'indbank';
    const INDIANBANK         = 'indianbank';
    const INDIANBK           = 'indianbk';
    const INDUS              = 'indus';
    const IOB                = 'iob';
    const JIO                = 'jio';
    const JKB                = 'jkb';
    const JSB                = 'jsb';
    const JSBP               = 'jsbp';
    const JUPITERAXIS        = 'jupiteraxis';
    const KARB               = 'karb';
    const KARURVYSYABANK     = 'karurvysyabank';
    const KAYPAY             = 'kaypay';
    const KBL                = 'kbl';
    const KBL052             = 'kbl052';
    const KMB                = 'kmb';
    const KMBL               = 'kmbl';
    const KOTAK              = 'kotak';
    const KVB                = 'kvb';
    const KVBANK             = 'kvbank';
    const LIME               = 'lime';
    const LVB                = 'lvb';
    const LVBANK             = 'lvbank';
    const MAHB               = 'mahb';
    const MAIRTEL            = 'mairtel';
    const MYICICI            = 'myicici';
    const OBC                = 'obc';
    const OKAXIS             = 'okaxis';
    const OKBIZAXIS          = 'okbizaxis';
    const OKHDFCBANK         = 'okhdfcbank';
    const OKICICI            = 'okicici';
    const OKSBI              = 'oksbi';
    const PAYTM              = 'paytm';
    const PAYZAPP            = 'payzapp';
    const PINGPAY            = 'pingpay';
    const PNB                = 'pnb';
    const PNBPAY             = 'pnbpay';
    const POCKETS            = 'pockets';
    const POSTBANK           = 'postbank';
    const PSB                = 'psb';
    const PURZ               = 'purz';
    const RAJGOVHDFCBANK     = 'rajgovhdfcbank';
    const RBL                = 'rbl';
    const RMHDFCBANK         = 'rmhdfcbank';
    const S2B                = 's2b';
    const SBI                = 'sbi';
    const SC                 = 'sc';
    const SCB                = 'scb';
    const SCBL               = 'scbl';
    const SCMOBILE           = 'scmobile';
    const SIB                = 'sib';
    const SRCB               = 'srcb';
    const SYND               = 'synd';
    const SYNDBANK           = 'syndbank';
    const SYNDICATE          = 'syndicate';
    const TJSB               = 'tjsb';
    const TRANS              = 'trans';
    const UBI                = 'ubi';
    const UBOI               = 'uboi';
    const UCO                = 'uco';
    const UNIONBANK          = 'unionbank';
    const UNIONBANKOFINDIA   = 'unionbankofindia';
    const UNITED             = 'united';
    const UPI                = 'upi';
    const UTBI               = 'utbi';
    const VIJAYABANK         = 'vijayabank';
    const VIJB               = 'vijb';
    const VJB                = 'vjb';
    const WAAXIS             = 'waaxis';
    const WAHDFCBANK         = 'wahdfcbank';
    const WAICICI            = 'waicici';
    const WASBI              = 'wasbi';
    const YBL                = 'ybl';
    const YESBANK            = 'yesbank';
    const YESBANKLTD         = 'yesbankltd';
    const YESB               = 'yesb';
    const NSDL               = 'nsdl';
    const TIMECOSMOS         = 'timecosmos';
    const TAPICICI           = 'tapicici';
    const LIV                = 'liv';
    const SLICEAXIS          = 'sliceaxis';
    const PZ                 = 'pz';
    const APAY               = 'apay';
    const AMAZON_PAY         = 'amazonpay';
    const AMAZON             = 'amazon';
    const EQUITASBANK        = 'equitasbank';
    const GROWW              = 'yesg';
    const OKCREDIT           = 'axb';
    const SLASH              = 'icici';
    const YESG               = 'yesg';
    const AXB                = 'axb';
    const FAM                = 'fam';
    const RAPL               = 'rapl';
    const PINELABS           = 'pinelabs';
    const ZOICICI            = 'zoicici';
    const GOAXB              = 'goaxb';
    const TMB                = 'tmb';
    const UTKARSHBANK        = 'utkarshbank';
    const OMNI               = 'omni';
    const DHANI              = 'dhani';
    const NIYOICICI          = 'niyoicici';
    const NAVIAXIS           = 'naviaxis';
    const SHRIRAMHDFCBANK    = 'shriramhdfcbank';

    //Only for test Upi
    const RAZORPAY         = 'razorpay';

    // IFSC
    const PPIW           = 'PPIW';

    /**
     * PSP handle to bank code mapping
     * In some cases, we don't have the proper
     * bank details, in those cases we are
     * mapping the psp to NPCI code.
     */
    protected static $bankCodes = [
        self::ABFSPAY            => IFSC::UTBI,
        self::AIRTEL             => IFSC::AIRP,
        self::AIRTELPAYMENTSBANK => IFSC::AIRP,
        self::ALBK               => IFSC::ALLA,
        self::ALLAHABADBANK      => IFSC::ALLA,
        self::ALLBANK            => IFSC::ALLA,
        self::ANDB               => IFSC::ANDB,
        self::APB                => IFSC::AIRP,
        self::APL                => IFSC::AIRP,
        self::YAPL               => IFSC::YESB,
        self::AUBANK             => IFSC::AUBL,
        self::AXIS               => IFSC::UTIB,
        self::AXISBANK           => IFSC::UTIB,
        self::AXISGO             => IFSC::UTIB,
        self::AXISB              => IFSC::UTIB,
        self::AXL                => IFSC::UTIB,
        self::BANDHAN            => IFSC::BDBL,
        self::BARODAMPAY         => IFSC::BARB,
        self::BARODAPAY          => IFSC::BARB,
        self::BIRLA              => IFSC::ABPB,
        self::BOB                => IFSC::BARB,
        self::BOI                => IFSC::BKID,
        self::CBIN               => IFSC::CBIN,
        self::CBOI               => IFSC::CBIN,
        self::CENTRALBANK        => IFSC::CBIN,
        self::CITI               => IFSC::CITI,
        self::CITIBANK           => IFSC::CITI,
        self::CITIGOLD           => IFSC::CITI,
        self::CMSIDFC            => IFSC::IDFB,
        self::CNRB               => IFSC::CNRB,
        self::CSBCASH            => IFSC::CSBK,
        self::CSBPAY             => IFSC::CSBK,
        self::CUB                => IFSC::CIUB,
        self::DB                 => IFSC::DEUT,
        self::DBS                => IFSC::DBSS,
        self::DCB                => IFSC::DCBL,
        self::DCBBANK            => IFSC::DCBL,
        self::DENABANK           => IFSC::BKDN,
        self::DHANI              => 'NPCI',
        self::DLB                => IFSC::DLXB,
        self::EAZYPAY            => IFSC::ICIC,
        self::EQUITAS            => IFSC::ESFB,
        self::EZEEPAY            => 'NPCI',
        self::FBL                => IFSC::FDRL,
        self::FBPE               => IFSC::FDRL,
        self::FEDERAL            => IFSC::FDRL,
        self::FINOBANK           => IFSC::FINO,
        self::FREECHARGE         => IFSC::UTIB,
        self::HDFCBANK           => IFSC::HDFC,
        self::HDFCBANKJD         => IFSC::HDFC,
        self::HSBC               => IFSC::HSBC,
        self::IBL                => IFSC::ICIC,
        self::ICICI              => IFSC::ICIC,
        self::ICICIBANK          => IFSC::ICIC,
        self::ICICIPAY           => IFSC::ICIC,
        self::IDBI               => IFSC::IBKL,
        self::IDBIBANK           => IFSC::IBKL,
        self::IDFC               => IFSC::IDFB,
        self::IDFCBANK           => IFSC::IDFB,
        self::IDFCFIRST          => IFSC::IDFB,
        self::IDFCFIRSTBANK      => IFSC::IDFB,
        self::IDFCNETC           => IFSC::IDFB,
        self::IKWIK              => IFSC::HDFC,
        self::IMOBILE            => IFSC::ICIC,
        self::INDBANK            => IFSC::IDIB,
        self::INDIANBANK         => IFSC::IDIB,
        self::INDIANBK           => IFSC::IDIB,
        self::INDUS              => IFSC::INDB,
        self::IOB                => IFSC::IOBA,
        self::JIO                => IFSC::JIOP,
        self::JSB                => IFSC::JSBP,
        self::JSBP               => IFSC::JSBP,
        self::JKB                => IFSC::JAKA,
        self::JUPITERAXIS        => IFSC::UTBI,
        self::KARB               => IFSC::KARB,
        self::KARURVYSYABANK     => IFSC::KVBL,
        self::KAYPAY             => IFSC::KKBK,
        self::KBL                => IFSC::KARB,
        self::KBL052             => IFSC::KARB,
        self::KMB                => IFSC::KKBK,
        self::KMBL               => IFSC::KKBK,
        self::KOTAK              => IFSC::KKBK,
        self::KVB                => IFSC::KVBL,
        self::KVBANK             => IFSC::KVBL,
        self::LIME               => 'NPCI',
        self::LVB                => IFSC::LAVB,
        self::LVBANK             => IFSC::LAVB,
        self::MAHB               => IFSC::MAHB,
        self::MAIRTEL            => IFSC::AIRP,
        self::MYICICI            => IFSC::ICIC,
        self::OBC                => IFSC::ORBC,
        self::OKAXIS             => IFSC::UTIB,
        self::OKBIZAXIS          => IFSC::UTIB,
        self::OKICICI            => IFSC::ICIC,
        self::OKHDFCBANK         => IFSC::HDFC,
        self::OKSBI              => IFSC::SBIN,
        self::OMNI               => 'NPCI',
        self::PAYTM              => IFSC::PYTM,
        self::PAYZAPP            => IFSC::HDFC,
        self::PINGPAY            => IFSC::UTIB,
        self::PNB                => IFSC::PUNB,
        self::PNBPAY             => IFSC::PUNB,
        self::PSB                => IFSC::PSIB,
        self::POCKETS            => IFSC::ICIC,
        self::POSTBANK           => IFSC::IPOS,
        self::PURZ               => 'NPCI',
        self::RAJGOVHDFCBANK     => IFSC::HDFC,
        self::RBL                => IFSC::RATN,
        self::RMHDFCBANK         => IFSC::HDFC,
        self::S2B                => 'NPCI',
        self::SBI                => IFSC::SBIN,
        self::SC                 => IFSC::SCBL,
        self::SCB                => IFSC::SCBL,
        self::SCBL               => IFSC::SCBL,
        self::SCMOBILE           => IFSC::SCBL,
        self::SIB                => IFSC::SIBL,
        self::SLASH              => IFSC::ICIC,
        self::SRCB               => 'NPCI',
        self::SYND               => IFSC::SYNB,
        self::SYNDBANK           => IFSC::SYNB,
        self::SYNDICATE          => IFSC::SYNB,
        self::TRANS              => self::PPIW,
        self::TJSB               => IFSC::TJSB,
        self::TMB                => IFSC::TMBL,
        self::UBI                => IFSC::UTBI,
        self::UBOI               => IFSC::UBIN,
        self::UCO                => IFSC::UCBA,
        self::UNIONBANK          => IFSC::UBIN,
        self::UNIONBANKOFINDIA   => IFSC::UBIN,
        self::UNITED             => IFSC::UTBI,
        self::UPI                => 'NPCI',
        self::UTBI               => IFSC::UTBI,
        self::UTKARSHBANK        => IFSC::UTKS,
        self::VIJAYABANK         => IFSC::VIJB,
        self::VIJB               => IFSC::VIJB,
        self::VJB                => IFSC::VIJB,
        self::WAAXIS             => IFSC::UTIB,
        self::WAHDFCBANK         => IFSC::HDFC,
        self::WAICICI            => IFSC::ICIC,
        self::WASBI              => IFSC::SBIN,
        self::YBL                => IFSC::YESB,
        self::YESBANK            => IFSC::YESB,
        self::YESBANKLTD         => IFSC::YESB,
        self::RAZORPAY           => 'RZPY',
        self::YESB               => IFSC::YESB,
        self::NSDL               => IFSC::NSPB,
        self::TIMECOSMOS         => IFSC::COSB,
        self::TAPICICI           => IFSC::ICIC,
        self::LIV                => self::PPIW,
        self::SLICEAXIS          => IFSC::UTIB,
        self::PZ                 => IFSC::HDFC,
        self::APAY               => self::PPIW,
        self::AMAZON             => self::PPIW,
        self::AMAZON_PAY         => self::PPIW,
        self::EQUITASBANK        => IFSC::ESFB,
        self::GROWW              => IFSC::YESB,
        self::OKCREDIT           => IFSC::UTIB,
        self::YESG               => IFSC::YESB,
        self::AXB                => IFSC::UTIB,
        self::FAM                => self::PPIW,
        self::RAPL               => IFSC::RATN,
        self::PINELABS           => self::PPIW,
        self::ZOICICI            => IFSC::ICIC,
        self::GOAXB              => IFSC::UTIB,
        self::NIYOICICI          => IFSC::ICIC,
        self::NAVIAXIS           => IFSC::UTIB,
        self::SHRIRAMHDFCBANK    => IFSC::HDFC,
    ];

    /**
     * PSP handle to psp mapping
     */

    protected static $psp = [
        self::OKAXIS        => ProviderPsp::GOOGLE_PAY,
        self::OKHDFCBANK    => ProviderPsp::GOOGLE_PAY,
        self::OKICICI       => ProviderPsp::GOOGLE_PAY,
        self::OKSBI         => ProviderPsp::GOOGLE_PAY,
        self::UPI           => ProviderPsp::BHIM,
        self::ICICI         => ProviderPsp::WHATSAPP,
        self::PAYTM         => ProviderPsp::PAYTM,
        self::YBL           => ProviderPsp::PHONEPE,
        self::IBL           => ProviderPsp::PHONEPE,
        self::AXL           => ProviderPsp::PHONEPE,
        self::APL           => ProviderPsp::AMAZON_PAY,
        self::YAPL          => ProviderPsp::AMAZON_PAY,
        self::BARODAMPAY    => ProviderPsp::BHIM_BARODAPAY,
        self::RAPL          => ProviderPsp::AMAZON_PAY,

        // used only for testing
        self::RAZORPAY      => ProviderPsp::RAZORPAY,
    ];

    /**
     * @var array App to PSP Mapping
     */
    protected static $appToPsp = [
        ProviderApp::GOOGLE_PAY        => ProviderPsp::GOOGLE_PAY,
        ProviderApp::GOOGLE_PAY_APP    => ProviderPsp::GOOGLE_PAY,
        ProviderApp::PHONEPE           => ProviderPsp::PHONEPE,
        ProviderApp::PHONEPE_APP       => ProviderPsp::PHONEPE,
        ProviderApp::PAYTM             => ProviderPsp::PAYTM,
        ProviderApp::PAYTM_APP         => ProviderPsp::PAYTM,
        ProviderApp::BHIM_APP          => ProviderPsp::BHIM,
    ];

    /**
     * @see https://www.npci.org.in/upi-live-ipo
     * @var array Provider which support one time  feature.
     */
    protected static $validOtmProviders = [
        self::BARODAMPAY,
        self::UPI,
        self::ALLBANK,
        self::AXISBANK,
        self::INDUS,
        self::FEDERAL,
        self::SBI,
        self::CITI,
        self::CITIGOLD,
        self::OKHDFCBANK,
        self::OKAXIS,
        self::OKSBI,
        self::OKICICI,
        self::HSBC,
        self::ICICI,
        self::YBL,
        self::SIB,
    ];

    /**
     * @see https://www.bhimupi.org.in/list-banks-and-apps-live-upi-autopay
     * @var array Psp Provider supporting AutoPay
     */
    protected static $validAutoPayPspProvider = [
        ProviderPsp::BHIM,
        ProviderPsp::PAYTM,
        ProviderPsp::PHONEPE,
        ProviderPsp::AMAZON_PAY,
        ProviderPsp::GOOGLE_PAY,
        ProviderPsp::BHIM_BARODAPAY,
        ProviderPsp::BHIM_BOI_UPI,
        ProviderPsp::CANDI_CANARA_BANK,
        ProviderPsp::IMOBILE,
        ProviderPsp::NSDL_JIFFY,
        ProviderPsp::BHIM_AXISPAY,
        ProviderPsp::DAKPAY_UPI_IPBB,
        ProviderPsp::MOBIKWIK,
        ProviderPsp::DIGI_BANK,
        ProviderPsp::BHIM_DLB_UPI,
        ProviderPsp::PAYZAPP,
        ProviderPsp::BHIM_INDUSPAY,
        ProviderPsp::GROWW,
        ProviderPsp::OK_CREDIT,
        ProviderPsp::JIO,
        ProviderPsp::BHIM_SBIPAY,
        ProviderPsp::IDFC,
        ProviderPsp::TATA_NEU,
        ProviderPsp::JUPITER_MONEY,
        ProviderPsp::BHIM_PNB,
        ProviderPsp::FAM_PAY,
        ProviderPsp::FAVE,
        ProviderPsp::ZOMATO,
        ProviderPsp::BAJAJ_FINSERVE,
        ProviderPsp::GO_NIYO,
        ProviderPsp::EQUITAS_SMALL_FINANCE_BANK_LTD,
        ProviderPsp::NAVI,
        ProviderPsp::SHRIRAMONE,
    ];

    /**
     * @var array VPA handles to psp mapping for AutoPay
     */
    protected static $pspForAutopay = [
        self::UPI           => ProviderPsp::BHIM,
        self::PAYTM         => ProviderPsp::PAYTM,
        self::IBL           => ProviderPsp::PHONEPE,
        self::YBL           => ProviderPsp::PHONEPE,
        self::AXL           => ProviderPsp::PHONEPE,
        self::OKHDFCBANK    => ProviderPsp::GOOGLE_PAY,
        self::OKICICI       => ProviderPsp::GOOGLE_PAY,
        self::APL           => ProviderPsp::AMAZON_PAY,
        self::YAPL          => ProviderPsp::AMAZON_PAY,
        self::BARODAMPAY    => ProviderPsp::BHIM_BARODAPAY,
        self::BOI           => ProviderPsp::BHIM_BOI_UPI,
        self::CNRB          => ProviderPsp::CANDI_CANARA_BANK,
        self::ICICI         => ProviderPsp::IMOBILE,
        self::OKAXIS        => ProviderPsp::GOOGLE_PAY,
        self::NSDL          => ProviderPsp::NSDL_JIFFY,
        self::AXISBANK      => ProviderPsp::BHIM_AXISPAY,
        self::POSTBANK      => ProviderPsp::DAKPAY_UPI_IPBB,
        self::IKWIK         => ProviderPsp::MOBIKWIK,
        self::DBS           => ProviderPsp::DIGI_BANK,
        self::DLB           => ProviderPsp::BHIM_DLB_UPI,
        self::SLICEAXIS     => ProviderPsp::BHIM_AXISPAY,
        self::PZ            => ProviderPsp::PAYZAPP,
        self::INDUS         => ProviderPsp::BHIM_INDUSPAY,
        self::APAY          => ProviderPsp::AMAZON_PAY,
        self::AMAZON        => ProviderPsp::AMAZON_PAY,
        self::AMAZON_PAY    => ProviderPsp::AMAZON_PAY,
        self::YESG          => ProviderPsp::GROWW,
        self::AXB           => ProviderPsp::OK_CREDIT,
        self::JIO           => ProviderPsp::JIO,
        self::SBI           => ProviderPsp::BHIM_SBIPAY,
        self::FAM           => ProviderPsp::IDFC,
        self::TAPICICI      => ProviderPsp::TATA_NEU,
        self::JUPITERAXIS   => ProviderPsp::JUPITER_MONEY,
        self::PNB           => ProviderPsp::BHIM_PNB,
        self::FAM           => ProviderPsp::FAM_PAY,
        self::PINELABS      => ProviderPsp::FAVE,
        self::OKSBI         => ProviderPsp::GOOGLE_PAY,
        self::ZOICICI       => ProviderPsp::ZOMATO,
        self::ABFSPAY       => ProviderPsp::BAJAJ_FINSERVE,
        self::NIYOICICI     => ProviderPsp::GO_NIYO,
        self::EQUITAS       => ProviderPsp::EQUITAS_SMALL_FINANCE_BANK_LTD,
        self::NAVIAXIS      => ProviderPsp::NAVI,

        self::SHRIRAMHDFCBANK      => ProviderPsp::SHRIRAMONE,

        // used only for testing
        self::RAZORPAY      => ProviderPsp::RAZORPAY,
    ];

    public static function getBankCode($provider)
    {
        return self::$bankCodes[$provider] ?? null;
    }

    public static function getPsp($vpaHandle, $isAutopay=false)
    {
        if($isAutopay === true)
        {
            return self::$pspForAutopay[$vpaHandle] ?? null;
        }

        return self::$psp[$vpaHandle] ?? null;
    }

    public static function getPspForVpa($vpa, $isAutopay=false)
    {
        // Anything after @ is handle
        $code = substr($vpa, (strpos($vpa, '@') + 1));

        return self::getPsp(strtolower($code), $isAutopay);
    }

    /**
     * Returns the PSP corresponding to the provided app name
     *
     * @param   string $appName
     * @return  string
     */
    public static function getPspForAppName(string $appName)
    {
        $appName = strtolower($appName);

        return self::$appToPsp[$appName] ?? ProviderApp::OTHER;
    }

    public static function validate(string $provider)
    {
        return (self::getBankCode(strtolower($provider)) !== null);
    }

    public static function validateBankCode(string $bankCode): bool
    {
        return (array_search($bankCode, self::$bankCodes) !== false);
    }

    public static function validateOtmProvider(string $bankCode, $mode): bool
    {
        $testModeProviders = [
            self::RAZORPAY,
        ];

        if (($mode === Constants\Mode::TEST) and
            (array_search($bankCode, $testModeProviders) !== false )) {
            return true;
        }

        return (array_search($bankCode, self::$validOtmProviders) !== false);
    }

    public static function validateAutoPayPspProvider(string $vpa, bool $isTestMode = false)
    {
        $psp = self::getPspForVpa($vpa, true);

        $testModeProvider = [
            ProviderPsp::RAZORPAY,
            ProviderPsp::WHATSAPP,
        ];

        if (($isTestMode === true) and
            (array_search($psp, $testModeProvider) !== false))
        {
            return true;
        }

        return (array_search($psp, self::$validAutoPayPspProvider) !== false);
    }

    /**
     * Checks if the VPA Handle of the provided VPA has been whitelisted
     * for the provided Merchant ID (mid) for testing purposes
     *
     * @param string $vpa
     * @param string $mid
     *
     * @return bool
     */
    public static function validateAutopayVpaHandleForPspTesting(string $vpa, string $mid): bool
    {
        $vpaHandle  = substr($vpa, (strpos($vpa, '@') + 1));
        $isValid    = false;
        $key = null;

        switch ($vpaHandle)
        {
            // For GooglePay's Testing
            case self::OKSBI:
                $key            = 'gateway.upi_icici.recurring_' . self::OKSBI . '_test_merchants';
                break;
            case self::OKICICI:
                $key            = 'gateway.upi_icici.recurring_' . self::OKICICI . '_test_merchants';
                break;
            default:
                $isValid = false;
        }

        if (is_null($key) === false)
        {
            $merchantIds        = app('config')->get($key);
            $isValid            = (in_array($mid, $merchantIds, true) === true);
        }

        app('trace')->info(TraceCode::MISC_TRACE_CODE, [
            'vpa_handle'    => $vpaHandle,
            'is_valid'      => $isValid,
            'merchant_id'   => $mid,
        ]);

        return $isValid;
    }
}
