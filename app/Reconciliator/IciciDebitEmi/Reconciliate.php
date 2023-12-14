<?php

namespace RZP\Reconciliator\IciciDebitEmi;

use Illuminate\Support\Str;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\FileProcessor;
use RZP\Trace\TraceCode;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = ['CAN_RAZORPAY','can_razorpay'];
    const PAYMENT_RECON_FILE_NAME   = ['PROCESSED_RAZORPAY','processed_razorpay'];
    const ACCEPTED_SHEET_NAMES = [
        'PROCESSED_RAZORPAY',
        'CAN_RAZORPAY',
    ];

    protected function getTypeName($sheetName)
    {
        $type = null;

        if (Str::contains(strtolower($sheetName), self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }

        if (Str::contains(strtolower($sheetName), self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }

        return $type;
    }

    /**
     * The list of sheet names in the excel file which should be
     * used to run reconciliation.
     * Some excel files have sheets that should not be considered for
     * reconciliation.
     *
     * @param array $fileDetails
     * @return array
     */
    public function getSheetNames(array $fileDetails = [])
    {
        return self::ACCEPTED_SHEET_NAMES;
    }

}
