<?php

namespace RZP\Gateway\Mozart\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\WalletPhonepe;
use RZP\Gateway\Mozart\NetbankingScb;
use RZP\Gateway\Mozart\NetbankingSib;
use RZP\Gateway\Mozart\NetbankingCbi;
use RZP\Gateway\Mozart\NetbankingYesb;
use RZP\Gateway\Mozart\NetbankingKvb;
use RZP\Gateway\Mozart\NetbankingIbk;
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

        for ($i = 0; $i < 4; $i++)
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

    protected function netbanking_kvb($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        for ($i = 0; $i < 2; $i++)
        {
            $data[] = [];
        }

        $data[] = NetbankingKvb\ReconFields::RECON_FIELDS;

        $count = 1;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-Y');

            $date = strtoupper($date);

            $col = [
                NetbankingKvb\ReconFields::SR_NO                 => $count++,
                NetbankingKvb\ReconFields::MERCHANT_CODE         => 'RAZORPAY',
                NetbankingKvb\ReconFields::TRANSACTION_DATE      => $date,
                NetbankingKvb\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingKvb\ReconFields::ACCOUNT_NUMBER        => '12345',
                NetbankingKvb\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingKvb\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                    $row['mozart']['raw'],
                    'bank_payment_id'),
            ];

            $this->content($col, 'col_payment_kvb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    protected function netbanking_ibk($input)
    {
        $this->fileExtension = FileStore\Format::TXT;
        $this->fileToWriteName = 'RAZORPAY_2019May';
        $data = [];
        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/y');
            $col = [
                NetbankingIbk\ReconFields::PID                  => $row['payment']['id'],
                NetbankingIbk\ReconFields::BILLER_NAME          => 'xyz',
                NetbankingIbk\ReconFields::DATE_TIME            => $date,
                NetbankingIbk\ReconFields::MERCHANT_REF_NO      => $date,
                NetbankingIbk\ReconFields::AMOUNT               => $row['payment']['amount'] / 100,
                NetbankingIbk\ReconFields::CUSTOMER_NO          => $date,
                NetbankingIbk\ReconFields::DATE_BANK            => $date,
                NetbankingIbk\ReconFields::BANK_REF_NO          => $this->fetchFieldFromJsonData($row['mozart']['raw'],'bank_payment_id'),
                NetbankingIbk\ReconFields::JOURNAL_NO           => "900322626",
                NetbankingIbk\ReconFields::PAID_STATUS          => "Y",
            ];
            $this->content($col, 'col_payment_ibk_nb_recon');
            $data[] = $col;
        }
        $formattedData = $this->generateText($data, '^');
        return $formattedData;
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


    protected function netbanking_scb($input)
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
                NetbankingScb\ReconFields::BANK_TRANSACTION_ID          => $this->fetchFieldFromJsonData(
                                                                           $row['mozart']['raw'],
                                                                     'bank_payment_id'),
                NetbankingScb\ReconFields::PAYMENT_ID                   => $row['payment']['id'],
                NetbankingScb\ReconFields::BANK_PAYMENT_ID              => $this->fetchFieldFromJsonData(
                                                                           $row['mozart']['raw'],
                                                                     'bank_payment_id'),
                NetbankingScb\ReconFields::NORTHAKROSS_TRANSACTION_ID   => '1234',
                NetbankingScb\ReconFields::AMOUNT                       => $row['payment']['amount'] / 100,
                NetbankingScb\ReconFields::DATE                         => $date,

            ];

            $this->content($col, 'col_payment_scb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    protected function netbanking_cbi($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'DailyRecon-' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('Ymd');

            $col = [
                NetbankingCbi\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingCbi\ReconFields::BANK_REFERENCE_NUMBER =>
                    $this->fetchFieldFromJsonData(
                        $row['mozart']['raw'],
                        'bank_payment_id'),
                NetbankingCbi\ReconFields::AMOUNT                => $row['payment']['amount'] / 100,
                NetbankingCbi\ReconFields::STATUS                => 'Y',
                NetbankingCbi\ReconFields::DATE                  => $date,
                NetbankingCbi\ReconFields::ACCOUNT_NUMBER        => 'HS-123456789',
                NetbankingCbi\ReconFields::ACCOUNT_TYPE          => '01',
            ];

            $this->content($col, 'col_payment_cbi_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '^');

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

    protected function bajajfinserv($input)
    {
        $dt = Carbon::now()->format('dM_Y');

        $this->fileToWriteName = 'Payment_MIS_Razorpay_' . $dt;

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('j-M-y');

            $gatewayData = json_decode($row['mozart']['raw'], true);

            $col = [
                'Dealer ID'                           => $row['payment']['id'],
                'Type of Txn'                         => 'Sale',
                'RRN'                                 => $gatewayData['DealID'],
                'Transaction Date'                    => 'Sale',
                'Disbursement Date'                   => $date,
                'Amount Financed (Rs) '               => (string)$row['payment']['amount'] / 100,
                'Scheme Desc'                         => '123445',
                'Interest Subsidy including GST (Rs)' => 2233,
                'Interest Subsidy (%)+GST'            => '6.00%',
                'Net Disb. Amount (Rs)'               => 2233,
                'UTR No'                              => '911082787695',
                'Asset Serial Number/IMEI'            => $row['payment']['id'],
            ];

            $this->content($col, 'col_payment_bfl_recon');

            $data[] = $col;
        }
        return $data;
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
