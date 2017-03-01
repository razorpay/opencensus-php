<?php

namespace RZP\Gateway\Netbanking\Federal;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'FBK_REFUND_';

    const EMAIL_BODY = 'Please forward the Federal Netbanking refunds file to the operations team';

    protected static $headers = [
        'Payee ID',
        'Date',
        'PRN',
        'FREEFIELD',
        'BID',
        'TXN Amount',
        'Refund Amount'
    ];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::FEDERAL_NETBANKING_REFUND
        );

        $file = $creator->get();

        $this->sendRefundEmail();

        return [$totalAmount, $file['local_file_path']];
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    'Asia/Kolkata')
                    ->format('Y-d-m');

            $data[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'PRN'           => $row['payment']['id'],
                'FREEFIELD'     => '00000000',
                'BID'           => $row['gateway']['bank_payment_id'],
                'TXN Amount'    => $row['payment']['amount'],
                'Refund Amount' => $row['refund']['amount']
            ];

            $totalAmount += $row['refund']['amount'];
        }

        $txt = $this->getTextData($data);

        return [$txt, $totalAmount];
    }

    protected function getTextData($data)
    {
        $txt = $this->generateText($data, '|', true);

        return txt;
    }

    protected function sendRefundEmail()
    {
        $filePath = $this->getFileToWriteName(FileStore\Format::TXT);

        $this->mail->queue('email.message', $filePath, function ($message) use ($filePath)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Federal Netbanking refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('Federal Netbanking refunds file for ' . $today);

            $message->to($emails);

            $message->attach($filePath);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::HDFC_NETBANKING_REFUNDS_MAIL);
        });
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = $time = Carbon::now('Asia/Kolkata')->format('d_m_Y');

        return self::$fileToWriteName . '_' . $date;
    }
}
