<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of abstract class (Base\Reconciliate).

    protected function getTypeName($fileName)
    {
        // TODO: Figure out how to get the reconciliation type for HDFC.
        return self::PAYMENT;
    }
}