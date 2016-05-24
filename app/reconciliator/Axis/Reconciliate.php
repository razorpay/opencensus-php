<?php

namespace Reconciliator\Axis;


// Unused imports here could be used by parent classes.
use Reconciliator\Base;
use Reconciliator\Messenger;
use Trace\TraceCode;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of base class (Base\Reconciliate).
    const SALE = 'sale';
    const ACCEPTED_SHEET_NAMES = ['b', 'a', 'Refund', 'Sale'];

    protected $messenger;

    public function __construct()
    {
        $this->messenger = new Messenger();
    }

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