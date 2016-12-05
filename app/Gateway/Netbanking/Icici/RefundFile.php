<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Netbanking_Refunds';

    const EMAIL_BODY = 'Please forward the ICICI Netbanking refunds file to UBPS operations team';

    // The columns of the file
    protected static $headers = [
        RefundFields::SERAL_NO,
        RefundFields::PAYEE_ID,
        RefundFields::SPID,
        RefundFields::BANK_REFERENCE_ID,
        RefundFields::TRANSACTION_DATE,
        RefundFields::TRANSACTION_AMOUNT,
        RefundFields::REFUND_AMOUNT,
        RefundFields::TRANSACTION_ID,
        RefundFields::REFUND_MODE,
        RefundFields::REMARKS,
    ];

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Gets the path to the excel file
        $urlExcel = $this->writeToExcelFile($data, $fileName);

        // Creating a file with excel format
        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::ICICI_NETBANKING_REFUND);

        $fileData = [
            'file_path' => $this->getExcelFullFilePath(),
            'body' => self::EMAIL_BODY,
        ];

        $this->sendRefundEmail($fileData);

        return $urlExcel;
    }

    protected function getRefundData($input)
    {
        foreach ($input['data'] as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], 'Asia/Kolkata')->format('jS F Y');

            $data[] = array(
                RefundFields::SERAL_NO               => $index + 1,
                RefundFields::PAYEE_ID               => $row['terminal']['gateway_merchant_id'],
                RefundFields::SPID                   => $row['terminal']['gateway_merchant_id2'],
                RefundFields::BANK_REFERENCE_ID      => $row['gateway']['bank_payment_id'],
                RefundFields::TRANSACTION_DATE       => $date,
                RefundFields::TRANSACTION_AMOUNT     => $row['payment']['amount'] / 100,
                RefundFields::REFUND_AMOUNT          => $row['refund']['amount'] / 100,
                RefundFields::TRANSACTION_ID         => $row['payment']['id'],
                RefundFields::REFUND_MODE            => 'C',
                RefundFields::REMARKS                => '', // empty
            );
        }

        return $data;
    }
}
