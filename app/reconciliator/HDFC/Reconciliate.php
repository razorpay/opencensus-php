<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of abstract class (Base\Reconciliate).
    const SALE = 'sale';
    const acceptedSheetNames = [];

    /*********************
     * Instance variables
     *********************/
    protected $subReconciliator;


    public function startReconciliation($allFilesContents)
    {
        foreach ($allFilesContents as $fileContents)
        {
            $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);
            $this->setSubReconciliator($reconciliationType);
            $this->subReconciliator->startReconciliation($fileContents);
        }
    }


    protected function getTypeName($fileName)
    {
        // TODO: Figure out how to get the reconciliation type for HDFC.
        return self::PAYMENT;
    }


    public function getSheetNames()
    {
        return self::acceptedSheetNames;
    }
}