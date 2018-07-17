<?php

namespace RZP\Gateway\Netbanking\Allahabad;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use RZP\Constants\MailTags;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;


class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Refund_sample';

    protected static $headers = [
        'PID',
        'Bank ID',
        'Merchant Name',
        'Txn Date',
        'Refund Date',
        'Bank Merchant Code',
        'Bank Ref No.',
        'PGI Reference No.',
        'Txn Amount',
        'Refund'
    ];

    public function generate($input)
    {
        $content = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
                    FileStore\Format::TXT,
                    $content,
                    $fileName,
                    FileStore\Type::ALLAHABAD_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'signed_url' => $signedFileUrl,
            'file_name'  => basename($file['local_file_path']),
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];

    }

    protected function sendRefundEmail($fileData = [], array $email = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_ALLAHABAD, $email);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData($input)
    {
        foreach ($input['data'] as $row)
        {
            $txnDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $pid = $row['payment']['id'];

            $data[] = [
                'PID'                   => $pid,
                'Bank Id'               => '',
                'Merchant Name'         => '',
                'Txn Date'              => $txnDate,
                'Refund Date'           => $refundDate,
                'Bank Merchant Code'    => '',
                'Bank Ref No.'          => '',
                'PGI Reference No.'     => '',
                'Txn Amount'            => $row['payment']['amount'] / 100,
                'Refund'                => $row['refund']['amount'] / 100,
            ];

            $txt = $this->getTextData($data);
            s($txt);
            return $txt;

        }
    }

    protected function getTextData($data)
    {
        $txt  = $this->generateText($data,'|',true);

        return $txt;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        return self::$fileToWriteName;
    }
}