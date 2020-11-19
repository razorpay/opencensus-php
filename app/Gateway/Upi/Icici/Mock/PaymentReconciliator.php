<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class PaymentReconciliator extends Base\Mock\PaymentReconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    /**
     * @override
     * @var string
     */
    protected $fileToWriteName = 'MIS_REPORT';

    /**
     * The name of the sheet to be processed is Refund MIS
     * @var string
     */
    protected $sheetName = 'Recon MIS';

    /**
     * The parent class's method gets only successful payments,
     * but for sbi recon, we need all payments - both successful
     * and failed. This method accomplishes that.
     *
     * @override
     * @return PublicCollection
     */
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

    protected function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/y');

            $col = [
                'accountNumber'   => '000205025290',
                'merchantID'      => '116798',
                'merchantName'    => 'RAZORPAY',
                'subMerchantID'   => '116798',
                'subMerchantName' => 'Razorpay SUB',
                'merchantTranID'  => $row['payment']['id'],
                'bankTranID'      => '734122607521',
                'date'            => $date,
                'time'            => '10:39 PM',
                'amount'          => $row['payment']['amount'] / 100,
                'payerVA'         => '9619218329@ybl',
                'status'          => 'SUCCESS',
                'Commission'      => '0',
                'Net amount'      => '0',
                'Service tax'     => '0',
            ];

            $this->content($col, 'col_payment_icici_recon');

            $data[] = $col;
        }

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
