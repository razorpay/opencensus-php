<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = ['merchant_ref_no', 'merchant_refno'];
    const COLUMN_REFUND_AMOUNT      = ['refunded', 'debit'];

    protected function getRefundId($row)
    {
        foreach (self::COLUMN_REFUND_ID as $cri)
        {
            if (isset($row[$cri]) === true)
            {
                $paymentId = $row[$cri];

                return $paymentId;
            }
        }
    }

    protected function getPaymentId($row)
    {
        $paymentId = $this->getRefundId($row);

        return $paymentId;
    }

    /**
     * Gets amount refunded/debited.
     *
     * Since we get two type of sheets,
     * both have different column headers for refund amount.
     * We need to check which one of them is set
     * and get the refund amount accordingly.
     *
     * @param $row array
     *
     * @return float|int|null $paymentAmount
     * @throws ReconciliationException
     */
    protected function getReconRefundAmount(array $row)
    {
        $columnRefundAmount = null;

        foreach (self::COLUMN_REFUND_AMOUNT as $cra)
        {
            if (isset($row[$cra]) === true)
            {
                $columnRefundAmount = $cra;
                break;
            }
        }

        if ($columnRefundAmount === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_FAILURE,
                    'message'         => 'Unable to get the refund amount!',
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            throw new ReconciliationException('Unable to get refund amount for EBS from the recon file.');
        }

        $paymentAmount = floatval($row[$columnRefundAmount]) * 100;

        return abs($paymentAmount);
    }

    /**
     * Checks if refund amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund amount mismatch',
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'currency'          => $this->refund->getCurrency(),
                    'row'               => $row,
                    'gateway'           => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
