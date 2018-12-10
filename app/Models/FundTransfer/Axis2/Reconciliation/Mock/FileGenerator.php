<?php

namespace RZP\Models\FundTransfer\Axis2\Reconciliation\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

use Config;
use RZP\Constants\Timezone;
use RZP\Models\Settlement;
use RZP\Models\FileStore\Utility;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Axis2\Headings;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Axis2\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Reconciliation\Mock\Generator;

class FileGenerator extends Generator
{
    use FileHandlerTrait;

    const CHANNEL = Settlement\Channel::AXIS;

    protected static $fileToReadName = 'Axis_Settlement';

    protected static $fileToWriteName = 'Axis_Settlement_Reconciliation';

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
        {
            return [];
        }

        $this->initRequestParams($input);

        $data = $this->parseTextFile($setlFile, '^');

        $reconData = [];

        foreach ($data as $row)
        {
            $record = $this->generateReconciliationFields($row);

            $reconData[] = implode('^', $record);
        }

        $textContent = implode(PHP_EOL, $reconData);

        $today = Carbon::now(Timezone::IST)->format('ymd');

        $filename = 'axis_reversefeed_razorpay_' . $today . '-'. str_random(6) .'-' . str_random(3);

        $dir = Utility::getStorageDir();

        $file = $this->createTxtFile($filename, $textContent, $dir);

        return $file;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    public static function getResponseHeading()
    {
        return Headings::getResponseFileHeadings();
    }

    protected function generateReconciliationFields($row)
    {
        $data = [
            Headings::CUSTOMER_UNIQUE_NO         => $row[Headings::CUSTOMER_REFERENCE_NUMBER],
            Headings::CORP_CODE                  => $row[Headings::CORPORATE_CODE],
            Headings::PAYMENT_RUN_DATE           => Carbon::now(Timezone::IST)->format('Y-m-d H:i:m'),
            Headings::PRODUCT_CODE               => $row[Headings::PAYMENT_MODE],
            Headings::TRANSACTION_UTR_NUMBER     => UniqueIdEntity::generateUniqueId(),
            Headings::CHEQUE_NUMBER              => null,
            Headings::STATUS_CODE                => Status::SUCCESS,
            Headings::STATUS_DESCRIPTION         => Status::SUCCESS,
            Headings::BATCH_NO                   => 1,
            Headings::VENDOR_CODE                => $row[Headings::BENEFICIARY_CODE],
            Headings::TRANSACTION_VALUE_DATE     => $row[Headings::VALUE_DATE],
            Headings::BANK_REFERENCE_NUMBER      => UniqueIdEntity::generateUniqueId(),
            Headings::AMOUNT                     => $row[Headings::TRANSACTION_AMOUNT],
            Headings::CORPORATE_ACCOUNT_NUMBER   => $row[Headings::DEBIT_ACCOUNT_NUMBER],
            Headings::CORPORATE_IFSC_CODE        => 'UTIB00001',
            Headings::DEBIT_OR_CREDIT_INDICATOR  => 'Debit',
            Headings::BENEFICIARY_ACCOUNT_NUMBER => $row[Headings::BENEFICIARY_ACCOUNT_NUMBER],
            Headings::CLIENT_BATCH_NO            => 1,
        ];

        if ($this->generateFailedReconciliations === true)
        {
            $data[Headings::STATUS_CODE]  = Status::REJECTED;
        }

        return $data;
    }
}
