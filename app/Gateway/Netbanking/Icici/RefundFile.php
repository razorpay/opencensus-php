<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Metadata;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Netbanking_Refunds';

    const EMAIL_BODY = 'Please forward the ICICI Netbanking refunds file to UBPS operations team';

    // The columns of the file
    protected static $headers = [
        RefundFileFields::SERIAL_NO,
        RefundFileFields::PAYEE_ID,
        RefundFileFields::SPID,
        RefundFileFields::BANK_REFERENCE_ID,
        RefundFileFields::TRANSACTION_DATE,
        RefundFileFields::TRANSACTION_AMOUNT,
        RefundFileFields::REFUND_AMOUNT,
        RefundFileFields::TRANSACTION_ID,
        RefundFileFields::REFUND_MODE,
        RefundFileFields::REMARKS,
    ];

    public function generate($input)
    {
        $data = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        // Creating a file with excel format
        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::ICICI_NETBANKING_REFUND);

        $file = $creator->get();

        $fileData = [
            'file_path' => $file['local_file_path'],
            'body'      => self::EMAIL_BODY,
        ];

        $this->sendRefundEmail($fileData);

        return $file['local_file_path'];
    }

    protected function getRefundData($input)
    {
        foreach ($input['data'] as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], 'Asia/Kolkata')->format('jS F Y');

            $data[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::PAYEE_ID           => $row['terminal']['gateway_merchant_id'],
                RefundFileFields::SPID               => $row['terminal']['gateway_merchant_id2'],
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id'],
                RefundFileFields::TRANSACTION_DATE   => $date,
                RefundFileFields::TRANSACTION_AMOUNT => $row['payment']['amount'] / 100,
                RefundFileFields::REFUND_AMOUNT      => $row['refund']['amount'] / 100,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::REFUND_MODE        => 'C',
                RefundFileFields::REMARKS            => '',
            ];
        }

        return $data;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Metadata::NETBANKING_ICICI);

        $this->mail->send($refundFileMail);
    }
}
