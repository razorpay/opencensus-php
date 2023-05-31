<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Reconciliator extends Base\RefundFile
{
    use FileHandlerTrait;
    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    public function generate($input)
    {
        list($totalAmount, $data) = $this->getReconciliationData($input);

        $fileName = $this->getFileNametoWrite();

        $txt = $this->generateTextWithHeadings($data, ',', false, array_keys($data[0]));

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::MOCK_RECONCILIATION_FILE
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

        $totalAmount = 0;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row[self::PAYMENT_ENTITY][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('Y-m-d');
            $data[] = [
                'TXN ID'       => 99999, // this is the value used in createNetbanking function used in recon test
                'ITC'       => $row['payment']['id'],
                'PRN'    => $row['payment']['id'],
                'Amount'    => $this->getFormattedAmount($row['payment']['amount']),
                'Date'      => $date,
            ];

            $totalAmount += $row[self::PAYMENT_ENTITY][Payment\Entity::AMOUNT] / 100;
        }

        // Adding this extra line, as this is present in prod MIS file for NB-axis, and
        // while parsing we skip the last line as defined in getNumLinesToSkip()
        $data[] = ['stats ' => 'TOTAL_NUMBER_OF_RECORDS : ' . count($input)];

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
            'gateway' => 'netbanking_axis'
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

    protected function getFileNametoWrite()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $fileName = 'razorpay mis report' . $date ;

        return $fileName;
    }
}
