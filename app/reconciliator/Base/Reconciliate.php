<?php

namespace Reconciliator\Base;


use Reconciliator\FileProcessor;


class Reconciliate
{

    /***********************
     * Reconciliation Types
     ***********************/
    const NODAL   = 'nodal';
    const PAYMENT = 'payment';
    const REFUND  = 'refund';


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

        return $reconciliationType;
    }


    protected function setSubReconciliator($reconciliationType)
    {
        switch ($reconciliationType)
        {
            case self::REFUND:
                $subReconciliatorClassName = $this->getSubReconciliatorClassName($reconciliationType);
                $this->subReconciliator = new $subReconciliatorClassName;
                break;
            case self::PAYMENT:
                break;
            case self::NODAL:
                break;
            default:
                // TODO: Throw exception for not being any of the recognized reconciliation types.
        }
    }


    protected function getSubReconciliatorClassName($reconciliationType)
    {
        $parentNamespace = $this->getParentNamespace();
        $subReconciliatorClassName = $parentNamespace . '\\'
                                    . 'SubReconciliator' . '\\'
                                    . ucfirst($reconciliationType);

        return $subReconciliatorClassName;
    }


    protected function getParentNamespace()
    {
        return join('\\', explode('\\', get_called_class(), -1));
    }
}