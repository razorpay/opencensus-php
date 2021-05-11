<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;

class Reconciliator extends Base\RefundFile
{
    const PAYMENT_ENTITY = 'payment';
    const GATEWAY_ENTITY = 'gateway';

    const BANK_REF_NUMBER = '0099999';
    const BANK_ACC_NUMBER = '309002069863';

    protected static $fileToWriteName = 'Bob_Netbanking_Reconciliation';

    const BASE_STORAGE_DIRECTORY = 'Bob/Recon/Netbanking/';

    protected $header1 = ['fileName.txt', 'Num of Txn', 'Total Amount of Txns'];

    protected $header2 = [
        'Sr.No',
        'fldMerchCode',
        'TransDate',
        'fldMerchRefNbr',
        'Transaction Amount',
        'fldBankRefNbr',
        'AccountNo.'
    ];

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_bob'
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
            FileStore\Type::BOB_NETBANKING_REFUND
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
                'sr_no'           => $index++,
                'merchant_code'   => 'razorpay',
                'date'            => $date,
                'payment_id'      => $row['payment']['id'],
                'amount'          => $amount,
                'bank_ref_number' => self::BANK_REF_NUMBER,
                'bank_acc_number' => self::BANK_ACC_NUMBER,
            ];

            $totalAmount += floatval($amount);
        }

        $output = array_merge(
            $this->generateHeaders($data, $totalAmount),
            $data
        );

        return [$totalAmount, $output];
    }

    protected function generateHeaders($data, $totalAmount)
    {
        return [
            $this->header1,
            [
                self::$fileToWriteName . ".txt",
                count($data),
                $totalAmount
            ],
            $this->header2
        ];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
