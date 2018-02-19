<?php

namespace RZP\Gateway\Wallet\Payumoney\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    /**
     * @override
     * @var string
     */
    protected $gateway = Payment\Gateway::WALLET_PAYUMONEY;

    protected $fileToWriteName = 'Payu Money Recon';

    protected $fileExtension = FileStore\Format::CSV;

    protected function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $settledAt = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)->addMinutes(30)
                ->format('d-M-y H:i:s');

            $col = [
                'Payment Id'              => $row['payment']['id'],
                'Amount'                  => $row['payment']['amount'] / 100,
                'AddedOn Date'            => $date,
                'SucceededOn Date'        => $date,
                'Merchant Transaction ID' => "'" . $row['payment']['id'],
                'Customer Name'           => 'sadhvidhawan',
                'Customer Email'          => 'sadhvi_dhawan@hotmail.com',
                'Customer Phone'          => '9899510818',
                'Payment Status'          => 'Settlement in Process',
                'Settlement Amount'       => $row['payment']['amount'] / 100 - 30,
                'Settlement Date'         => $settledAt,
                'PayUMoney TDR Charges'   => '',
                'Service Tax'             => '23.45',
                'Convenience Fee Charges' => 0,
                'Payment Mode'            => 'WALLET',
                'Product Info'            => '',
                'Payment Source'          => 'extusewallet',
                'payment parts'           => '{"Convenience Fee":0.0}',
                'payee info'              => '{}',
                'UTR number'              => '',
                'udf1'                    => '',
                'udf2'                    => '',
                'udf3'                    => '',
                'udf4'                    => '',
                'udf5'                    => '',
                'Error Message'           => 'null'
            ];

            $this->content($col, 'col_payment_payu_recon');

            $data[] = $col;
        }

        $this->content($data, 'payu_recon');

        return $data;
    }

    /**
     * The parent class's method gets only successful payments,
     * but for payu recon, we need all payments - both successful
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
}
