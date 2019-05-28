<?php

namespace RZP\Reconciliator\CardlessEmiFlexMoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUNDS = 'refunds';

    const PAYMENT_COLUMN_HEADERS = [
        'PG Transaction ID',
        'Flexpay Transaction ID',
        'Transaction Amount',
        'Transaction Date',
    ];

    const REFUND_COLUMN_HEADERS = [
        'PG Refund ID',
        'Flexpay Transaction ID',
        'Transaction Amount',
        'Refund Amount',
        'Refund Date',
    ];

    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::REFUNDS) !== false)
        {
            $typeName = self::REFUND;
        }
        else
        {
            $typeName = self::PAYMENT;
        }

        return $typeName;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getColumnHeadersForType($type)
    {
        $headers = [];

        if ($type === self::PAYMENT)
        {
            $headers = self::PAYMENT_COLUMN_HEADERS;
        }
        else if ($type === self::REFUND)
        {
            $headers = self::REFUND_COLUMN_HEADERS;
        }

        return $headers;
    }
}
