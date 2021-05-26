<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Rbl\Status;
use RZP\Gateway\Netbanking\Base\Entity;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;

class Reconciliator extends Base\RefundFile
{
    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    const BASE_STORAGE_DIRECTORY = 'Rbl/Recon/Netbanking/';

    protected static $fileToWriteName = 'Rbl_Netbanking_Reconciliation';

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
        list($totalAmount, $data) = $this->getReconciliationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, ',');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::RBL_NETBANKING_CLAIM
        );

        $file = $creator->get();

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];
    }

    protected function getReconciliationData($input)
    {
        $data = [];

        $index = 1;

        $totalAmount = 0;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                        Timezone::IST)
                        ->format('m-d-y h:m:s');

            $data[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => 342355,
                ClaimFields::DEBIT_ACCOUNT      => '',
                ClaimFields::CREDIT_ACCOUNT     => '',
                ClaimFields::TRANSACTION_AMOUNT => $row[self::PAYMENT_ENTITY][Payment\Entity::AMOUNT] / 100,
                ClaimFields::PGI_REFERENCE      => $row[self::GATEWAY_ENTITY][Entity::BANK_PAYMENT_ID] ?? Server::BANK_REF_NO,
                ClaimFields::BANK_REFERENCE     => $row[self::PAYMENT_ENTITY][Payment\Entity::ID],
                ClaimFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                //As recon payments will always be success in gateway
                ClaimFields::PGI_STATUS         => 'Success',
                ClaimFields::ERROR_DESCRIPTION  => $this->getErrorMessage($row),
            ];

            $totalAmount += $row[self::PAYMENT_ENTITY][Payment\Entity::AMOUNT] / 100;
        }

        $this->content($data, 'claims_data');

        return [$totalAmount, $data];
    }

    protected function getErrorMessage(array $row)
    {
        if (empty($row[self::GATEWAY_ENTITY][Entity::ERROR_MESSAGE]) === true)
        {
            return 'NA';
        }

        return $row[self::GATEWAY_ENTITY][Entity::ERROR_MESSAGE];
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_rbl'
        ];

        $payments = $this->repo->payment->fetch($input, '10000000000000');

        $inputData = [];

        foreach ($payments as $payment)
        {
            $data[self::PAYMENT_ENTITY] = $payment->toArray();

            $gatewayInput['payment_id'] = $payment[Payment\Entity::ID];

            $gatewayPayment = $this->repo->netbanking->fetch($gatewayInput);

            $data[self::GATEWAY_ENTITY] = $gatewayPayment[0]->toArray();

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
