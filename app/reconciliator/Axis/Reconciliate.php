<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of base class (Base\Reconciliate).
    const SALE = 'sale';
    const acceptedSheetNames = ['b', 'a', 'Refund', 'Sale'];
    

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
        return self::acceptedSheetNames;
    }
}