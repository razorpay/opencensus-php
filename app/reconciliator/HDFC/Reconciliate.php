<?php

namespace Reconciliator\HDFC;


use Reconciliator\FileProcessor;
use Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /***********************
     * Reconciliation Types
     ***********************/
    const NODAL   = 'nodal';
    const PAYMENT = 'payment';
    const REFUND  = 'refund';


    public function startReconciliation($allFilesContents)
    {
        // TODO: While getting the file contents, exclude file_details and sheet_name params.
        var_dump(json_encode($allFilesContents));

        foreach ($allFilesContents as $fileContents)
        {
            $reconciliationType = $this->getReconciliationType($fileContents[FileProcessor::FILE_DETAILS]);
        }
    }


    protected function getReconciliationType($fileDetails)
    {
        // TODO: Figure out the reconciliation type.
        return null;
    }
}