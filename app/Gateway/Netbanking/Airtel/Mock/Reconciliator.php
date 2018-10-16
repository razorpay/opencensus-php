<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Payment\Gateway::NETBANKING_AIRTEL;

        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'ECOM_Merch_Txn_Report_' . Carbon::now(Timezone::IST)->format('dmY');

        parent::__construct();
    }

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $payment = $data['payment'];

        $data['netbanking'] = $this->repo
                                   ->netbanking
                                   ->findByPaymentIdAndAction($payment['id'], 'authorize')
                                   ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $data = [];

        $i = 1;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $col = [
                'SNO'                       => $i++,
                'DATE_AND_TIME'             => $date,
                'TRANSACTION_ID'            => $row['netbanking']['bank_payment_id'],
                'CUSTOMER_MOBILE_NO'        => '9876543210',
                'CUSTOMER_CATEGORY'         => '',
                'PARTNER_TXN_ID'            => $row['payment']['id'],
                'ORIGINAL_INPUT_AMT'        => $row['payment']['amount'] / 100,
                'COMMISION_DR_'             => '0',
                'COMMISION_CR_'             => '0',
                'UGST_DR_'                  => '0',
                'UGST_CR_'                  => '0',
                'IGST_DR_'                  => '0',
                'IGST_CR_'                  => '0',
                'CGST_DR_'                  => '0',
                'CGST_CR_'                  => '0',
                'SGST_DR_'                  => '0',
                'SGST_CR_'                  => '0',
                'TDS_DR_'                   => '0',
                'TDS_CR_'                   => '0',
                'GDS_DR_'                   => '0',
                'GDS_CR_'                   => '0',
                'NET_AMOUNT_PAYABLE_DR_'    => '0',
                'NET_AMOUNT_PAYABLE_CR_'    => $row['payment']['amount'] / 100,
                'MERCHANT_STATE'            => 'KARNATAKA',
                'COUNTERPARTY_STATE'        => 'JHARKHAND',
                'STORE_ID'                  => '3',
                'TILL_ID'                   => 'RAZORPAY',
                'REF_TXN_NO_ORG'            => '',
                'TRANSACTION_STATUS'        => 'Sale',
                'MERCHANT_MSISDN'           => '1100003333',
                'MERCHANT_ID'               => '11223344',
                'MERCHANT_ACCOUNT_NUMBER'   => '1022754423',
                'MERCHANT_NAME'             => 'RAZORPAY SOFTWARE PVT LTD',
                'MERCHANT_SETTLEMENT_TYPE'  => '2',
            ];

            $this->content($col, 'col_payment_airtelnb_recon');

            $data[] = $col;
        }

        return $data;
    }
}
