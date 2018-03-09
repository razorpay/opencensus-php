<?php

namespace RZP\Models\FundTransfer\Icici;

use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Models\FundTransfer\Icici\Reconciliation\Mode;
use RZP\Models\FundTransfer\Icici\Reconciliation\Status;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class ReconciliationGenerator
{
    use FileHandlerTrait;

    const INTERNAL_FAILURE_REMARK = 'Rejected by RTGS Gateway (Some string goes here)';

    protected static $fileToReadName = 'Icici_Settlement';

    protected static $fileToWriteName = 'Icici_Settlement_Reconciliation';

    # TODO:: Handle multiple FTAs for a settlement in one file later
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
            $newRow = $this->generateReconciliationFields($row, $input);

            $reconData[] = $newRow;
        }

        $txt = $this->generateText($reconData, ',');

        $filename = 'NRPSS_' . str_random(10). '.txt';

        $file = $this->createTxtFile($filename, $txt);

        return $file;
    }

    protected function getDecryptedFile($file)
    {
        $aes = new AES(AES::MODE_ECB);

        $aes->setKey(NodalAccount::ENCRYPTION_KEY);

        $decryptedData = $aes->decrypt(file_get_contents($file));

        $dirPath = pathinfo($file, PATHINFO_DIRNAME);

        $decryptedFilePath = $dirPath . '/Decrypted.txt';

        file_put_contents($decryptedFilePath, $decryptedData);

        $data = $this->parseTextFile($decryptedFilePath, ',');

        return $data;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    protected function generateReconciliationFields($row, array $params)
    {
        $remark = (isset($params['internal_failure']) and $params['internal_failure'] === '1') ?
            self::INTERNAL_FAILURE_REMARK : random_integer(7);

        $generateFailedReconciliations = (isset($params['failed_recons'])) ?
            ((bool) $params['failed_recons']) : false;

        $data = [
            Headings::FILE_REF_NO               => '000205025290',
            Headings::PAYMENT_MODE              => $this->getReconModeFromTransactionMode($row[Headings::PAYMENT_MODE]),
            Headings::BENEFICIARY_NAME          => $row[Headings::BENEFICIARY_NAME],
            Headings::BENEFICIARY_ACCOUNT_NO    => $row[Headings::BENEFICIARY_ACCOUNT_NO],
            Headings::BENEFICIARY_IFSC          => $row[Headings::BENEFICIARY_IFSC],
            Headings::AMOUNT                    => $row[Headings::AMOUNT],
            Headings::PAYMENT_DATE              => $row[Headings::PAYMENT_DATE],
            Headings::REMARKS                   => $remark,
            Headings::CMS_REF_NO                => 'CMS' . random_integer(9),
            Headings::PAYMENT_REF_NO            => $row[Headings::INSTRUMENT_REFERENCE],
            Headings::STATUS                    => Status::PAID,
            Headings::CREATE_DATE               => $row[Headings::PAYMENT_DATE],
            Headings::DUMMY3                    => 'dummy',
        ];

        if ($generateFailedReconciliations === true)
        {
            $data[Headings::STATUS]  = Status::CANCELLED;

            unset($data[Headings::CREATE_DATE]);
        }

        return $data;
    }

    protected function getReconModeFromTransactionMode(string $mode)
    {
        switch ($mode)
        {
            case 'N':
                return Mode::NEFT;
            case 'R':
                return Mode::RTGS;
            case 'I':
                return Mode::IFT;
            default:
                throw new Exception\LogicException('Unknown bank mode: ', $mode);
        }
    }
}
