<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use App;
use RZP\Reconciliator\FileProcessor;
use RZP\Constants\Entity;

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

    public function inExcludeList(array $fileDetails)
    {
        if (strpos($fileDetails[FileProcessor::FILE_NAME], 'detailed') !== false)
        {
            return true;
        }

        if (strpos($fileDetails[FileProcessor::EXTENSION], 'txt') !== false)
        {
            return true;
        }

        return false;
    }

    public function getReconPassword($fileDetails)
    {
        $terminalId = explode('-', $fileDetails['file_name'])[0];

        $terminalRepo = App::getFacadeRoot()['repo']->terminal;

        $gatewayTerminal = $terminalRepo->getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull($terminalId, Entity::HDFC);

        // Example case: Zips of all recon files in another zip file.
        // This zip file name does not contain terminal name.
        if ($gatewayTerminal === null)
        {
            return null;
        }

        $reconPassword = $gatewayTerminal->getGatewayReconPassword();

        return $reconPassword;
    }
}