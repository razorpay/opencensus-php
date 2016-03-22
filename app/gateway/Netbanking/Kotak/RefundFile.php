<?php

namespace Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use Gateway\Netbanking\Base;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Kotak_Netbanking_Refunds';

    protected static $headers = [
        'S.No',
        'Mer.Id',
        'Date',
        'Mer.RefNo.',
        'Amount',
        'Bank.RefNo.'];

    public function generate($input)
    {
        $txt = $this->getRefundData($input);

        $name = $this->getFileToWriteName();

        $filePath = $this->writeToTextFile($txt);

        $fileFullPath = $this->getFullFilePath($name);

        $this->sendKotakRefundsMail($totalAmount, $fileFullPath);

        return $filePath;
    }

    protected function getTextData($data, $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine.$txt;

        return $txt;
    }

    protected function sendKotakRefundsMail($totalAmount, $filePath)
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $data = [
            'subject'   => 'Kotak NB Refund files for '.$today,
            'body'      => 'PFA attached refund file.',
            'file'      => $filePath,
        ];

        $this->mail->queue('emails.message', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Refunds');

            $message->subject($data['subject']);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }

    protected function getRefundData($input)
    {
        // S.No in this file begins with 1
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
                $row['gateway']['int_payment_id'],
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + $row['refund']['amount'] / 100;
        }

        $name = $this->getFileToWriteName();

        $i--;

        // First Line in the file is expected to be of the format
        // Format : FileName|ItemsCount|TotalAmount(Rs.)|CHECKSUM
        $initialLine = $name.'|'.$i.'|'.$totalAmount.'|CHECKSUM'."\r\n";

        $txt = $this->getTextData($data, $initialLine);

        return $txt;
    }
}
