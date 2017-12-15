<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment;

class Reconciliator extends Base\RefundFile
{
    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    protected static $fileToWriteName = 'Pnb_Netbanking_Reconciliation';

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getReconciliationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, '|');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::PNB_NETBANKING_REFUND
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
                        ->format('d/m/y');
            s($row);
            $data[] = [
                'prn'            => $row['payment']['reference1'],
                'payment_id'     => $row['payment']['id'],
                'bank_reference' => $row['gateway']['bank_payment_id'],
                'amount'         => $row['payment']['amount'],
                'date'           => $date,
            ];
            $totalAmount += $row[self::PAYMENT_ENTITY][Payment\Entity::AMOUNT] / 100;
        }

        $this->content($data, 'claims_data');

        return [$totalAmount, $data];
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_pnb'
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
}
