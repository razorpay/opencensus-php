<?php

namespace RZP\Gateway\Upi\Icici;

use Carbon\Carbon;
use Mail;
use RZP\Gateway\Base;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants;
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

        // TODO FIX CSV
        $creator = $this->createFile(
            FileStore\Format::CSV,
            $data,
            $fileName,
            FileStore\Type::ICICI_UPI_REFUND);

        $file = $creator->get();

        $fileData = [
            'file_path' => $file['local_file_path'],
        ];

        $this->sendRefundEmail($fileData);

        return $fileData['file_path'];
    }

    protected function sendRefundEmail($fileData = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Constants::UPI_ICICI);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData($input)
    {
        $fileName = $this->getFileToWriteName(FileStore\Format::CSV);

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
