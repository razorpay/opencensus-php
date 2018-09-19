<?php

namespace RZP\Gateway\Netbanking\Idfc\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Reconciliator\NetbankingIdfc\Headers;

class Reconciliator extends Base\RefundFile
{
    static protected $fileToWriteName = 'MER';

    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    public function generateReconciliation($input = null)
    {
        $payments = $this->repo->payment->fetch($input, '10000000000000');

        $inputData = [];

        foreach ($payments as $payment)
        {
            $data['payment'] = $payment->toArray();

            $gatewayInput['payment_id'] = $payment[Payment\Entity::ID];

            $gatewayPayment = $this->repo->netbanking->fetch($gatewayInput);

            $data[self::GATEWAY_ENTITY] = $gatewayPayment[0]->toArray();

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    public function generate($input)
    {
        $data = $this->getReconciliationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, '|');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::IDFC_NETBANKING_REFUND
        );

        $file = $creator->get();

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
        ];
    }

    public function getReconciliationData($input)
    {
        $data[] = Headers::COLUMN_HEADERS;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment'][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('Y-m-d H:i:s');

            $data[] = [
                Headers::TXN_INITIATE_DATE_TIME   => $date,
                Headers::RIB_TXN_ID               => $row['gateway']['bank_payment_id'],
                Headers::MERCHANT_ID              => '10000000000000',
                Headers::MERCHANT_NAME            => 'RZP',
                Headers::CUST_ACC_NO              => '1234567890',
                Headers::ECOMM_ACC_NO             => '0987654321',
                Headers::TXN_AMT                  => $this->getFormattedAmount($row['payment']['amount']),
                Headers::SERVICE_CHARGE           => '0.0',
                Headers::SERVICE_TAX              => '0.0',
                Headers::COMMISSION               => '0.0',
                Headers::PAYMENT_TYPE             => 'E-commerce',
                Headers::TXN_COMPLETION_DATE_TIME => $date,
                Headers::RIB_TXN_STATUS           => 'Main Fund Transfer Successiated',
                Headers::BANK_REFERENCE_NUMBER    => mt_rand(11111, 99999),
                Headers::AGGREGATOR_REF_NO        => $row['payment']['id'],
                Headers::PAYMENT_STATUS           => 'SUCCESS',
                Headers::ERROR_CODE               => '',
                Headers::ERROR_MSG                => '',
            ];
        }

        return $data;
    }
}
