<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Carbon\Carbon;
use Mail;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;
use RZP\Constants\MailTags;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class ClaimsFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Rbl_Netbanking_Claims';

    protected static $headers = [
        ClaimFields::SERIAL_NO,
        ClaimFields::TRANSACTION_DATE,
        ClaimFields::USER_ID,
        ClaimFields::DEBIT_ACCOUNT,
        ClaimFields::CREDIT_ACCOUNT,
        ClaimFields::TRANSACTION_AMOUNT,
        ClaimFields::PGI_REFERENCE,
        ClaimFields::BANK_REFERENCE,
        ClaimFields::MERCHANT_NAME,
        ClaimFields::PGI_STATUS,
        ClaimFields::ERROR_DESCRIPTION,
        ClaimFields::TRANSACTION_STATUS,
    ];

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getClaimsData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, ',');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::RBL_NETBANKING_REFUND
        );

        $file = $creator->get();

        $today = Carbon::now('Asia/Kolkata')->format('jS F Y');

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        return [
            'file_path'    => $file['local_file_path'],
            'signed_url'   => $signedFileUrl,
            'count'        => count($data) - 1,
            'file_name'    => basename($file['local_file_path']),
            'total_amount' => $totalAmount,
        ];
    }

    protected function getClaimsData($input)
    {
        $data = [];

        $index = 1;

        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row['payment']['created_at'],
                        'Asia/Kolkata')
                        ->format('m-d-y h:m:s');

            $data[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => $row['gateway']['customer_id'],
                ClaimFields::DEBIT_ACCOUNT      => $row['gateway']['account_number'],
                ClaimFields::CREDIT_ACCOUNT     => $row['gateway']['credit_account_number'],
                ClaimFields::TRANSACTION_AMOUNT => $row['payment']['amount'] / 100,
                ClaimFields::PGI_REFERENCE      => $row['payment']['id'],
                ClaimFields::BANK_REFERENCE     => $row['gateway']['bank_payment_id'],
                ClaimFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                ClaimFields::PGI_STATUS         => $row['gateway']['status'],
                ClaimFields::ERROR_DESCRIPTION  => $row['gateway']['error_message'],
                ClaimFields::TRANSACTION_STATUS => $row['payment']['status'],
            ];

            $totalAmount +=  $row['payment']['amount'] / 100;
        }

        return [$totalAmount, $data];
    }
}
