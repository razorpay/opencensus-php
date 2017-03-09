<?php

namespace RZP\Gateway\Upi\Icici;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    const BANKADJREF            = 'bankadjref';
    const FLAG                  = 'Flag';
    const SHTDAT                = 'shtdat';
    const ADJAMT                = 'adjamt';
    const SHSER                 = 'shser';
    const SHCRD                 = 'shcrd';
    const FILENAME              = 'filename';
    const REASON                = 'reason';
    const SPECIFYOTHER          = 'specifyother';
    const MERCHANTACCOUNT       = 'Merchantaccount';
    const MERCHANT_IFSC_CODE    = 'MerchantIFSCCode';

    protected static $fileToWriteName = 'Icici_Upi_Refunds';

    protected static $headers = array(
        self::BANKADJREF,
        self::FLAG,
        self::SHTDAT,
        self::ADJAMT,
        self::SHSER,
        self::SHCRD,
        self::FILENAME,
        self::REASON,
        self::SPECIFYOTHER,
        self::MERCHANTACCOUNT,
        self::MERCHANT_IFSC_CODE,
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

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::ICICI_UPI_REFUNDS_MAIL);
        });
    }

    protected function getRefundData($input)
    {
        $fileName = $this->getCsvFileToWriteName();

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('Y-m-d');

            $data[] = [
                self::BANKADJREF         => $row['refund']['id'],
                self::FLAG               => 'C',
                self::SHTDAT             => $date,
                self::ADJAMT             => ($row['refund']['amount'] / 100),
                self::SHSER              => $row['gateway']['gateway_payment_id'],
                self::SHCRD              => $row['gateway']['vpa'],
                self::FILENAME           => $fileName,
                self::REASON             => 'NA',
                self::SPECIFYOTHER       => $row['refund']['id'],
                self::MERCHANTACCOUNT    => '',
                self::MERCHANT_IFSC_CODE => '',
            ];
        }

        return $data;
    }
}
