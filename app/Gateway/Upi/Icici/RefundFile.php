<?php

namespace RZP\Gateway\Upi\Icici;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Upi_Refunds';

    protected static $headers = array(
        'Merchant reference Number',
        'bankadjref',
        'refundRef',
        'Flag',
        'shtdat',
        'adjamt',
        'shcrd',
        'specifyother',
    );

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $urlCsv = $this->writeToCsvFile($data, $fileName);

        $creator = $this->createFile(
            FileStore\Format::CSV,
            $data,
            $fileName,
            FileStore\Type::ICICI_UPI_REFUND);

        $this->sendRefundEmail();

        return $urlCsv;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $fullpath = $this->getCsvFullFilePath();

        $data['file'] = $fullpath;
        $data['body'] = 'Please find attached refunds information for UPI';

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('refunds@razorpay.com', 'UPI Icici Refunds');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('UPI Icici refunds file for ' . $today);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }

    protected function getRefundData($input)
    {
        $fileName = $this->getCsvFileToWriteName();

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('Y-m-d');

            $data[] = array(
                'Merchant reference Number' => $row['payment']['id'],
                'bankadjref'                => $row['gateway']['gateway_payment_id'],
                'refundRef'                 => $fileName,
                'Flag'                      => 'C',
                'shtdat'                    => $date,
                'adjamt'                    => ($row['refund']['amount'] / 100),
                'shcrd'                     => $row['gateway']['vpa'],
                'specifyother'              => $row['refund']['id'],
            );
        }

        return $data;
    }
}
