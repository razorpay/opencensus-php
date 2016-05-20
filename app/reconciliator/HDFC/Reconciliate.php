<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        // TODO: Figure out how to get the reconciliation type for HDFC.
        return self::PAYMENT;
    }

    
    public function inExcludeList($fileDetails)
    {
        if (strpos($fileDetails['file_name'], 'detailed') !== false)
        {
            return true;
        }

        return false;
    }
}