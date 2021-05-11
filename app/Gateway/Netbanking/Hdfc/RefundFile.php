<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Constants\MailTags;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'HDFC_Netbanking_Refunds';

    const BASE_STORAGE_DIRECTORY = 'Hdfc/Refund/Netbanking/';

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

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];
    }

    protected function sendRefundEmail($fileData = [], $email = null)
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_HDFC, $email);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData($input)
    {
        $i = 1;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d/m/Y');

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

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
