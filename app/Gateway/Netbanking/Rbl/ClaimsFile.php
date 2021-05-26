<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Gateway\Netbanking\Base\Entity;

class ClaimsFile extends Base\RefundFile
{
    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    const BASE_STORAGE_DIRECTORY = 'Rbl/Claims/Netbanking/';

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
            FileStore\Type::RBL_NETBANKING_CLAIM
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        return [
            'local_file_path' => $file['local_file_path'],
            'signed_url'      => $signedFileUrl,
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
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
                        $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                        Timezone::IST)
                        ->format('m-d-y h:m:s');

            $paymentAmount = $this->getFormattedAmount($row[self::PAYMENT_ENTITY][Payment\Entity::AMOUNT]);

            $data[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => $row[self::GATEWAY_ENTITY][Entity::CUSTOMER_ID],
                ClaimFields::DEBIT_ACCOUNT      => $row[self::GATEWAY_ENTITY][Entity::ACCOUNT_NUMBER],
                ClaimFields::CREDIT_ACCOUNT     => $row[self::GATEWAY_ENTITY][Entity::CREDIT_ACCOUNT_NUMBER],
                ClaimFields::TRANSACTION_AMOUNT => $paymentAmount,
                ClaimFields::PGI_REFERENCE      => $row[self::GATEWAY_ENTITY][Entity::BANK_PAYMENT_ID],
                ClaimFields::BANK_REFERENCE     => $row[self::PAYMENT_ENTITY][Payment\Entity::ID],
                ClaimFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                ClaimFields::PGI_STATUS         => $this->getGatewayStatus($row),
                ClaimFields::ERROR_DESCRIPTION  => $this->getErrorMessage($row),
                ClaimFields::TRANSACTION_STATUS => $this->getPaymentStatus($row),
            ];

            $totalAmount +=  $row['payment']['amount'] / 100;
        }

        return [$totalAmount, $data];
    }

    protected function getPaymentStatus(array $row)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::REFUNDED,
            Payment\Status::CAPTURED
        ];

        if (in_array($row[self::PAYMENT_ENTITY][Payment\Entity::STATUS], $status, true) === true)
        {
            return 'Success';
        }

        return 'Failed';
    }

    protected function getGatewayStatus(array $row)
    {
        if ($row[self::GATEWAY_ENTITY][Entity::STATUS] === 'SUC')
        {
            return 'Success';
        }

        return 'Failed';
    }

    protected function getErrorMessage(array $row)
    {
        if (empty($row[self::GATEWAY_ENTITY][Entity::ERROR_MESSAGE]) === true)
        {
            return 'NA';
        }

        return $row[self::GATEWAY_ENTITY][Entity::ERROR_MESSAGE];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
