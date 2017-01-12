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
        'Sr No',
        'Payee_id',
        'SPID',
        'Bank Reference No.',
        'Transaction Date',
        'Transaction Amount',
        'Refund Amount',
        'Transaction Id',
        'Reversal/Cancellation',
        'Remarks',
    ];

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

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

            $data[] = [
                RefundFileFields::SERAL_NO               => $index + 1,
                RefundFileFields::PAYEE_ID               => $row['terminal']['gateway_merchant_id'],
                RefundFileFields::SPID                   => $row['terminal']['gateway_merchant_id2'],
                RefundFileFields::BANK_REFERENCE_ID      => $row['gateway']['bank_payment_id'],
                RefundFileFields::TRANSACTION_DATE       => $date,
                RefundFileFields::TRANSACTION_AMOUNT     => $row['payment']['amount'] / 100,
                RefundFileFields::REFUND_AMOUNT          => $row['refund']['amount'] / 100,
                RefundFileFields::TRANSACTION_ID         => $row['payment']['id'],
                RefundFileFields::REFUND_MODE            => 'C',
                RefundFileFields::REMARKS                => '',
            ];
        }

        return $data;
    }
}
