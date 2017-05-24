<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Rbl_Netbanking_Refunds';

    const EMAIL_BODY = 'Please forward the RBL Netbanking refunds file to the operations team';

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
        $data = $this->getRefundData($input);

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

        $fileData = [
            'subject'    => 'RBL Netbanking refunds file for ' . $today,
            'file_path'  => $file['local_file_path'],
            'signed_url' => $signedFileUrl,
            'count'      => count($data),
            'date'       => $today
        ];

        $this->sendRefundEmail($fileData);

        return $file['local_file_path'];
    }

    protected function getRefundData($input)
    {
        $data[] = self::$headers;

        $index = 1;

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
                RefundFields::TRANSACTION_AMOUNT => $row['payment']['amount'] / 100,
                RefundFields::REFUND_AMOUNT      => $row['refund']['amount'] / 100,
            ];
        }

        return $data;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $this->mail->queue('emails.message', $fileData, function ($message) use ($fileData)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Rbl Netbanking refunds');

            $message->subject($fileData['subject']);

            $message->to($emails);

            $message->attach($fileData['signed_url'], ['as' => $fileData['file_name']]);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::RBL_NETBANKING_REFUNDS_MAIL);
        });
    }
}
