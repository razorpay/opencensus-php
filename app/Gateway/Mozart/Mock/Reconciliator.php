<?php

namespace RZP\Gateway\Mozart\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\WalletPhonepe;
use RZP\Gateway\Mozart\NetbankingSib;
use RZP\Gateway\Mozart\NetbankingYesb;
use RZP\Gateway\Mozart\NetbankingCub;
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

    protected function netbanking_yesb($input)
    {
        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        for ($i = 0; $i < 5; $i++)
        {
            $data[] = [];
        }

        $data[] = NetbankingYesb\ReconFields::RECON_FIELDS;

        $data[] = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $col = [
                NetbankingYesb\ReconFields::MERCHANT_CODE      => 'test_merchant',
                NetbankingYesb\ReconFields::CLIENT_CODE        => 'RAZORPAY',
                NetbankingYesb\ReconFields::PAYMENT_ID         => $row['payment']['id'],
                NetbankingYesb\ReconFields::TRANSACTION_DATE   => $date,
                NetbankingYesb\ReconFields::AMOUNT             => $row['payment']['amount'] / 100,
                NetbankingYesb\ReconFields::SERVICE_CHARGES    => '0',
                NetbankingYesb\ReconFields::BANK_REFERENCE_ID  => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
                NetbankingYesb\ReconFields::TRANSACTION_STATUS => NetbankingYesb\Constants::RECON_STATUS_SUCCESS,
            ];

            $this->content($col, 'col_payment_yesb_nb_recon');

            $data[] = $col;
        }

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
                NetbankingSib\ReconFields::TRANSACTION_DATE      => $date,
                NetbankingSib\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingSib\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingSib\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
            ];

            $this->content($col, 'col_payment_sib_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    protected function netbanking_cub($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'RAZORPAY_2019May';

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $col = [
                NetbankingCub\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingCub\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingCub\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
                NetbankingCub\ReconFields::PAYMENT_DATE          => $date,
            ];

            $this->content($col, 'col_payment_cub_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, ',');

        return $formattedData;
    }

    protected function wallet_phonepe($input)
    {
        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-m-Y');

            $col = [
                WalletPhonepe\ReconFields::PAYMENT_TYPE         => 'PAYMENT',
                WalletPhonepe\ReconFields::RZP_ID               => $row['payment']['id'],
                WalletPhonepe\ReconFields::ORDER_ID             => $row['payment']['id'],
                WalletPhonepe\ReconFields::PHONEPE_ID           => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'providerReferenceId'),
                WalletPhonepe\ReconFields::FROM                 => $date,
                WalletPhonepe\ReconFields::CREATION_DATE        => $date,
                WalletPhonepe\ReconFields::TRANSACTION_DATE     => $date,
                WalletPhonepe\ReconFields::SETTLEMENT_DATE      => $date,
                WalletPhonepe\ReconFields::BANK_REFERENCE_NO    => 'N0000012345',
                WalletPhonepe\ReconFields::AMOUNT               => $row['payment']['amount']/100,
                WalletPhonepe\ReconFields::FEE                  => '0',
                WalletPhonepe\ReconFields::IGST                 => '0',
                WalletPhonepe\ReconFields::CGST                 => '0',
                WalletPhonepe\ReconFields::SGST                 => '0',
            ];

            $this->content($col, 'col_payment_wallet_phonepe_recon');

            $data[] = $col;
        }

        return $data;
    }

    public function generateReconciliation(array $input)
    {
        $this->gateway = $input['gateway'];

        return parent::generateReconciliation($input);
    }

    protected function fetchFieldFromJsonData($data, $field)
    {
        $dataArray = json_decode($data, true);

        return $dataArray[$field];
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
             return parent::createFile($content, $type, $store);
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

    protected function getEntitiesToReconcile()
    {
        return $this->repo
                    ->payment
                    ->fetch(['gateway' => $this->gateway]);
    }
}
