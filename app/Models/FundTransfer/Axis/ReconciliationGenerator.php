<?php

namespace RZP\Models\FundTransfer\Axis;

use phpseclib\Crypt\AES;

use Config;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Axis\Reconciliation\Status;

class ReconciliationGenerator
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Axis_Settlement';

    protected static $fileToWriteName = 'Axis_Settlement_Reconciliation';

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
        {
            return [];
        }

        $data = $this->getDecryptedFile($setlFile);

        $reconData = [];

        foreach ($data as $row)
        {
            $reconData[] = $this->generateReconciliationFields($row, $input);
        }

        $filename = 'NRPSS_' . str_random(10);

        $dir = $this->getStorageDir();

        $file = $this->createExcelFile($reconData, $filename, $dir);

        return $file;
    }

    protected function getDecryptedFile($file)
    {
        $aes = new AES(AES::MODE_CBC);

        $aes->setKey(Config::get('nodal.axis.secret'));

        $aes->setIV(Config::get('nodal.axis.iv'));

        $content = base64_decode(file_get_contents($file));

        $decryptedData = $aes->decrypt($content);

        $dirPath = pathinfo($file, PATHINFO_DIRNAME);

        $decryptedFilePath = $dirPath . '/Decrypted.xlsx';

        file_put_contents($decryptedFilePath, $decryptedData);

        $data = $this->parseExcelSheets($decryptedFilePath);

        return $data;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    public static function getResponseHeading()
    {
        return Headings::getResponseFileHeadings();
    }

    protected function generateReconciliationFields($row, array $params)
    {
        $generateFailedReconciliations = (isset($params['failed_recons'])) ?
            ((bool) $params['failed_recons']) : false;

        $data = [
            Headings::FILE_LEVEL_REFERENCE => $row[Headings::REFERENCE_NUMBER],
            Headings::BENEFICIARY_CODE     => 'some code',
            Headings::TRANSACTION_AMOUNT   => $row[Headings::AMOUNT],
            Headings::SETTLEMENT_DATE      => $row[Headings::EXECUTION_DATE],
            Headings::RBI_SEQUENCE_NUMBER  => UniqueIdEntity::generateUniqueId(),
            Headings::STATUS               => Status::SETTLED,
            Headings::RETURN_REASON        => 'nothing',
        ];

        if ($generateFailedReconciliations === true)
        {
            $data[Headings::STATUS]  = Status::REJECTED;
        }

        return $data;
    }
}
