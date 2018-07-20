<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;

class Reconciliator extends Base\RefundFile
{
    const PAYMENT_ENTITY = 'payment';
    const GATEWAY_ENTITY = 'gateway';

    const BANK_REF_NUMBER = '99999';

    protected static $fileToWriteName = 'Allahabad_Netbanking_Reconciliation';

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

        $index = 1;

        $totalAmount = 0.0;

        foreach ($input as $row)
        {
            $trnxDate = Carbon::createFromTimestamp(
                $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('d-M-y');

            $amount = $this->getFormattedAmount($row['payment']['amount']);

            $data[] = [
                'Bank Id' => '027',
                'Txn Date' => $trnxDate,
                'Merchant Name' => '',
                'Trnx Amount' => $amount,
                'PGI Reference No.' => $row['payment']['id'],
                'Bank Ref No.' => '',
            ];

            $totalAmount += floatval($amount);
        }

        return [$totalAmount,$data];
    }
}
