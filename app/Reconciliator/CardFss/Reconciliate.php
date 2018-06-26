<?php

namespace RZP\Reconciliator\CardFss;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const ACCEPTED_SHEET_NAMES = [
        'payment', 'refund',
    ];

    public function getSheetNames(array $fileDetails = [])
    {
        return self::ACCEPTED_SHEET_NAMES;
    }

    /**
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        $fileName = strtolower($fileName);

        if (str_contains($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else if (str_contains($fileName, self::PAYMENT) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else
        {
            return null;
        }

        return $typeName;
    }
}
