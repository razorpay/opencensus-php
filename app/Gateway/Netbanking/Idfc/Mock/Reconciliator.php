<?php

namespace RZP\Gateway\Netbanking\Idfc\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Reconciliator\NetbankingIdfc\Constants;

class Reconciliator extends Base\RefundFile
{
    static protected $fileToWriteName = 'MER';

    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    const BASE_STORAGE_DIRECTORY = 'Idfc/Recon/Netbanking/';

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
        $data[] = Constants::COLUMN_HEADERS;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment'][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('d-M-Y H:i:s');

            $data[] = [
                Constants::RZP_PAYMENT_ID         => $row['payment']['id'],
                Constants::BANK_REFERENCE_NO      => $row['gateway']['bank_payment_id'] ?? '99999',
                Constants::TRANSACTION_AMOUNT     => $this->getFormattedAmount($row['payment']['amount']),
                Constants::STATUS                 => 'SUCCESS',
                Constants::TRANSACTION_DATE       => $date
            ];
        }

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
