<?php

namespace Reconciliator\Base;


use Reconciliator\FileProcessor;
use Reconciliator\Orchestrator;


class Reconciliate
{

    /***********************
     * Reconciliation Types
     ***********************/
    const NODAL   = 'nodal';
    const PAYMENT = 'payment';
    const REFUND  = 'refund';

    const VALID_RECONCILIATION_TYPES = [self::NODAL, self::PAYMENT, self::REFUND];


    public function startReconciliation($allFilesContents)
    {
        foreach ($allFilesContents as $fileContents)
        {
            $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);
            $this->setSubReconciliator($reconciliationType);
            $this->subReconciliator->startReconciliation($fileContents);
        }
    }
    

    public function getSheetNames()
    {
        return null;
    }


    protected function getReconciliationType($extraDetails)
    {
        if (isset($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]) === true)
        {
            $fileName = strtolower($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]);
        }
        else
        {
            $fileName = strtolower($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME]);
        }

        // The method is present in child class since different gateways have
        // different sheet names/file names for reconciliation types.
        $reconciliationType = $this->getTypeName($fileName);

        // Ideally, should never come here.
        if (in_array($reconciliationType, self::VALID_RECONCILIATION_TYPES) === false)
        {
            // TODO: Move this to validator?
            // TODO: Throw an exception about wrong reconciliation type.
        }

        return $reconciliationType;
    }


    protected function setSubReconciliator($reconciliationType)
    {
        $subReconciliatorClassName = $this->getSubReconciliatorClassName($reconciliationType);
        $this->subReconciliator = new $subReconciliatorClassName;
    }


    protected function getSubReconciliatorClassName($reconciliationType)
    {
        $parentNamespace = $this->getParentNamespace();
        $subReconciliatorClassName = $parentNamespace . '\\'
                                    . 'SubReconciliator' . '\\'
                                    . ucfirst($reconciliationType)
                                    . 'Reconciliate';

        return $subReconciliatorClassName;
    }


    protected function getParentNamespace()
    {
        return join('\\', explode('\\', get_called_class(), -1));
    }
}