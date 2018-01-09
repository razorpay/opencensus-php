<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Mock\Reconciliator;

class RefundReconciliator extends Reconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    private $refundHeaders = [
        'merchantID',
        'merchantName',
        'subMerchantID',
        'subMerchantName',
        'MerchantTranID',
        'Original Transaction date',
        'Original Transaction Time',
        'Refund Transaction date',
        'Refund Transaction Time',
        'Refund Amount',
        'Original Bank RRN',
        'Customer VPA',
        'Reason for refund',
        'Merchantaccount',
        'MerchantIFSCCode',
        'Customer Account Number',
        'Customer IFSC Code',
        'Type of Refund (Online/offline)',
        'Refund RRN',
        'Status',
    ];

    /**
     * @override
     * @var string
     */
    protected $fileToWriteName = 'REFUND_REPORT';

    public function generateReconciliation(array $input)
    {
        $refunds = $this->getAllRefundsToReconcile();

        $inputData = [];

        foreach ($refunds as $refund)
        {
            $data['refund'] = $refund->toArray();

            $this->addGatewayEntityIfNeeded($data, $refund);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

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
                '116798',
                'RAZORPAY',
                '116798',
                'RAZORPAY',
                $row['refund']['id'],
                $date,
                '08:06 AM',
                '04-12-2017',
                '05:09 PM',
                $row['refund']['amount'] / 100,
                $row['gateway']['gateway_payment_id'],
                '9560658505@upi',
                'Razorpay Refund ' . $row['refund']['id'],
                '205025290',
                'ICIC0000002',
                '00000020183413215',
                'SBIN0010441',
                'ONLINE',
                '733817298334',
                'SUCCESS',
            ];

            $this->content($col, 'col_icici_recon');

            $data[] = $col;
        }

        $emptyRow = array_fill(0, sizeof($this->refundHeaders), ' ');

        $headers = [$emptyRow, $this->refundHeaders];

        $data = array_merge($headers, $data);

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