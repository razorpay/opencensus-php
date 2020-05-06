<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use App;
use RZP\Reconciliator\FileProcessor;
use RZP\Constants\Entity;

class Reconciliate extends Base\Reconciliate
{
    const CORP_FILE_REGEX = "/1413-(0[1-9]|[12][0-9]|3[01])(0[1-9]|1[0-2])20[0-9]{2}/";

    const CORP_FILE_BOTTOM_LINES_SKIP = 3;

    const BHARAT_QR_TYPE = 'BHARAT QR';

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

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
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
        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        if (preg_match(self::CORP_FILE_REGEX, $fileName) === 1)
        {
            return $this->getReconPasswordForCorpFile();
        }

        $gateway = Entity::HDFC;
        $terminalId = explode('-', $fileDetails['file_name'])[0];

        if ($this->isCybersource($fileDetails) === true)
        {
            $gateway = Entity::CYBERSOURCE;
            $terminalId = 'hdfc_' . $terminalId;
        }

        $terminalRepo = $this->repo->terminal;

        $gatewayTerminal = $terminalRepo->getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull($terminalId, $gateway);

        // Example case: Zips of all recon files in another zip file.
        // This zip file name does not contain terminal name.
        if ($gatewayTerminal === null)
        {
            return null;
        }

        $reconPassword = $gatewayTerminal->getGatewayReconPassword();

        return $reconPassword;
    }

    public function getReconPasswordForCorpFile()
    {
        return 'G27471';
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        $linesFromTop = $linesFromBottom = 0;

        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        if (preg_match(self::CORP_FILE_REGEX, $fileName) === 1)
        {
            $linesFromBottom = self::CORP_FILE_BOTTOM_LINES_SKIP;
        }

        return [
            FileProcessor::LINES_FROM_TOP    => $linesFromTop,
            FileProcessor::LINES_FROM_BOTTOM => $linesFromBottom
        ];
    }

    protected function isCybersource(array $fileDetails)
    {
        $terminalId = explode('-', $fileDetails[FileProcessor::FILE_NAME])[0];

        $isCybersource = self::isCybersourceTerminalId($terminalId);

        return $isCybersource;
    }

    public static function isCybersourceTerminalId(string $terminalId)
    {
        // cybersource gateway terminal ID starts with 8

        return ((substr($terminalId, 0, 1) === '8') and (strlen($terminalId) == 8));
    }
}
