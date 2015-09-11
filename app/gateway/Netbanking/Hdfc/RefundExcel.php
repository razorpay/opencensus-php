<?php

namespace Gateway\Netbanking\Hdfc;

use Models\Settlement\Kotak\FileHandlerTrait;

class RefundExcel
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'HDFC_Netbanking_Refunds';

    protected static $headers = array(
        'Sr No',
        'Transaction date',
        'Bank reference #',
        'Order #',
        'Order Amount',
        'Refund Amount',
        'Merchant Code',

    );

    public static function generate($input)
    {
        $data = [];

        $i = 1;

        foreach ($input as $col)
        {
            $data = array(
                'Sr No'             => $i++,
                'Transaction date'  => $col['gateway']['date'],
                'Bank reference #'  => $col['gateway']['bank_payment_id'],
                'Order #'           => $col['payment']['id'],
                'Order Amount'      => $col['gateway']['amount'],
                'Refund Amount'     => $col['refund']['amount'] / 100,
                'Merchant Code'     => $col['gateway']['gateway_merchant_id'],
            );
        }

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());

        $this->sendHdfcNbRefundEmail();

        return [$urlText, $urlExcel];
    }

    protected function sendHdfcNbRefundEmail()
    {
        $fileName = $this->getExcelFullFilePath();

        $data['file'] = $fullpath;
        $data['body'] = '
            Please forward the HDFC Netbanking refunds file to:
                Directpay.Refunds@hdfcbank.com,
                Kavita.Puthran@hdfcbank.com,
                Charusheela.Ghorpade@hdfcbank.com,
                Santosh.Ghorpade@hdfcbank.com,
                Santosh.Malap@hdfcbank.com,
                Keshav.Mishra@hdfcbank.com,
                Ashish.Mandhare@hdfcbank.com';

        $this->mail->queue('emails.message', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Hdfc Netbanking refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('HDFC Netbanking refunds file for ' . $today);

            $message->to($emails);

            $message->attach($data['file'];
        });
    }
}