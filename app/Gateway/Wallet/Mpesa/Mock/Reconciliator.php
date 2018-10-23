<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::WALLET_MPESA;

        $this->fileExtension = FileStore\Format::XLS;

        $this->fileToWriteName = 'Transaction Report of 100000000000000';

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
                ->format('d-m-Y H:i:s');

            $col = [
                'm-pesa Txn ID'             => $row['wallet']['gateway_payment_id'],
                'Date and Time'             => $date,
                'Service Name'              => 'Online Payment',
                'Sender Name'               => 'ABC',
                'Sender Mobile No.'         => '9876543210',
                'Txn Type'                  => '',
                'm-pesa Txn Status'         => 'SUCCESS',
                'm-pesa Failure Reason'     => $row['wallet']['error_message'],
                'Reversal Txn Id'           => '',
                'Account ID'                => $row['wallet']['gateway_merchant_id'],
                'Customer ID'               => '',
                'Biller ID'                 => '',
                'Biller Name'               => '',
                'Partner Txn ID'            => $row['payment']['id'],
                'Partner Txn Status'        => '',
                'Partner Txn Comments'      => '',
                'Failure Codes'             => '',
                'Txn Amount (Rs.)'          => $this->formatAmount($row['payment']['amount'] / 100),
                'Balance (Rs.)'             => '10000000.00',
                'Beneficiary Nick Name'     => '',
                'Basic Charge (Rs.)'        => '3.00',
                'CGST'                      => '2.00',
                'SGST'                      => '0.00',
                'UTGST'                     => '0.00',
                'IGST'                      => '0.00',
                'Cess'                      => '0.00',
                'Total Charge Amount (Rs.)' => '0.00',
                'Total Amount (Rs.)'        => $this->formatAmount($row['payment']['amount'] / 100),
                'Convenience charges (Rs.)' => '0.00',
                'Parameter 2'               => '',
                'Parameter 3'               => '',
                'Parameter 4'               => '',
                'Parameter 5'               => '',
                'Reversal Method'           => '',
                'Parent m-pesa Txn ID'      => '',
                'Partner’s Refund Txn ID'   => '',
                'Metro Agent ID'            => '',
                'Channel Name'              => 'PARTNERPORTAL',
                'Agent category'            => '',
                'Agent sub category'        => '',
            ];

            $this->content($col, 'col_mpesa_recon');

            $data[] = $col;
        }

        return $data;
    }

    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->repo
                    ->payment
                    ->fetch([
                                'gateway' => $this->gateway,
                                'from'    => $createdAtStart,
                                'to'      => $createdAtEnd,
                            ]);
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
