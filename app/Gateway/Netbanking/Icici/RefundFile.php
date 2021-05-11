<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Netbanking_Refunds';

    const BASE_STORAGE_DIRECTORY = 'Icici/Refund/Netbanking/';

    // The columns of the file
    protected static $headers = [
        RefundFileFields::SERIAL_NO,
        RefundFileFields::PAYEE_ID,
        RefundFileFields::SPID,
        RefundFileFields::BANK_REFERENCE_ID,
        RefundFileFields::TRANSACTION_DATE,
        RefundFileFields::TRANSACTION_AMOUNT,
        RefundFileFields::REFUND_AMOUNT,
        RefundFileFields::TRANSACTION_ID,
        RefundFileFields::REFUND_MODE,
        RefundFileFields::REMARKS,
    ];

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Creating a file with excel format
        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::ICICI_NETBANKING_REFUND);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
            'count'      => count($data),
            'amount'     => number_format($totalAmount, 2, '.', ''),
            'date'       => $today
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0;

        $data = [];

        foreach ($input['data'] as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('jS F Y');

            //
            // Although for recurring payments we use token ID as the ITC parameter, we use
            // payment ID as the PRN, therefore we can use the same transaction ID below
            //
            $data[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::PAYEE_ID           => $row['terminal']['gateway_merchant_id'],
                RefundFileFields::SPID               => $row['terminal']['gateway_merchant_id2'],
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id'],
                RefundFileFields::TRANSACTION_DATE   => $date,
                RefundFileFields::TRANSACTION_AMOUNT => $row['payment']['amount'] / 100,
                RefundFileFields::REFUND_AMOUNT      => $row['refund']['amount'] / 100,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::REFUND_MODE        => 'C',
                RefundFileFields::REMARKS            => '',
            ];

            $totalAmount += $row['refund']['amount'] / 100;
        }

        return [$totalAmount, $data];
    }

    protected function sendRefundEmail($fileData = [], $email = null)
    {
        $refundFileMail = new RefundFileMail(
                                $fileData,
                                Gateway::NETBANKING_ICICI,
                                $email,
                                'emails.admin.icici_refunds');

        Mail::queue($refundFileMail);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
