<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use RZP\Gateway\Netbanking\Axis\EMandateDebitFileHeadings as Headings;

class AxisV2 extends Axis
{
    const EXTENSION = FileStore\Format::XLS;

    const FILE_NAME = '274_BulkDebit_{$datePrefix}_RAZOR{$datePostfix}';

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->netbanking;
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt([]);

            $creator = new FileStore\Creator;

            $colFormat = [
                'D'  => NumberFormat::FORMAT_TEXT,
                'F'  => NumberFormat::FORMAT_TEXT,
                'H'  => NumberFormat::FORMAT_TEXT,
                'I'  => NumberFormat::FORMAT_TEXT,
                'J'  => NumberFormat::FORMAT_TEXT
            ];

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->headers(false)
                ->columnFormat($colFormat)
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->metadata(static::FILE_METADATA)
                ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "OTHER_BANKS");
        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_GENERATION_ERROR);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $debitDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('d/m/Y');

            $razorDate = "RAZOR" . Carbon::now(Timezone::IST)->format('dmY');

            $row = [
                Headings::PAYMENT_ID                  => $paymentId,
                Headings::DEBIT_DATE                  => $debitDate,
                Headings::RAZORPAY_CODE               => "RAZORPA",
                Headings::CUSTOMER_UID                => $token->getGatewayToken(),
                Headings::CUSTOMER_NAME               => $token->customer === null ? "" : $token->customer->getName(),
                // If the account number starts with 0 and the file is
                // opened with MS-Excel, it trims the 0 since it treats
                // the account number as an integer rather than a string.
                // Adding a `'` in the start ensures that MS-Excel
                // treats it as a string and not an integer.
                Headings::DEBIT_ACCOUNT               => $token->getAccountNumber(),
                Headings::AMOUNT                      => $this->getAmount($token['payment_amount']),
                Headings::CUSTOMER_UID_1              => $token->getGatewayToken(),
                Headings::GATEWAY_MERCHANT_ID         => $token->terminal->getGatewayMerchantId(),
                Headings::RAZOR_DATE                  => $razorDate
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getAmount($amount)
    {
        return $amount / 100;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $datePrefix = Carbon::now(Timezone::IST)->format('Ydm');

        $datePostfix = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->isTestMode() === true)
        {
            $fileName = strtr(static::FILE_NAME, ['{$datePrefix}' => $datePrefix, '{$datePostfix}' => $datePostfix]);

            return $fileName . '_' . $this->mode;
        }

        $fileName = strtr(static::FILE_NAME, ['{$datePrefix}' => $datePrefix, '{$datePostfix}' => $datePostfix]);

        return static::BASE_STORAGE_DIRECTORY . $fileName;
    }
}
