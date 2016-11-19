<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

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

    const EMAIL_BODY = 'Please forward the HDFC Netbanking refunds file to: Directpay.Refunds@hdfcbank.com';

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $urlExcel = $this->writeToExcelFile($data, $fileName);

        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::HDFC_NETBANKING_REFUND);

        $fileData = [
            'file_path' => $this->getExcelFullFilePath(),
            'body' => self::EMAIL_BODY,
        ];

        $this->sendRefundEmail($fileData);

        return $urlExcel;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $fullpath = $this->getExcelFullFilePath();

        $this->mail->queue('emails.message', $fileData, function ($message) use ($fileData)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Hdfc Netbanking refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('HDFC Netbanking refunds file for ' . $today);

            $message->to($emails);

            $message->attach($fileData['file_path']);
        });
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
