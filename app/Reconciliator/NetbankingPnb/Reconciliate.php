<?php

namespace RZP\Reconciliator\NetbankingPnb;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_COLUMN_HEADER = [
        'bank_reference',
        'amount',
        'date',
        'payment_id',
        'account_number',
    ];

    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER
    ];

    /**
     * There is single MIS file for all payments
     *
     * @param  string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::TYPE_TO_COLUMN_HEADER_MAP[$type];
    }

    public function getDelimiter()
    {
        return '^';
    }
}
