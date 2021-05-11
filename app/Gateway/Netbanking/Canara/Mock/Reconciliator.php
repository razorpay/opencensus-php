<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;

class Reconciliator extends Base\RefundFile
{
    const PAYMENT_ENTITY            = 'payment';
    const GATEWAY_ENTITY            = 'gateway';
    const BANK_REF_NUMBER           = 'AB1234';
    const CUSTOMER_ACCOUNT_NO       = '100000';
    const MERCHANT_CODE             = 'RAZORPAY';

    const INITIAL_LINE              = 'FCDBREFERENCE|DEBITACCOUNT|TXNDATE|MERCHANTREFRENCE|MERCHATNCODE|AMOUNT';

    protected static $fileToWriteName = 'Canara_Netbanking_Reconciliation';

    const BASE_STORAGE_DIRECTORY = 'Canara/Recon/Netbanking/';

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_canara'
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

        $finalTextData = self::INITIAL_LINE ."\r\n" . $txt;

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $finalTextData,
            $fileName,
            FileStore\Type::CANARA_NETBANKING_REFUND
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
            $date = Carbon::createFromTimestamp(
                $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('d/m/Y H:i:s A');

            $amount = $this->getFormattedAmount($row['payment']['amount']);

            $col = [
                self::BANK_REF_NUMBER,
                self::CUSTOMER_ACCOUNT_NO,
                $date,
                $row['payment']['id'],
                self::MERCHANT_CODE,
                $amount,
            ];

            $this->content($col, 'canara_recon');

            $totalAmount += floatval($amount);

            $data[] = $col;
        }

        return [$totalAmount,$data];
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
