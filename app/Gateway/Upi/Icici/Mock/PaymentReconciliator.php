<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class PaymentReconciliator extends Base\Mock\Reconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    private $headers = [
        'accountNumber',
        'merchantID',
        'merchantName',
        'subMerchantID',
        'subMerchantName',
        'merchantTranID',
        'bankTranID',
        'date',
        'time',
        'amount',
        'payerVA',
        'status',
        'Commission',
        'Service tax',
        'Net amount'
    ];

    /**
     * @override
     * @var string
     */
    protected $fileToWriteName = 'MIS_REPORT';

    /**
     * The parent class's method gets only successful payments,
     * but for sbi recon, we need all payments - both successful
     * and failed. This method accomplishes that.
     *
     * @override
     * @return PublicCollection
     */
    protected function getAllPaymentsToReconcile()
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
                '000205025290',
                '116798',
                'RAZORPAY',
                '116798',
                'Razorpay SUB',
                $row['payment']['id'],
                '734122607521',
                $date,
                '10:39 PM',
                $row['payment']['amount'] / 100,
                '9619218329@ybl',
                'SUCCESS',
                '0',
                '0',
                '0',
            ];

            $this->content($col, 'col_payment_icici_recon');

            $data[] = $col;
        }

        $emptyRow = array_fill(0, sizeof($this->headers), ' ');

        $headers = [$emptyRow, $this->headers];

        $data = array_merge($headers, $data);

        $this->content($data, 'icici_payment_recon');

        return $data;
    }

    /**
     * @override
     * @param array $data
     */
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $gatewayPayment = $this->repo->upi->fetchByPaymentId($data['payment']['id']);

        $data['gateway'] = $gatewayPayment->toArray();
    }
}
