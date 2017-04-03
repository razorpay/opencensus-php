<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use Mail;

use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Metadata;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'HDFC_Netbanking_Refunds';

    protected static $headers = [
        'Sr No',
        'Transaction date',
        'Bank reference #',
        'Order #',
        'Order Amount',
        'Refund Amount',
        'Merchant Code',
    ];

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::HDFC_NETBANKING_REFUND);

        $file = $creator->get();

        $fileData = [
            'file_path' => $file['local_file_path'],
        ];

        $this->sendRefundEmail($fileData);

        return $file['local_file_path'];
    }

    protected function sendRefundEmail($fileData = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Metadata::NETBANKING_HDFC);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData($input)
    {
        $i = 1;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d/m/Y');

            $data[] = [
                'Sr No'            => $i++,
                'Transaction date' => $date,
                'Bank reference #' => $row['gateway']['bank_payment_id'],
                'Order #'          => $row['payment']['id'],
                'Order Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'    => $row['refund']['amount'] / 100,
                'Merchant Code'    => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $data;
    }
}
