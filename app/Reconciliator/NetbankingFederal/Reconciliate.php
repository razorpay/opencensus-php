<?php

namespace RZP\Reconciliator\NetbankingFederal;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = [
        'mis_report_razorpay' => self::PAYMENT
    ];

    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER
    ];

    const PAYMENT_COLUMN_HEADER = [
        'PRN',
        'MID',
        'ITC',
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
}
