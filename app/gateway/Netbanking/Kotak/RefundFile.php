<?php

namespace Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use Models\Settlement\Kotak\FileHandlerTrait;

class RefundFile
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Netbanking_Refunds';

    //@shk need clarity on what each of these fields means
    //for each transaction. That's about it.
    protected static $headers = [
    'S.No',
    'Mer.Id',
    'Date',
    'Mer.RefNo.',
    'Amount',
    'Bank.RefNo.'];

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        $i = 1;

        $data = [];
        $totalAmount = 0;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d-M-Y');

            $data[] = array(
                $i++,
                $row['gateway']['merchant_code'],
                $date,
                $row['payment']['id'],
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + $row['refund']['amount'] / 100;
        }

        $name = $this->getFileToWriteName();

        $i--;

        $initialLine = $name.'|'.$i.'|'.$totalAmount.'|CHECKSUM'."\r\n";

        $txt = $this->getTextData($data, $initialLine);

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakRefundsMail($totalAmount, $urlText);

        return ;

    }

    protected function getTextData($data, $prependLine = '')
    {
        $txt = $this->generateText($data, '|');

        $txt = $prependLine.$txt;

        return $txt;
    }

    protected function sendKotakRefundsMail($totalAmount, $urlText)
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $data = [
            'amount'    => $totalAmount,
            'subject'   => "Kotak NB Refund files for $today",
            'file'      => $urlText,
        ];

        $this->mail->queue('emails.admin.kotak_refund', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Refunds');

            $message->subject($data['subject']);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }
}
