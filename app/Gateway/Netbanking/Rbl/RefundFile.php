<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Rbl_Netbanking_Refunds';

    protected static $headers = [
        RefundFields::SERIAL_NO,
        RefundFields::REFUND_ID,
        RefundFields::BANK_ID,
        RefundFields::MERCHANT_NAME,
        RefundFields::TRANSACTION_DATE,
        RefundFields::REFUND_DATE,
        RefundFields::MERCHANT_ID,
        RefundFields::BANK_REFERENCE,
        RefundFields::PGI_REFERENCE,
        RefundFields::TRANSACTION_AMOUNT,
        RefundFields::REFUND_AMOUNT,
    ];

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::RBL_NETBANKING_REFUND
        );

        $file = $creator->get();

        $today = Carbon::now('Asia/Kolkata')->format('jS F Y');

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        return [
            'local_file_path' => $file['local_file_path'],
            'signed_url'      => $signedFileUrl,
            'count'           => count($data) - 1,
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];
    }

    protected function getRefundData($input)
    {
        $data = [];

        $data[] = self::$headers;

        $index = 1;

        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row['payment']['created_at'],
                        'Asia/Kolkata')
                        ->format('m-d-y h:m:s');

            $refundDate = Carbon::createFromTimestamp(
                              $row['refund']['created_at'],
                              'Asia/Kolkata')
                              ->format('m-d-y h:m:s');

            $data[] = [
                RefundFields::SERIAL_NO          => $index++,
                RefundFields::REFUND_ID          => $row['refund']['id'],
                RefundFields::BANK_ID            => Constants::BANK_ID,
                RefundFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                RefundFields::TRANSACTION_DATE   => $date,
                RefundFields::REFUND_DATE        => $refundDate,
                RefundFields::MERCHANT_ID        => $row['terminal']['gateway_merchant_id'],
                RefundFields::BANK_REFERENCE     => $row['gateway']['bank_payment_id'],
                RefundFields::PGI_REFERENCE      => $row['payment']['id'],
                RefundFields::TRANSACTION_AMOUNT => $this->getFormatedAmount($row['payment']['amount']),
                RefundFields::REFUND_AMOUNT      => $this->getFormatedAmount($row['refund']['amount']),
            ];

            $totalAmount += $row['refund']['amount'] / 100;
        }

        return [$totalAmount, $data];
    }

    protected function getFormatedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
