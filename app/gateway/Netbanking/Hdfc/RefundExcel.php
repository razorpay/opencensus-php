<?php

namespace Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
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

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        $i = 1;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d/m/Y');

            $data[] = array(
                'Sr No'            => $i++,
                'Transaction date' => $date,
                'Bank reference #' => $row['gateway']['bank_payment_id'],
                'Order #'          => $row['payment']['id'],
                'Order Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'    => $row['refund']['amount'] / 100,
                'Merchant Code'    => $row['terminal']['gateway_merchant_id'],
            );
        }

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());

        $this->sendHdfcNbRefundEmail();

        return $urlExcel;
    }

    protected function sendHdfcNbRefundEmail()
    {
        $fullpath = $this->getExcelFullFilePath();

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

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'Hdfc Netbanking refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('HDFC Netbanking refunds file for ' . $today);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }
}