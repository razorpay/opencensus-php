<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\NetbankingSib;
use RZP\Models\Payment\Gateway as PaymentGateway;


class Reconciliator extends Base\Mock\PaymentReconciliator
{
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $payment = $data['payment'];

        $data['mozart'] = $this->repo->mozart->findByPaymentIdAndAction($payment['id'], 'authorize')->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $gateway = $this->gateway;

        $data = $this->$gateway($input);

        return $data;
    }

    protected function netbanking_sib($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'trn-' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $col = [
                NetbankingSib\ReconFields::TRANSACTION_DATE     => $date,
                NetbankingSib\ReconFields::PAYMENT_ID           => $row['payment']['id'],
                NetbankingSib\ReconFields::PAYMENT_AMOUNT       => $row['payment']['amount'] / 100,
               NetbankingSib\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchBankPaymentId($row['mozart']['raw']),
            ];

            $this->content($col, 'col_payment_yesb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    public function generateReconciliation(array $input)
    {
        $this->gateway = $input['gateway'];

        parent::generateReconciliation($input);
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
    }

    //Overriding this base class create file as it creates and excel file
    protected function createFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        if ($this->gateway === PaymentGateway::NETBANKING_SIB)
        {
            return $this->createTxtFile($content, $type, $store);
        }
        else
        {
            parent::createFile($content, $type, $store);
        }
    }

    protected function createTxtFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($this->fileExtension)
            ->content($content)
            ->name($this->fileToWriteName)
            ->store($store)
            ->type($type)
            ->save();

        return $creator;
    }
}
