<?php

namespace RZP\Gateway\CardlessEmi\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::CARDLESS_EMI;

        $this->fileExtension = FileStore\Format::CSV;

        $date = Carbon::now(
            Timezone::IST)
            ->format('d-m-Y');

        $this->fileToWriteName = 'Flexmoney_recon_file_' . $date;

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

        $data['cardless_emi'] = $this->repo->cardless_emi
            ->findByPaymentIdAndAction($payment['id'], 'authorize')
            ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'],
                Timezone::IST)->format('d-M-y H:i:s');

            $transAmount = $this->formatAmount($row['payment']['amount']);
            $flexmoneyMdr = $this->formatAmount($row['payment']['amount'])*0.016; //since this is 1.6 percent of the amount
            $flexmoneyMdrGst =  $flexmoneyMdr*0.18; //18 percent of flexmoneymdr
            $col = [
                'PG Transaction ID'         => $row['payment']['id'],
                'Flexpay Transaction ID'    => $row['cardless_emi']['gateway_reference_id'],
                'Transaction Amount'        => $transAmount,
                'Transaction Date'          => $date,
                'Lender ID'                 => '502',
                'Flexmoney MDR Share'       => $flexmoneyMdr,
                'GST on MDR'                => $flexmoneyMdrGst,
                'Settlement Value'          => $transAmount-$flexmoneyMdr-$flexmoneyMdrGst,
            ];

            $this->content($col, 'payment_recon');

            if (count($col) === 5)
            {
                $dt = Carbon::now(Timezone::IST)->format('d-m-Y');

                $this->fileToWriteName = 'Flexmoney_refunds_file_' . $dt;
            }

            $data[] = $col;
        }

        return $data;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
