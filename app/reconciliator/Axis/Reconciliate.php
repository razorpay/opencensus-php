<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;
use Reconciliator\Orchestrator;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Implement interface and use trait instead of abstract class (Base\Reconciliate).


    /*********************
     * Instance variables
     *********************/
    protected $subReconciliator;


    public function startReconciliation($allFilesContents)
    {
        // TODO: While reading the file contents, exclude file_details and sheet_name params.

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
        else
        {
            // TODO: Throw exception for not being able to find which reconciliation type is it.
        }

        return $typeName;
    }


    public function getSheetNames()
    {
        // TODO: Return all sheet names possible for this gateway. If not present, function will ignore.
        return ['b', 'a', 'Refund'];
    }
}