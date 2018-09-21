<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::WALLET_AMAZONPAY;

        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = '11933305459017751';

        parent::__construct();
    }

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $payment = $data['payment'];

        $data['wallet'] = $this->repo
                            ->wallet
                            ->findByPaymentIdAndAction($payment['id'], 'authorize')
                            ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $data = $this->getAdditionalRowsToSkip();

        $keys = [
            'TransactionPostedDate'     ,
            'SettlementId'              ,
            'AmazonTransactionId'       ,
            'SellerReferenceId'         ,
            'TransactionType'           ,
            'AmazonOrderReferenceId'    ,
            'SellerOrderId'             ,
            'StoreName'                 ,
            'CurrencyCode'              ,
            'TransactionDescription'    ,
            'TransactionAmount'         ,
            'TransactionPercentageFee'  ,
            'TransactionFixedFee'       ,
            'TotalTransactionFee'       ,
            'NetTransactionAmount'      ,
        ];

        $data[] = $keys;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('y-M-dTH:i:s +0000');

            $col = [
                $date,
                '55500008822',
                '',
                $row['wallet']['gateway_payment_id'],
                'Capture',
                $row['wallet']['gateway_payment_id'],
                $row['payment']['id'],
                'XYZ Store',
                $row['payment']['currency'],
                'XYZ Store',
                $this->formatAmount($row['payment']['amount'] / 100),
                '0',
                '0',
                '0',
                $this->formatAmount($row['payment']['amount'] / 100),
            ];

            $col = array_combine($keys, $col);

            $this->content($col, 'col_payment_amazonpay_recon');

            $data[] = $col;
        }

        return $this->generateText($data, ',');
    }

    public function getAdditionalRowsToSkip()
    {
        $settlementStartDate = Carbon::yesterday(Timezone::IST)->format('y-M-dTH:i:s +0000');
        $settlementEndDate   = Carbon::now(Timezone::IST)->format('y-M-dTH:i:s +0000');

        $data[] = ['Amazon Payments Advanced'];
        $data[] = ['Settlement Report'];

        $data[] = [
            'SellerId',
            'A3MJ8VJGR6SLBL'
        ];

        $data[] = [
            'SettlementStartDate',
            $settlementStartDate
        ];

        $data[] = [
            'SettlementEndDate',
            $settlementEndDate
        ];

        return $data;
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
