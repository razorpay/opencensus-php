<?php

namespace RZP\Reconciliator\Freecharge\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID      = 'order_id';
    const COLUMN_SERVICE_TAX     = 'service_tax';
    const COLUMN_FEE             = 'fee';
    const COLUMN_PAYMENT_AMOUNT  = 'total_amount';


    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = $row[self::COLUMN_SERVICE_TAX] ?? null;

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        //
        // This should be isset not empty as fee can be 0 also.
        //
        if (isset($row[self::COLUMN_FEE]) === false)
        {
            $this->reportMissingColumn($row, self::COLUMN_FEE);

            return null;
        }

        //
        // The fee is provided as a separate column.
        //
        $fee = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);
        $tax = $this->getGatewayServiceTax($row);

        return $fee + $tax;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_AMOUNT]) === true)
        {
            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
    }


    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
