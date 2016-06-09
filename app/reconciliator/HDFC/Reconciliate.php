<?php

namespace Reconciliator\HDFC;

use Reconciliator\Base;
use App;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
    
    public function inExcludeList($fileDetails)
    {
        if (strpos($fileDetails['file_name'], 'detailed') !== false)
        {
            return true;
        }

        return false;
    }

    public function getReconPassword($fileDetails)
    {
        $terminalId = explode('-', $fileDetails['file_name'])[0];

        $terminalRepo = App::getFacadeRoot()['repo']->terminal;

        $reconPassword = $terminalRepo->getById($terminalId)->getReconPassword();

        return $reconPassword;
    }
}