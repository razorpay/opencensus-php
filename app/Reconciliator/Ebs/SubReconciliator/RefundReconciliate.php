<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

class RefundReconciliate extends Base\RefundReconciliate
{
    // ----- Row header names -----
    const COLUMN_REFUND_ID          = 'merchant_ref_no';

    const COLUMN_REFUND_AMOUNT      = ['refunded', 'debit'];

    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
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
     * @return $paymentAmount integer
     */
    protected function getRefundAmount($row)
    {
        $columnRefundAmount = null;

        foreach ($self::COLUMN_REFUND_AMOUNT as $cra)
        {
            if (isset($row[$cra]) === true)
            {
                $columnRefundAmount = $cra;
                break;
            }
        }

        if ($columnServiceTax === null)
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
}
