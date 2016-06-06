<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;


class Reconciliate extends Base\Reconciliate
{
    const SALE = 'sale';
    const ACCEPTED_SHEET_NAMES = ['Refund', 'Sale'];


    protected function getTypeName($fileName)
    {
        if (strpos(self::REFUND, $fileName) !== false)
        {
            $typeName = self::REFUND;
        }
        else if (strpos(self::SALE, $fileName) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else
        {
            return null;
        }

        return $typeName;
    }


    public function getSheetNames()
    {
        return self::ACCEPTED_SHEET_NAMES;
    }
}