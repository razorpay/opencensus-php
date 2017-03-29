<?php

namespace RZP\Reconciliator\NetbankingIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Currently Icici shares only payment report
     */
    const SUCCESS = [
        'razorpayreports' => self::PAYMENT
    ];

    const EXCLUDE_FILE_STRING = 'success';

    /**
     * Currently Icici shares only payment report
     */
    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER
    ];

    const PAYMENT_COLUMN_HEADER = [
        'ITC',
        'PRN',
        'BID',
        'Amount',
        'Date'
    ];

    /**
     * Determines the type of reconciliation
     * based on the name of the file.
     * It can either be refund, payment or combined.
     * For now, only payment.
     * we convert file name to lower case before sending
     *
     * @param string $fileName
     * @return null | string
     */
    public function getTypeName($fileName)
    {
        $typeName = null;

        foreach (self::SUCCESS as $name => $type)
        {
            if (strpos($fileName, $name) !== false)
            {
                $typeName = $type;
            }
        }

        return $typeName;
    }

    public function getColumnHeadersForType($type)
    {
        return self::TYPE_TO_COLUMN_HEADER_MAP[$type];
    }

    public function inExcludeList(array $fileDetails)
    {
        if (strpos($fileDetails['file_name'], self::EXCLUDE_FILE_STRING) === false)
        {
            return false;
        }

        return true;
    }
}
