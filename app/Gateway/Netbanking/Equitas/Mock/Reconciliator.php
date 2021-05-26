<?php

namespace RZP\Gateway\Netbanking\Equitas\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Reconciliator\NetbankingEquitas\Constants;

class Reconciliator extends Base\RefundFile
{
    protected static $fileToWriteName = 'Equitas_Netbanking_Reconciliation';

    const ACCOUNT_NUMBER = '309002069863';

    const BASE_STORAGE_DIRECTORY = 'Equitas/Recon/Netbanking/';

    public function generateReconciliation($input = null)
    {
        $input = [
            'gateway' => 'netbanking_equitas'
        ];

        $payments = $this->repo->payment->fetch($input, '10000000000000');

        $inputData = [];

        foreach ($payments as $payment)
        {
            $data['payment'] = $payment->toArray();

            $gatewayInput['payment_id'] = $payment[Payment\Entity::ID];

            $gatewayPayment = $this->repo->netbanking->fetch($gatewayInput);

            $data['gateway'] = $gatewayPayment[0]->toArray();

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    public function generate($input)
    {
        $data = $this->getReconciliationData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $txt = $this->generateText($data, ',');

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::EQUITAS_NETBANKING_REFUND
        );

        $file = $creator->get();

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
        ];
    }

    protected function getReconciliationData($input)
    {
        $data[] = Constants::COLUMN_HEADERS;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment'][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('d-m-y');

            $col = [
                Constants::GATEWAY_REFERENCE_NUMBER     => $row['gateway']['payment_id'],
                Constants::BANK_TRANSACTION_ID          => '99999',
                Constants::AMOUNT                       => $this->getFormattedAmount($row['payment']['amount']),
                Constants::STATUS                       => Constants::PAYMENT_STATUS_SUCCESS,
                Constants::DATE_OF_TRANSACTION          => $date,
                Constants::ACCOUNT_NUMBER               => self::ACCOUNT_NUMBER,
            ];

            $this->content($col, 'equitas_recon');

            $data[] = $col;
        }

        return $data;
    }

    public function content(& $content, $action = null)
    {
        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
