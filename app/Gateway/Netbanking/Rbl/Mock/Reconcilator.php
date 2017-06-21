<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use Carbon\Carbon;
use Mail;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class Reconcilator extends Base\RefundFile
{
    protected static $fileToWriteName = 'Rbl_Netbanking_Reconcilation';

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
    ];

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getReconcilationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, ',');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::RBL_NETBANKING_CLAIM
        );

        $file = $creator->get();

        $today = Carbon::now('Asia/Kolkata')->format('jS F Y');

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];
    }

    protected function getReconcilationData($input)
    {
        $data = [];

        $index = 1;

        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            s($row);
            $date = Carbon::createFromTimestamp(
                        $row['created_at'],
                        'Asia/Kolkata')
                        ->format('m-d-y h:m:s');

            $data[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => 342355,
                ClaimFields::DEBIT_ACCOUNT      => '309002069863',
                ClaimFields::CREDIT_ACCOUNT     => '309001141935',
                ClaimFields::TRANSACTION_AMOUNT => $row['amount'] / 100,
                ClaimFields::PGI_REFERENCE      => $row['bank_payment_id'],
                ClaimFields::BANK_REFERENCE     => $row['id'],
                ClaimFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                ClaimFields::PGI_STATUS         => $this->getGatewayStatus($row),
                ClaimFields::ERROR_DESCRIPTION  => $this->getErrorMessage($row),
            ];

            $totalAmount +=  $row['amount'] / 100;
        }

        return [$totalAmount, $data];
    }

    protected function getGatewayStatus(array $row)
    {
        if ($row['status'] === 'SUC')
        {
            return 'Success';
        }

        return 'Failed';
    }

    protected function getErrorMessage(array $row)
    {
        if (empty($row['error_message']) === true)
        {
            return 'NA';
        }

        return $row['gateway']['error_message'];
    }
}
