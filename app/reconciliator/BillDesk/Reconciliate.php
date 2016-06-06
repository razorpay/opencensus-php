<?php

namespace Reconciliator\BillDesk;


use Reconciliator\Base;


class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = 'success';

    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::SUCCESS) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else
        {
            return null;
        }

        return $typeName;
    }


    public function inExcludeList($fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if ((strpos($fileName, self::SUCCESS) === false) and
            (strpos($fileName, self::REFUND) === false))
        {
            return true;
        }

        return false;
    }
}