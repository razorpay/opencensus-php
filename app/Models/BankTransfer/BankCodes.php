<?php

namespace RZP\Models\BankTransfer;

use App;

class BankCodes
{
    //
    // In order to facilitate a refund, we need a valid IFSC belonging
    // to the source bank. These are the ones we're using for now.
    //
    const IFSC_ANDB = 'ANDB0001334';
    const IFSC_BARB = 'BARB0MAINOF';
    const IFSC_CNRB = 'CNRB0000002';
    const IFSC_FDRL = 'FDRL0000121';
    const IFSC_GSCB = 'GSCB0000001';
    const IFSC_HDFC = 'HDFC0000001';
    const IFSC_IBKL = 'IBKL0000001';
    const IFSC_ICIC = 'ICIC0002445';
    const IFSC_IDFB = 'IDFB0010201';
    const IFSC_IDIB = 'IDIB0NEFTMU';
    const IFSC_KARB = 'KARB0000513';
    const IFSC_ORBC = 'ORBC0100001';
    const IFSC_PMCB = 'PMCB0000002';
    const IFSC_PUNB = 'PUNB0000100';
    const IFSC_SBIN = 'SBIN0010411';
    const IFSC_SCBL = 'SCBL0036001';
    const IFSC_SYNB = 'SYNB0000005';
    const IFSC_UCBA = 'UCBA0000002';
    const IFSC_UTIB = 'UTIB0001918';
    const IFSC_VIJB = 'VIJB0001398';
    const IFSC_YESB = 'YESB0000001';

    //
    // Kotak sends us 3 digit bank codes for IMPS transactions.
    // These are the ones we've collected till date.
    //
    const CODE_TO_IFSC_MAPPING = [
        'AND'   => self::IFSC_ANDB,
        'AXB'   => self::IFSC_UTIB,
        'BOB'   => self::IFSC_BARB,
        'CNB'   => self::IFSC_CNRB,
        'FBL'   => self::IFSC_FDRL,
        'GSC'   => self::IFSC_GSCB,
        'HDB'   => self::IFSC_HDFC,
        'ICI'   => self::IFSC_ICIC,
        'IDB'   => self::IFSC_IBKL,
        'IDF'   => self::IFSC_IDFB,
        'INB'   => self::IFSC_IDIB,
        'KTB'   => self::IFSC_KARB,
        'OBC'   => self::IFSC_ORBC,
        'PMC'   => self::IFSC_PMCB,
        'PNB'   => self::IFSC_PUNB,
        'SBI'   => self::IFSC_SBIN,
        'SCB'   => self::IFSC_SCBL,
        'SYB'   => self::IFSC_SYNB,
        'UCO'   => self::IFSC_UCBA,
        'VJBN1' => self::IFSC_VIJB,
        'VJBN2' => self::IFSC_VIJB,
        'VJBN3' => self::IFSC_VIJB,
        'VJBN4' => self::IFSC_VIJB,
        'YBL'   => self::IFSC_YESB,
    ];

    public static function getIfscForBankCode(string $bankCode)
    {
        $ifsc = self::CODE_TO_IFSC_MAPPING[$bankCode] ?? null;

        $ifsc = self::hackForTesting($bankCode, $ifsc);

        return $ifsc;
    }

    public static function hasIfscMapping(string $bankCode)
    {
        return (self::getIfscForBankCode($bankCode) !== null);
    }

    public static function hackForTesting(string $bankCode, string $ifsc = null)
    {
        $app = App::getFacadeRoot();

        // Bank transfers pass or fail depending on whether we have a valid IFSC
        // for the payer in CODE_TO_IFSC_MAPPING. This makes it hard to test.
        // Here, we mock a valid IFSC, for the last step of the test.
        if (($app['env'] === 'testing') and
            ($bankCode === 'XYZ') and
            ($ifsc === null) and
            ($app['api.route']->getCurrentRouteName() === 'bank_transfer_refund_retry'))
        {
            $ifsc = 'RAZR0000001';
        }

        return $ifsc;
    }
}
