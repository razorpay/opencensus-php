<?php

namespace RZP\Gateway\Netbanking\Canara;

use Mail;
use Config;
use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'canara_Netbanking_Refunds';

    const BASE_STORAGE_DIRECTORY = 'Canara/Refund/Netbanking/';

    public function generate($input)
    {
        $text = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $text,
            $fileName,
            FileStore\Type::CANARA_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];
    }

    protected function sendRefundEmail($fileData = [], array $email = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_CANARA, $email);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData(array $input)
    {
        $data = [];

        // For first line
        $data[]    = [
                        RefundFileFields::TRANSACTION_DATE_TIME,
                        RefundFileFields::REFUND_DATE,
                        RefundFileFields::BANK_REF_NO,
                        RefundFileFields::PG_REF_NUM,
                        RefundFileFields::REFUND_REFERENCE,
                        RefundFileFields::TRANSACTION_AMOUNT,
                        RefundFileFields::REFUND_AMOUNT
                    ];

        foreach ($input['data'] as $row)
        {
            $data[] = $this->getDataForRow(
                $row['payment']['created_at'],
                $row['refund']['created_at'],
                $row['gateway']['bank_payment_id'],
                $row['payment']['id'],
                $row['refund']['id'],
                $row['payment']['amount'],
                $row['refund']['amount']
            );
        }

        return $this->generateText($data, '|', true);
    }

    protected function getDataForRow(
        $transactionDate,
        $refundDate,
        $bankRefNo,
        $pgRefNo,
        $refundRef,
        $transactionAmount,
        $refundAmount
    )
    {
        $transactionDate = Carbon::createFromTimestamp((int) $transactionDate, Timezone::IST)
                                   ->format('d-m-Y H:i:s');

        $refundDate = Carbon::createFromTimestamp((int) $refundDate, Timezone::IST)
                              ->format('d-m-Y H:i:s');

        $data = [
            $transactionDate,
            $refundDate,
            $bankRefNo,
            $pgRefNo,
            $refundRef,
            $transactionAmount,
            $refundAmount,
        ];

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
