<?php

namespace RZP\Models\BankTransfer;

use App;

use RZP\Models\Bank\IFSC;

class BankCodes
{
    //
    // In order to facilitate a refund, we need a valid IFSC belonging
    // to the source bank. These are the ones we're using for now.
    //
    const IFSC_ANDB = 'ANDB0001334';
    const IFSC_BARB = 'BARB0MAINOF';
    const IFSC_CIUB = 'CIUB0000032';
    const IFSC_CNRB = 'CNRB0000002';
    const IFSC_COSB = 'COSB0000001';
    const IFSC_DCBL = 'DCBL0000001';
    const IFSC_ESFB = 'ESFB0000002';
    const IFSC_FDRL = 'FDRL0000121';
    const IFSC_GSCB = 'GSCB0000001';
    const IFSC_HDFC = 'HDFC0000001';
    const IFSC_IBKL = 'IBKL0000001';
    const IFSC_ICIC = 'ICIC0002445';
    const IFSC_IDFB = 'IDFB0010201';
    const IFSC_IDIB = 'IDIB0NEFTMU';
    const IFSC_INDB = 'INDB0000006';
    const IFSC_IOBA = 'IOBA0001548';
    const IFSC_KARB = 'KARB0000513';
    const IFSC_KVBL = 'KVBL0001101';
    const IFSC_KVGB = 'KVGB0000001';
    const IFSC_MAHB = 'MAHB0001150';
    const IFSC_ORBC = 'ORBC0100001';
    const IFSC_PMCB = 'PMCB0000002';
    const IFSC_PUNB = 'PUNB0000100';
    const IFSC_RATN = 'RATN0000999';
    const IFSC_SBIN = 'SBIN0010411';
    const IFSC_SCBL = 'SCBL0036001';
    const IFSC_SIBL = 'SIBL0000084';
    const IFSC_SRCB = 'SRCB0000024';
    const IFSC_SYNB = 'SYNB0000005';
    const IFSC_TMBL = 'TMBL0000001';
    const IFSC_UBIN = 'UBIN0538167';
    const IFSC_UCBA = 'UCBA0000002';
    const IFSC_UTBI = 'UTBI0XCNA10';
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
        'BOM'   => self::IFSC_MAHB,
        'CNB'   => self::IFSC_CNRB,
        'COB'   => self::IFSC_COSB,
        'CUB'   => self::IFSC_CIUB,
        'DCB'   => self::IFSC_DCBL,
        'ESF'   => self::IFSC_ESFB,
        'FBL'   => self::IFSC_FDRL,
        'GSC'   => self::IFSC_GSCB,
        'HDB'   => self::IFSC_HDFC,
        'ICI'   => self::IFSC_ICIC,
        'IDB'   => self::IFSC_IBKL,
        'IDF'   => self::IFSC_IDFB,
        'IIA'   => self::IFSC_INDB,
        'INB'   => self::IFSC_IDIB,
        'IOB'   => self::IFSC_IOBA,
        'IOBN1' => self::IFSC_IOBA,
        'IOBN2' => self::IFSC_IOBA,
        'KTB'   => self::IFSC_KARB,
        'KVB'   => self::IFSC_KVBL,
        'KVBN2' => self::IFSC_KVBL,
        'KVBN3' => self::IFSC_KVBL,
        'KVBN4' => self::IFSC_KVBL,
        'KVG'   => self::IFSC_KVGB,
        'OBC'   => self::IFSC_ORBC,
        'PMC'   => self::IFSC_PMCB,
        'PNB'   => self::IFSC_PUNB,
        'RNB'   => self::IFSC_RATN,
        'SBI'   => self::IFSC_SBIN,
        'SCB'   => self::IFSC_SCBL,
        'SIB'   => self::IFSC_SIBL,
        'SRC'   => self::IFSC_SRCB,
        'SYB'   => self::IFSC_SYNB,
        'TMB'   => self::IFSC_TMBL,
        'UBI'   => self::IFSC_UTBI,
        'UCO'   => self::IFSC_UCBA,
        'UOB'   => self::IFSC_UBIN,
        'UOBN1' => self::IFSC_UBIN,
        'UOBN2' => self::IFSC_UBIN,
        'UOBN3' => self::IFSC_UBIN,
        'UOBN4' => self::IFSC_UBIN,
        'UOBN5' => self::IFSC_UBIN,
        'UOBN6' => self::IFSC_UBIN,
        'VJB'   => self::IFSC_VIJB,
        'VJBN1' => self::IFSC_VIJB,
        'VJBN2' => self::IFSC_VIJB,
        'VJBN3' => self::IFSC_VIJB,
        'VJBN4' => self::IFSC_VIJB,
        'YBL'   => self::IFSC_YESB,
    ];

    const STRIP_LEADING_ZEROES_BANKS_IMPS = [
        'CNB'
    ];

    const STRIP_LEADING_ZEROES_BANKS_NEFT = [
        IFSC::CNRB,
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

    /**
     * Some banks send account number is an altered form, eg. there may be leading
     * zeroes. These need to be removed before creating the bank account entity.
     *
     * Identify the bank requires us to check Payer IFSC. If it's IMPS, it's not
     * actually an IFSC, it's one of the bank codes given above. Check both.
     *
     * @param  string $account
     * @param  Entity $bankTransfer
     * @return string $account
     */
    public static function modifyPayerAccountIfNeeded(string $account, Entity $bankTransfer)
    {
        $ifsc = $bankTransfer->getPayerIfsc();

        $haystack = self::STRIP_LEADING_ZEROES_BANKS_NEFT;

        $needle = substr($ifsc, 0, 4);

        if ($bankTransfer->getMode() === Mode::IMPS)
        {
            $haystack = self::STRIP_LEADING_ZEROES_BANKS_IMPS;

            $needle = substr($ifsc, 0, -10);
        }

        if (in_array($needle, $haystack, true) === true)
        {
            $account = ltrim($account, '0');
        }

        return $account;
    }
}
