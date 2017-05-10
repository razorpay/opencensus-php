<?php

namespace RZP\Gateway\Wallet\Payumoney;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Payumoney_Wallet_Refunds';

    protected static $headers = [
        'Sr No',
        'Transaction date',
        'Gateway reference #',
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
            FileStore\Type::PAYUMONEY_WALLET_REFUND);

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'signed_url' => $signedFileUrl,
            'name'       => basename($file['local_file_path'])
        ];

        $this->sendRefundEmail($fileData);

        return $fileData['file_path'];
    }

    protected function sendRefundEmail($fileData = [])
    {
        $data = [
            'file' => $fileData['signed_url'],
            'name' => $fileData['name'],
            'body' => 'Please find attached refunds information for PayUMoney'
        ];

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Wallet Payumoney refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('PayUMoney refunds file for ' . $today);

            $message->to($emails);

            $message->attach($data['file'], ['as' => $data['name']]);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYU_MONEY_REFUNDS_MAIL);
        });
    }

    protected function getRefundData($input)
    {
        $i = 1;

        $data = [];

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d/m/Y');

            $data[] = [
                'Sr No'               => $i++,
                'Transaction date'    => $date,
                'Gateway reference #' => $row['gateway']['gateway_payment_id'],
                'Order #'             => $row['payment']['id'],
                'Order Amount'        => $row['payment']['amount'] / 100,
                'Refund Amount'       => $row['refund']['amount'] / 100,
                'Merchant Code'       => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $data;
    }
}
