<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;
use Reconciliator\Messenger;

class Reconciliate extends Base\Reconciliate
{
    const SALE = 'sale';
    const ACCEPTED_SHEET_NAMES = ['Refund', 'Sale'];

    protected $messenger;

    public function __construct()
    {
        $this->messenger = new Messenger();
    }


    protected function getTypeName($fileName)
    {
        if (strpos(self::REFUND, strtolower($fileName)) !== false)
        {
            $typeName = self::REFUND;
        }
        else if (strpos(self::SALE, strtolower($fileName)) !== false)
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