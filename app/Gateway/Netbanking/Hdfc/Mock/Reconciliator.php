<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

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

    protected static $fileToWriteName = 'Hdfc_Netbanking_Reconciliation';

    const BASE_STORAGE_DIRECTORY = 'Hdfc/Recon/Netbanking/';

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_hdfc'
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

        $txt = $this->generateText($data, '~');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::HDFC_NETBANKING_REFUND
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
            $date = Carbon::createFromTimestamp(
                        $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                        Timezone::IST)
                        ->format('d-M-y');

            $amount = $this->getFormattedAmount($row['payment']['amount']);

            $data[] = [
                'RAZPSWIGGY',
                'void@razorpay.com',
                'INR',
                $amount,
                0,
                $row['payment']['id'],
                0,
                self::BANK_REF_NUMBER,
                $date,
                ''
            ];

            $totalAmount += floatval($amount);
        }

        return [$totalAmount,$data];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
