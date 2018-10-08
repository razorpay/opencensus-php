<?php

namespace RZP\Gateway\Wallet\Payzapp\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Wallet\Payzapp\ReconHeaders;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::WALLET_PAYZAPP;

        $this->fileExtension = FileStore\Format::CSV;

        $date = Carbon::now(Timezone::IST)->format('dmy');

        $this->fileToWriteName = 'Razorpay_Software_detailed_report_GST_'.$date;

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
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('Y-m-d H:i:s.u');

            $col = [
                ReconHeaders::TERMINAL_ID               => '12345678',
                ReconHeaders::MERCHANT_NAME             => 'Razorpay',
                ReconHeaders::TRANSACTION_TYPE          => 'Sale',
                ReconHeaders::CARD_NUMBER               => '486218XXXXXX5372',
                ReconHeaders::GROSS_AMT                 => $this->formatAmount($row['payment']['amount'] / 100),
                ReconHeaders::COMMISSION_AMT            => '0.0',
                ReconHeaders::CGST                      => '1.0',
                ReconHeaders::SGST                      => '2.0',
                ReconHeaders::IGST                      => '3.0',
                ReconHeaders::UTGST                     => '4.0',
                ReconHeaders::NET_AMT                   => $this->formatAmount($row['payment']['amount'] / 100),
                ReconHeaders::TRAN_DATE                 => $date,
                ReconHeaders::AUTH_CODE                 => 'AX9W13',
                ReconHeaders::TRACK_ID                  => $row['payment']['id'],
                ReconHeaders::PG_TXN_ID                 => '152463732',
                ReconHeaders::PG_SALE_ID                => $row['wallet']['gateway_payment_id_2'],
                ReconHeaders::CREDIT_DEBIT_CARD_FLAG    => 'D',
                ReconHeaders::GSTN                      => '29AHRCC8362ZUDJ',
                ReconHeaders::INVOICE_NUMBER            => '',
                ReconHeaders::CGST_PERCENTAGE           => '0',
                ReconHeaders::SGST_PERCENTAGE           => '0',
                ReconHeaders::IGST_PERCENTAGE           => '0',
                ReconHeaders::UTGST_PERCENTAGE          => '0',
                ReconHeaders::CGSTCESS1                 => '',
                ReconHeaders::CGSTCESS2                 => '',
                ReconHeaders::CGSTCESS3                 => '',
                ReconHeaders::SGSTCESS1                 => '',
                ReconHeaders::SGSTCESS2                 => '',
                ReconHeaders::SGSTCESS3                 => '',
                ReconHeaders::IGSTCESS1                 => '',
                ReconHeaders::IGSTCESS2                 => '',
                ReconHeaders::IGSTCESS3                 => '',
                ReconHeaders::UTGSTCESS1                => '',
                ReconHeaders::UTGSTCESS2                => '',
                ReconHeaders::UTGSTCESS3                => '',
            ];

            $this->content($col, 'col_payment_payzapp_recon');

            $data[] = $col;
        }

        return $data;
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
