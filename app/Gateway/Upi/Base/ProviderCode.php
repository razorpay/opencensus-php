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
     */
    const AIRTEL            = 'airtel';
    const ALLBANK           = 'allbank';
    const ANDB              = 'andb';
    // Ola
    const AXISGO            = 'axisgo';
    const AXISBANK          = 'axisbank';
    const BARODAMPAY        = 'barodampay';
    const BOI               = 'boi';
    const CENTRALBANK       = 'centralbank';
    const CNRB              = 'cnrb';
    const CSBPAY            = 'csbpay';
    const DBS               = 'dbs';
    const DCB               = 'dcb';
    const DENABANK          = 'denabank';
    const FBL               = 'fbl';
    const FEDERAL           = 'federal';
    const HDFCBANK          = 'hdfcbank';
    const HSBC              = 'hsbc';
    const ICICI             = 'icici';
    const IDBI              = 'idbi';
    const IDFCBANK          = 'idfcbank';
    const IMOBILE           = 'imobile';
    const INDIANBANK        = 'indianbank';
    const INDUS             = 'indus';
    const IOB               = 'iob';
    const JKB               = 'jkb';
    const KAYPAY            = 'kaypay';
    const KBL               = 'kbl';
    const KOTAK             = 'kotak';
    const KVB               = 'kvb';
    const LVB               = 'lvb';
    const MAHAB             = 'mahab';
    const MAHB              = 'mahb';
    const OBC               = 'obc';
    const OKICICI           = 'okicici';
    const OKHDFCBANK        = 'okhdfcbank';
    const OKAXIS            = 'okaxis';
    const PAYTM             = 'paytm';
    const PINGPAY           = 'pingpay';
    const PNB               = 'pnb';
    const PSB               = 'psb';
    const POCKETS           = 'pockets';
    const RBL               = 'rbl';
    const SBI               = 'sbi';
    const SCBL              = 'scbl';
    const SYNDICATE         = 'syndicate';
    const SCB               = 'scb';
    const SIB               = 'sib';
    const TJSB              = 'tjsb';
    const UBI               = 'ubi';
    const UBOI              = 'uboi';
    const UCO               = 'uco';
    const UNIONBANK         = 'unionbank';
    const UNIONBANKOFINDIA  = 'unionbankofindia';
    const UNITED            = 'united';
    const UPI               = 'upi';
    const UTBI              = 'utbi';
    const VIJB              = 'vijb';
    const YBL               = 'ybl';
    const YESBANK           = 'yesbank';

    //Only for test Upi
    const RAZORPAY         = 'razorpay';

    protected static $bankCodes = [
        self::AIRTEL            => IFSC::AIRP,
        self::ALLBANK           => IFSC::ALLA,
        self::ANDB              => IFSC::ANDB,
        self::AXISBANK          => IFSC::UTIB,
        self::AXISGO            => IFSC::UTIB,
        self::BARODAMPAY        => IFSC::BARB,
        self::BOI               => IFSC::BKID,
        self::CENTRALBANK       => IFSC::CBIN,
        self::CNRB              => IFSC::CNRB,
        self::CSBPAY            => IFSC::CSBK,
        self::DBS               => IFSC::DBSS,
        self::DCB               => IFSC::DCBL,
        self::DENABANK          => IFSC::BKDN,
        self::FBL               => IFSC::FDRL,
        self::FEDERAL           => IFSC::FDRL,
        self::HDFCBANK          => IFSC::HDFC,
        self::HSBC              => IFSC::HSBC,
        self::ICICI             => IFSC::ICIC,
        self::IDBI              => IFSC::IBKL,
        self::IDFCBANK          => IFSC::IDFB,
        self::IMOBILE           => IFSC::ICIC,
        self::INDUS             => IFSC::INDB,
        self::INDIANBANK        => IFSC::IDIB,
        self::IOB               => IFSC::IOBA,
        self::JKB               => IFSC::JAKA,
        self::KAYPAY            => IFSC::KKBK,
        self::KBL               => IFSC::KARB,
        self::KOTAK             => IFSC::KKBK,
        self::KVB               => IFSC::KVBL,
        self::LVB               => IFSC::LAVB,
        self::MAHAB             => IFSC::MAHB,
        self::MAHB              => IFSC::MAHB,
        self::OBC               => IFSC::ORBC,
        self::OKICICI           => IFSC::ICIC,
        self::OKHDFCBANK        => IFSC::HDFC,
        self::OKAXIS            => IFSC::UTIB,
        self::PAYTM             => IFSC::PYTM,
        self::PINGPAY           => IFSC::UTIB,
        self::PNB               => IFSC::PUNB,
        self::PSB               => IFSC::PSIB,
        self::POCKETS           => IFSC::ICIC,
        self::RBL               => IFSC::RATN,
        self::SBI               => IFSC::SBIN,
        self::SCB               => IFSC::SCBL,
        self::SCBL              => IFSC::SCBL,
        self::SIB               => IFSC::SIBL,
        self::SYNDICATE         => IFSC::SYNB,
        self::TJSB              => IFSC::TJSB,
        self::UBI               => IFSC::UTBI,
        self::UBOI              => IFSC::UBIN,
        self::UCO               => IFSC::UCBA,
        self::UNIONBANK         => IFSC::UBIN,
        self::UNIONBANKOFINDIA  => IFSC::UBIN,
        self::UNITED            => IFSC::UTBI,
        self::UPI               => 'NPCI',
        self::UTBI              => IFSC::UTBI,
        self::VIJB              => IFSC::VIJB,
        self::YBL               => IFSC::YESB,
        self::YESBANK           => IFSC::YESB,
        self::RAZORPAY          => 'RZPY',
    ];

    public static function getBankCode($provider)
    {
        return self::$bankCodes[$provider] ?? null;
    }

    public static function validate(string $provider)
    {
        return (self::getBankCode($provider) !== null);
    }
}
