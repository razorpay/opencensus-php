<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Amazonpay\ReconHeaders;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::WALLET_AMAZONPAY;

        $this->fileExtension = FileStore\Format::CSV;

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
        $keys = ReconHeaders::COLUMN_HEADERS;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST);

            $col = [
                "A3MJ8VJGR6SLBL",
                "RAZORPAY",
                "{$date->format('d/m/Y')}",
                "{$date->format('H:m:s')}",
                "{$row['wallet']['gateway_payment_id']}",
                "{$row['payment']['id']}",
                "{$row['wallet']['gateway_payment_id']}" . str_random(5),
                "AuthRef_" . "{$row['wallet']['gateway_payment_id']}",
                'Capture',
                'XYZ Store',
                '',
                '',
                "{$date->format('d/m/Y')}",
                "{$date->format('H:m:s')}",
                "{$row['payment']['currency']}",
                'XYZ Store',
                '123456',
                "{$this->formatAmount($row['payment']['amount'] / 100)}",
                '0',
                '0',
                "{$this->formatAmount($row['payment']['amount'] / 100)}",
                "{$this->formatAmount($row['payment']['amount'] / 100)}",
                '897878',
                '812980',
            ];

            $col = array_combine_pad($keys, $col);

            $this->content($col, 'col_payment_amazonpay_recon');

            $data[] = $col;
        }

        return $data;
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
        return number_format($amount, 2, '.', ',');
    }
}
