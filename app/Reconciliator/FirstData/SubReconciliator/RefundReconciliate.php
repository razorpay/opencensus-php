<?php

namespace RZP\Reconciliator\FirstData\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /******************
     * Row Header Names
     ******************/

    const GATEWAY_TRANSACTION_ID = 'ft_no';
    const COLUMN_RZP_ENTITY_ID   = 'session_id_aspd';
    const COLUMN_REFUND_AMOUNT   = 'transaction_amt';
    const COLUMN_ARN             = 'arn_no';

    /**
     * Refund id has been set in rzp_entity_id
     * column, while preprocessing in reconciliate.
     *
     * @param array   $row
     * @return string $refundId
     */
    protected function getRefundId($row)
    {
        return $row[self::COLUMN_RZP_ENTITY_ID];
    }

    /**
     * Fetches refund amount from the file.
     * It is negative for refunds, so we will be taking abs value
     *
     * @param array    $row
     * @return integer $refundAmount
     */
    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = parent::getReconRefundAmount($row);

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue

        $refundAmount = intval(number_format($refundAmount, 2, '.', ''));

        $refundAmount = abs($refundAmount);

        return $refundAmount;
    }

    /**
     * Fetches ARN for given rows
     *
     * @param array   $row
     * @return string $arn
     */
    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        $arn = $row[self::COLUMN_ARN];

        return $arn;
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
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
