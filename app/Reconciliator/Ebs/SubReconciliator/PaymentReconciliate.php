<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_KK_CESS            = 'krishi_kalyan_cess';
    const COLUMN_SB_CESS            = 'swachh_bharat_cess';
    const COLUMN_SERVICE_TAX        = 'service_tax';
    const COLUMN_BANK_REFERENCE_NO  = 'bank_reference';

    const COLUMN_FEE                = ['tdr', 'tdr_amt'];
    const COLUMN_PAYMENT_AMOUNT     = ['captured', 'credit'];
    const COLUMN_PAYMENT_ID         = ['merchant_ref_no', 'merchant_refno'];

    /**
     * Gets payment_id from row data
     *
     * @param $row array
     * @return $paymentId string
     */
    protected function getPaymentId($row)
    {
        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                $paymentId = $row[$cpi];

                return $paymentId;
            }
        }
    }

    /**
     * Gets amount captured.
     *
     * Since we get two type of sheets,
     * both have different column headers for payment amount.
     * We need to check which one of them is set
     * and get the payment amount accordingly.
     *
     * @param $row array
     * @return $paymentAmount integer
     */
    protected function getGatewayPaymentAmount($row)
    {
        $columnPaymentAmount = null;

        foreach (self::COLUMN_PAYMENT_AMOUNT as $cpa)
        {
            if (empty($row[$cpa]) === false)
            {
                $columnPaymentAmount = $cpa;
                break;
            }
        }

        if ($columnPaymentAmount === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_FAILURE,
                    'message'         => 'Unable to get payment amount!',
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            throw new ReconciliationException('Unable to get payment amount for EBS from the recon file.');
        }

        $paymentAmount = floatval($row[$columnPaymentAmount]) * 100;

        return abs($paymentAmount);
    }

    /**
     * Gets service tax levied by EBS
     *
     * Some files have KKC & SBC tax given separately
     * If they are present separately,
     * it means it's not added to the service tax.
     *
     * @param $row array
     * @return $serviceTax float
     */
    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        // Check for SB & KK Cess
        if (isset($row[self::COLUMN_SB_CESS]) === true)
        {
            $sbCess = floatval($row[self::COLUMN_SB_CESS]) * 100;

            $serviceTax += $sbCess;
        }

        if (isset($row[self::COLUMN_KK_CESS]) === true)
        {
            $kkCess = floatval($row[self::COLUMN_KK_CESS]) * 100;

            $serviceTax += $kkCess;
        }

        return abs(round($serviceTax));
    }

    /**
     * Gets TDR
     *
     * @param $row array
     * @return $fee float
     */
    protected function getGatewayFee($row)
    {
        $fee = null;

        foreach (self::COLUMN_FEE as $cf)
        {
            if (empty($row[$cf]) === false)
            {
                $fee = $row[$cf];
                break;
            }
        }

        // Convert fee into basic unit of currency (ex: paise)
        $fee = abs(floatval($fee)) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[self::COLUMN_BANK_REFERENCE_NO]) === true)
        {
            $referenceNumber = $row[self::COLUMN_BANK_REFERENCE_NO];

            return $referenceNumber;
        }
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getAmount() !== $this->getGatewayPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getAmount(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
