<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation\Mock;

use RZP\Models\Settlement;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Hdfc\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Reconciliation\Mock\Generator;

class FileGenerator extends Generator
{
    use FileHandlerTrait;

    const CHANNEL   = Settlement\Channel::HDFC;

    protected static $fileToReadName  = 'Hdfc_Settlement';

    protected static $fileToWriteName = 'Hdfc_Settlement_Reconciliation';

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
        {
            return [];
        }

        $this->initRequestParams($input);

        $data = $this->parseTextFile($setlFile, ',');

        $reconData = [];
        foreach ($data as $row)
        {
            $newRow = $this->generateReconciliationFields($row);

            $reconData[] = $newRow;
        }

        list($extension, $delimiter) = ($this->generateInternalFailure === true) ?
                                        ['.F001', PHP_EOL] : ['.R001', ','];

        $txt = $this->generateText($reconData, $delimiter);

        $filename = 'HDFC_Recon_' . str_random(10) . $extension;

        $file = $this->createTxtFile($filename, $txt);

        return $file;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    protected function generateReconciliationFields($row)
    {
        if ($this->generateInternalFailure === true)
        {
            $data = $this->generateFailedReconFile($row);
        }
        else
        {
            $data = $this->generateSuccessfulReconFile($row);

            if ($this->generateFailedReconciliations === true)
            {
                $data[Headings::TRANSACTION_STATUS]  = Status::CANCELLED;
            }
        }

        return $data;
    }

    protected function generateSuccessfulReconFile(array $row)
    {
        return [
            Headings::TRANSACTION_TYPE           => $row[Headings::TRANSACTION_TYPE],
            Headings::BENEFICIARY_CODE           => $row[Headings::BENEFICIARY_CODE],
            Headings::BENEFICIARY_NAME           => $row[Headings::BENEFICIARY_NAME],
            Headings::INSTRUMENT_AMOUNT          => $row[Headings::INSTRUMENT_AMOUNT],
            Headings::CHEQUE_NUMBER              => $row[Headings::CHEQUE_NUMBER],
            Headings::TRANSACTION_DATE           => $row[Headings::TRANSACTION_DATE],
            Headings::CUSTOMER_REFERENCE_NUMBER  => $row[Headings::CUSTOMER_REFERENCE_NUMBER],
            Headings::PAYMENT_DETAILS_1          => $row[Headings::PAYMENT_DETAILS_1],
            Headings::PAYMENT_DETAILS_2          => $row[Headings::PAYMENT_DETAILS_2],
            Headings::BENEFICIARY_ACCOUNT_NUMBER => $row[Headings::BENEFICIARY_ACCOUNT_NUMBER],
            Headings::BANK_REFERENCE_NO          => UniqueIdEntity::generateUniqueId(),
            Headings::TRANSACTION_STATUS         => Status::SETTLED,
            Headings::REJECT_REASON              => '',
            Headings::IFC_CODE                   => $row[Headings::IFC_CODE],
            Headings::MICR_NUMBER                => '',
            Headings::UTR                        => UniqueIdEntity::generateUniqueId(),
        ];
    }

    protected function generateFailedReconFile($row)
    {
        return [
            '[' . implode(',' , $row) . '] ' . PHP_EOL
            . 'error : Invalid IFSC code for IMPS(P2A) at line no3 ' . PHP_EOL
            . 'error : Invalid Transaction Type at line no :3' . PHP_EOL
        ];
    }
}
