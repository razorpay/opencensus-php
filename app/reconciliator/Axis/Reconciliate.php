<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of abstract class (Base\Reconciliate).
    const SALE = 'sale';
    const acceptedSheetNames = ['b', 'a', 'Refund', 'Sale'];

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
            // TODO: Throw exception for not being able to find which reconciliation type is it.
        }

        return $typeName;
    }


    public function getSheetNames()
    {
        return self::acceptedSheetNames;
    }
}