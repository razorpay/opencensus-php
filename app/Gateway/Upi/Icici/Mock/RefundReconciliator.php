<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;

class RefundReconciliator extends Base\Mock\RefundReconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    /**
     * @override
     * @var string
     */
    protected $fileToWriteName = 'REFUND_REPORT';

    protected function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $col = [
                'merchantID'                      => '116798',
                'merchantName'                    => 'RAZORPAY',
                'subMerchantID'                   => '116798',
                'subMerchantName'                 => 'RAZORPAY',
                'MerchantTranID'                  => $row['refund']['id'],
                'Original Transaction date'       => $date,
                'Original Transaction Time'       => '08:06 AM',
                'Refund Transaction date'         => '04-12-2017',
                'Refund Transaction Time'         => '05:09 PM',
                'Refund Amount'                   => $row['refund']['amount'] / 100,
                'Original Bank RRN'               => $row['gateway']['gateway_payment_id'],
                'Customer VPA'                    => '9560658505@upi',
                'Reason for refund'               => 'Razorpay Refund ' . $row['refund']['id'],
                'Merchantaccount'                 => '205025290',
                'MerchantIFSCCode'                => 'ICIC0000002',
                'Customer Account Number'         => '00000020183413215',
                'Customer IFSC Code'              => 'SBIN0010441',
                'Type of Refund (Online/offline)' => 'ONLINE',
                'Refund RRN'                      => '733817298334',
                'Status'                          => 'SUCCESS',
            ];

            $this->content($col, 'col_icici_recon');

            $data[] = $col;
        }

        $this->content($data, 'icici_recon');

        return $data;
    }

    /**
     * @override
     * @param array $data
     */
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $gatewayPayment = $this->repo->upi->fetchByRefundId($data['refund']['id']);

        $data['gateway'] = $gatewayPayment->toArray();
    }
}
