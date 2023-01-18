<?php

namespace RZP\Reconciliator\PayZappV2\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    /*******************
     * Row Header Names
     *******************/
    const COLUMN_SERVICE_TAX        = ['cgst_amt', 'sgst_amt', 'igst_amt', 'utgst_amt', 'serv_tax'];
    const COLUMN_GATEWAY_FEE        = 'msf';
    const COLUMN_BANK_REF_NUMBER    = 'tran_id';
    const COLUMN_PAYMENT_AMOUNT     = 'domestic_amt';
    const COLUMN_PAYMENT_ID         = 'merchant_trackid';
    const COLUMN_UPI_PAYMENT_ID     = 'order_id';
    const COLUMN_UPI_GATEWAY_FEE    = 'msf_amount';
    const COLUMN_UPI_PAYMENT_AMOUNT = 'transaction_amount';
    const COLUMN_UPI_RRN            = 'txn_ref_no_rrn';

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? $row[self::COLUMN_UPI_PAYMENT_ID];
    }

    protected function getReconPaymentAmount(array $row): int
    {
        return Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT] ?? $row[self::COLUMN_UPI_PAYMENT_AMOUNT]);
    }

    protected function getGatewayServiceTax($row): int
    {
        // Convert service tax and GST into paise
        $serviceTax = 0;

        foreach (self::COLUMN_SERVICE_TAX as $tax)
        {
            if (isset($row[$tax]) === true)
            {
                $serviceTax += Helper::getIntegerFormattedAmount($row[$tax]);
            }
        }

        return $serviceTax;
    }

    protected function getGatewayFee($row): int
    {
        $fee = 0;

        $feeColumn = $row[self::COLUMN_GATEWAY_FEE] ?? $row[self::COLUMN_UPI_GATEWAY_FEE];

        $fee += Helper::getIntegerFormattedAmount($feeColumn);

        $fee += $this->getGatewayServiceTax($row);

        return round($fee);
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_REF_NUMBER] ?? $row[self::COLUMN_UPI_RRN];
    }
}
