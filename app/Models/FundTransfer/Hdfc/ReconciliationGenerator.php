<?php

namespace RZP\Models\FundTransfer\Hdfc;

use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Hdfc\Reconciliation\Status;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class ReconciliationGenerator
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Hdfc_Settlement';

    protected static $fileToWriteName = 'Hdfc_Settlement_Reconciliation';

    # TODO:: Handle multiple FTAs for a settlement in one file later
    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
        {
            return [];
        }

        $data = $this->parseTextFile($setlFile, ',');

        $generateFailedReconciliations = false;

        if (isset($input['failed_recons']) === true)
        {
            $generateFailedReconciliations = ($input['failed_recons'] === '1');
        }

        $reconData = [];
        foreach ($data as $row)
        {
            $newRow = $this->generateReconciliationFields($row, $generateFailedReconciliations);

            $reconData[] = $newRow;
        }

        $txt = $this->generateText($reconData, ',');

        $filename = 'HDFC_Recon_' . str_random(10) . '.r01';

        $file = $this->createTxtFile($filename, $txt);

        return $file;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    protected function generateReconciliationFields($row, bool $generateFailedReconciliations)
    {
        $data = [
            Headings::TRANSACTION_TYPE          => $row[Headings::TRANSACTION_TYPE],
            Headings::BENEFICIARY_CODE          => $row[Headings::BENEFICIARY_CODE],
            Headings::BENEFICIARY_NAME          => $row[Headings::BENEFICIARY_NAME],
            Headings::INSTRUMENT_AMOUNT         => $row[Headings::INSTRUMENT_AMOUNT],
            Headings::CHEQUE_NUMBER             => $row[Headings::CHEQUE_NUMBER],
            Headings::TRANSACTION_DATE          => $row[Headings::TRANSACTION_DATE],
            Headings::CUSTOMER_REFERENCE_NUMBER => $row[Headings::CUSTOMER_REFERENCE_NUMBER],
            Headings::PAYMENT_DETAILS_1         => $row[Headings::PAYMENT_DETAILS_1],
            Headings::PAYMENT_DETAILS_2         => $row[Headings::PAYMENT_DETAILS_2],
            Headings::BENEFICIARY_ACCOUNT_NUMBER => $row[Headings::BENEFICIARY_ACCOUNT_NUMBER   ],
            Headings::BANK_REFERENCE_NO         => UniqueIdEntity::generateUniqueId(),
            Headings::TRANSACTION_STATUS        => Status::SETTLED,
            Headings::REJECT_REASON             => '',
            Headings::IFC_CODE                  => $row[Headings::IFC_CODE],
            Headings::MICR_NUMBER               => '',
            Headings::UTR                       => UniqueIdEntity::generateUniqueId(),
        ];

        if ($generateFailedReconciliations === true)
        {
            $data[Headings::TRANSACTION_STATUS]  = Status::CANCELLED;
        }

        return $data;
    }
}
