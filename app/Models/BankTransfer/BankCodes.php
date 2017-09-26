<?php

namespace RZP\Models\BankTransfer;

class BankCodes
{
    //
    // Kotak sends us 3 digit bank codes for IMPS transactions.
    // These are the ones we've collected till date.
    //
    const AXB = 'AXB';
    const HDB = 'HDB';
    const ICI = 'ICI';
    const IDF = 'IDF';
    const INB = 'INB';
    const PMC = 'PMC';
    const PNB = 'PNB';
    const SCB = 'SCB';
    const SYB = 'SYB';
    const SBI = 'SBI';
    const YBL = 'YBL';

    //
    // In order to faciliate a refund, we need a valid IFSC belonging
    // to the source bank. These are the ones we're using for now.
    //
    const IFSC_UTIB = 'UTIB0001918';
    const IFSC_HDFC = 'HDFC0000001';
    const IFSC_ICIC = 'ICIC0002445';
    const IFSC_IDFB = 'IDFB0010201';
    const IFSC_IDIB = 'IDIB0NEFTMU';
    const IFSC_PMCB = 'PMCB0000002';
    const IFSC_PUNB = 'PUNB0000100';
    const IFSC_SCBL = 'SCBL0036001';
    const IFSC_SYNB = 'SYNB0000005';
    const IFSC_SBIN = 'SBIN0010411';
    const IFSC_YESB = 'YESB0000001';

    const CODE_TO_IFSC_MAPPING = [
        self::AXB => self::IFSC_UTIB,
        self::HDB => self::IFSC_HDFC,
        self::ICI => self::IFSC_ICIC,
        self::IDF => self::IFSC_IDFB,
        self::INB => self::IFSC_IDIB,
        self::PMC => self::IFSC_PMCB,
        self::PNB => self::IFSC_PUNB,
        self::SCB => self::IFSC_SCBL,
        self::SYB => self::IFSC_SYNB,
        self::SBI => self::IFSC_SBIN,
        self::YBL => self::IFSC_YESB,
    ];

    public static function getIfscForBankCode(string $bankCode)
    {
        return self::CODE_TO_IFSC_MAPPING[$bankCode] ?? null;
    }

    public static function hasIfscMapping(string $bankCode)
    {
        return (isset(self::CODE_TO_IFSC_MAPPING[$bankCode]) === true);
    }
}
