<?php

namespace RZP\Reconciliator\Mobikwik\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID     = 'orderid';
    const COLUMN_SERVICE_TAX    = 'servicetax';
    const COLUMN_IGST           = 'igst';
    const COLUMN_FEE            = 'fee';
    const COLUMN_PAYMENT_AMOUNT = 'txnamount';

    /**
     * Gets payment_id from row data
     *
     * @param array $row
     * @return string|null
     */
    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];

            $paymentId = trim(str_replace('"', '', $paymentId));
        }

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * @param $row array
     *
     * @return int $paymentAmount
     */
    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
    }

    /**
     * Mobikwik gives something like 45.56738 as service tax
     *
     * @param  $row array
     * @return $serviceTax float
     */
    protected function getGatewayServiceTax($row)
    {
        $igst = $this->getIgst($row);

        // Convert service tax into paise
        $serviceTax = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_SERVICE_TAX]);

        $serviceTax += $igst;

        return $serviceTax;
    }

    protected function getIgst($row)
    {
        $columnIgst = null;

        //
        // This should be isset only and not empty
        // because igst can be 0 also.
        //
        if (isset($row[self::COLUMN_IGST]) === true)
        {
            $columnIgst = $row[self::COLUMN_IGST];
        }

        $igst = Base\SubReconciliator\Helper::getIntegerFormattedAmount($columnIgst);

        return $igst;
    }

    /**
     * Mobikwik reconciliation files have fee and service tax separately
     * Round off because service tax is like 5.56731
     *
     * @param  $row array
     * @return $fee float
     */
    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return $fee;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
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
