<?php

namespace RZP\Gateway\Upi\Base;

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
     */
    const AIRTEL             = 'airtel';
    const AIRTELPAYMENTSBANK = 'airtelpaymentsbank';
    const ALBK               = 'albk';
    const ALLAHABADBANK      = 'allahabadbank';
    const ALLBANK            = 'allbank';
    const ANDB               = 'andb';
    const APB                = 'apb';
    const APL                = 'apl';
    const AXIS               = 'axis';
    const AXISBANK           = 'axisbank';
    const AXISGO             = 'axisgo';
    const BANDHAN            = 'bandhan';
    const BARODAMPAY         = 'barodampay';
    const BIRLA              = 'birla';
    const BOI                = 'boi';
    const CBIN               = 'cbin';
    const CBOI               = 'cboi';
    const CENTRALBANK        = 'centralbank';
    const CITIBANK           = 'citibank';
    const CMSIDFC            = 'cmsidfc';
    const CNRB               = 'cnrb';
    const CSBCASH            = 'csbcash';
    const CSBPAY             = 'csbpay';
    const CUB                = 'cub';
    const DBS                = 'dbs';
    const DCB                = 'dcb';
    const DENABANK           = 'denabank';
    const EAZYPAY            = 'eazypay';
    const EQUITAS            = 'equitas';
    const EZEEPAY            = 'ezeepay';
    const FBL                = 'fbl';
    const FEDERAL            = 'federal';
    const FINOBANK           = 'finobank';
    const FREECHARGE         = 'freecharge';
    const HDFCBANK           = 'hdfcbank';
    const HDFCBANKRZP        = 'hdfcbankrzp';
    const HSBC               = 'hsbc';
    const ICICI              = 'icici';
    const ICICIPAY           = 'icicipay';
    const IDBI               = 'idbi';
    const IDBIBANK           = 'idbibank';
    const IDFC               = 'idfc';
    const IDFCBANK           = 'idfcbank';
    const IDFCNETC           = 'idfcnetc';
    const IMOBILE            = 'imobile';
    const INDBANK            = 'indbank';
    const INDIANBANK         = 'indianbank';
    const INDIANBK           = 'indianbk';
    const INDUS              = 'indus';
    const IOB                = 'iob';
    const JKB                = 'jkb';
    const JSB                = 'jsb';
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
    const OBC                = 'obc';
    const OKAXIS             = 'okaxis';
    const OKHDFCBANK         = 'okhdfcbank';
    const OKICICI            = 'okicici';
    const OKSBI              = 'oksbi';
    const PAYTM              = 'paytm';
    const PAYZAPP            = 'payzapp';
    const PINGPAY            = 'pingpay';
    const PNB                = 'pnb';
    const PNBPAY             = 'pnbpay';
    const POCKETS            = 'pockets';
    const PSB                = 'psb';
    const PURZ               = 'purz';
    const RAJGOVHDFCBANK     = 'rajgovhdfcbank';
    const RBL                = 'rbl';
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
    const YBL                = 'ybl';
    const YESBANK            = 'yesbank';
    const YESBANKLTD         = 'yesbankltd';

    //Only for test Upi
    const RAZORPAY         = 'razorpay';

    /**
     * PSP handle to bank code mapping
     * In some cases, we don't have the proper
     * bank details, in those cases we are
     * mapping the psp to NPCI code.
     */
    protected static $bankCodes = [
        self::AIRTEL             => IFSC::AIRP,
        self::AIRTELPAYMENTSBANK => IFSC::AIRP,
        self::ALBK               => IFSC::ALLA,
        self::ALLAHABADBANK      => IFSC::ALLA,
        self::ALLBANK            => IFSC::ALLA,
        self::ANDB               => IFSC::ANDB,
        self::APB                => IFSC::AIRP,
        self::APL                => IFSC::AIRP,
        self::AXIS               => IFSC::UTIB,
        self::AXISBANK           => IFSC::UTIB,
        self::AXISGO             => IFSC::UTIB,
        self::BANDHAN            => IFSC::BDBL,
        self::BARODAMPAY         => IFSC::BARB,
        self::BIRLA              => IFSC::ABPB,
        self::BOI                => IFSC::BKID,
        self::CBIN               => IFSC::CBIN,
        self::CBOI               => IFSC::CBIN,
        self::CENTRALBANK        => IFSC::CBIN,
        self::CITIBANK           => IFSC::CITI,
        self::CMSIDFC            => IFSC::IDFB,
        self::CNRB               => IFSC::CNRB,
        self::CSBCASH            => IFSC::CSBK,
        self::CSBPAY             => IFSC::CSBK,
        self::CUB                => IFSC::CIUB,
        self::DBS                => IFSC::DBSS,
        self::DCB                => IFSC::DCBL,
        self::DENABANK           => IFSC::BKDN,
        self::EAZYPAY            => IFSC::ICIC,
        self::EQUITAS            => IFSC::ESFB,
        self::EZEEPAY            => 'NPCI',
        self::FBL                => IFSC::FDRL,
        self::FEDERAL            => IFSC::FDRL,
        self::FINOBANK           => IFSC::FINO,
        self::FREECHARGE         => IFSC::UTIB,
        self::HDFCBANK           => IFSC::HDFC,
        self::HDFCBANKRZP        => IFSC::HDFC,
        self::HSBC               => IFSC::HSBC,
        self::ICICI              => IFSC::ICIC,
        self::ICICIPAY           => IFSC::ICIC,
        self::IDBI               => IFSC::IBKL,
        self::IDBIBANK           => IFSC::IBKL,
        self::IDFC               => IFSC::IDFB,
        self::IDFCBANK           => IFSC::IDFB,
        self::IDFCNETC           => IFSC::IDFB,
        self::IMOBILE            => IFSC::ICIC,
        self::INDBANK            => IFSC::IDIB,
        self::INDIANBANK         => IFSC::IDIB,
        self::INDIANBK           => IFSC::IDIB,
        self::INDUS              => IFSC::INDB,
        self::IOB                => IFSC::IOBA,
        self::JSB                => IFSC::JSBP,
        self::JKB                => IFSC::JAKA,
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
        self::OBC                => IFSC::ORBC,
        self::OKAXIS             => IFSC::UTIB,
        self::OKICICI            => IFSC::ICIC,
        self::OKHDFCBANK         => IFSC::HDFC,
        self::OKSBI              => IFSC::SBIN,
        self::PAYTM              => IFSC::PYTM,
        self::PAYZAPP            => IFSC::HDFC,
        self::PINGPAY            => IFSC::UTIB,
        self::PNB                => IFSC::PUNB,
        self::PNBPAY             => IFSC::PUNB,
        self::PSB                => IFSC::PSIB,
        self::POCKETS            => IFSC::ICIC,
        self::PURZ               => 'NPCI',
        self::RAJGOVHDFCBANK     => IFSC::HDFC,
        self::RBL                => IFSC::RATN,
        self::S2B                => 'NPCI',
        self::SBI                => IFSC::SBIN,
        self::SC                 => IFSC::SCBL,
        self::SCB                => IFSC::SCBL,
        self::SCBL               => IFSC::SCBL,
        self::SCMOBILE           => IFSC::SCBL,
        self::SIB                => IFSC::SIBL,
        self::SRCB               => 'NPCI',
        self::SYND               => IFSC::SYNB,
        self::SYNDBANK           => IFSC::SYNB,
        self::SYNDICATE          => IFSC::SYNB,
        self::TJSB               => IFSC::TJSB,
        self::UBI                => IFSC::UTBI,
        self::UBOI               => IFSC::UBIN,
        self::UCO                => IFSC::UCBA,
        self::UNIONBANK          => IFSC::UBIN,
        self::UNIONBANKOFINDIA   => IFSC::UBIN,
        self::UNITED             => IFSC::UTBI,
        self::UPI                => 'NPCI',
        self::UTBI               => IFSC::UTBI,
        self::VIJAYABANK         => IFSC::VIJB,
        self::VIJB               => IFSC::VIJB,
        self::VJB                => IFSC::VIJB,
        self::YBL                => IFSC::YESB,
        self::YESBANK            => IFSC::YESB,
        self::YESBANKLTD         => IFSC::YESB,
        self::RAZORPAY           => 'RZPY',
    ];

    public static function getBankCode($provider)
    {
        return self::$bankCodes[$provider] ?? null;
    }

    public static function validate(string $provider)
    {
        return (self::getBankCode($provider) !== null);
    }

    public static function validateBankCode(string $bankCode): bool
    {
        return (array_search($bankCode, self::$bankCodes) !== false);
    }
}
