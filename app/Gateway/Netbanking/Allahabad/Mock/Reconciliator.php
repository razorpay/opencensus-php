<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\RefundFile
{
    const BANK_ID        = '021';
    const PAYMENT_ENTITY = 'payment';
    const GATEWAY_ENTITY = 'gateway';

    protected static $fileToWriteName = 'Allahabad_Netbanking_Reconciliation';

    const BASE_STORAGE_DIRECTORY = 'Allahabad/Recon/Netbanking/';

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_allahabad'
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

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getReconciliationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, '|');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::ALLAHABAD_NETBANKING_REFUND
        );

        $file = $creator->get();

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];
    }

    protected function getReconciliationData(array $input)
    {
        $data = [];

        $totalAmount = 0.0;

        foreach ($input as $row)
        {
            $trnxDate = Carbon::createFromTimestamp(
                                $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                                Timezone::IST)
                                ->format('d-M-y');

            $amount = $this->getFormattedAmount($row['payment']['amount']);

            $data[] = [
                'Bank Id'           => self::BANK_ID,
                'Txn Date'          => $trnxDate,
                'Merchant Name'     => 'Razor',
                'Trnx Amount'       => $amount,
                'PGI Reference No.' => $row['payment']['id'],
                'Bank Ref No.'      => $row['gateway']['bank_payment_id'],
            ];

            $totalAmount += floatval($amount);
        }

        return [$totalAmount, $data];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
