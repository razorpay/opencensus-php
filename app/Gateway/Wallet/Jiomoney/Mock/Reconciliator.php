<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::WALLET_JIOMONEY;

        $this->fileExtension = FileStore\Format::CSV;

        $date = Carbon::now(
                Timezone::IST)
                ->format('dmY');

        $testMerchantId = '10000000000000';

        $this->fileToWriteName = 'Settlement_Details_'.$testMerchantId.'_'.$date;

        parent::__construct();
    }

    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED,
            Payment\Status::FAILED,
        ];

        return $this->repo
                    ->payment
                    ->fetchPaymentsWithStatus($createdAtStart, $createdAtEnd, $this->gateway, $statuses);
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
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    Timezone::IST)
                    ->format('d-M-y H:i:s');

            $col = [
                'Transaction Date'      => $date,
                'Merchant ID'           => $row['payment']['merchant_id'],
                'Terminal ID'           => $row['payment']['terminal_id'],
                'Card Number'           => '281232XXXXXX3245',
                'Merchant Ref ID'       => $row['payment']['id'],
                'Transaction ID'        => $row['wallet']['gateway_payment_id'],
                'Payment Instrument'    => 'JioMoney',
                'Transaction Type'      => 'Sale',
                'Payment Type'          => 'Purchase',
                'Gross Amount'          => $this->formatAmount($row['payment']['amount']),
                'Tran Com'              => '0.00',
                'IGST'                  => '0.00',
                'UGST'                  => '0.00',
                'CGST'                  => '0.00',
                'SGST'                  => '0.00',
                'Net Amount'            => $this->formatAmount($row['payment']['amount']),
                'Payment Status'        => 'Processed',
                'CMS Number'            => '',
            ];

            $this->content($col, 'col_jiomoney_recon');

            $data[] = $col;
        }

        return $data;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
