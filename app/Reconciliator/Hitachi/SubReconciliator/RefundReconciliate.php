<?php

namespace RZP\Reconciliator\Hitachi;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID              = 'invoice_number';
    const COLUMN_ARN                    = 'arn';
    const COLUMN_AUTH_CODE              = 'auth_id';
    const COLUMN_FEE                    = 'fee_amount';
    const COLUMN_REFUND_AMOUNT          = 'amount';
    const COLUMN_ISSETTLED              = 'issettled';

    protected function getRefundId(array $row)
    {
        $refundId = null;
        
        // Unsettled rows should be skipped while processing.
        if ($row[self::COLUMN_ISSETTLED] !== 'S')
        {
            $this->trace->error(
                TraceCode::RECON_ALERT,
                [
                    'info_code' => 'UNSETTLED_ROW_FOUND',
                    'message'   => 'Unsettled row found. Skipping',
                    'row'       => $row,
                    'gateway'   => get_called_class()
                ]);

            $this->setFailUnprocessedRow(true);
        }
        else
        {
            $refundId = $row[self::COLUMN_REFUND_ID];
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayEntities = $this->repo->hitachi->findSuccessfulRefundByRefundId($refundId);

        if ($gatewayEntities->count() === 0)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_MISMATCH,
                    'message'       => 'Gateway refund not found.',
                    'refund_id'     => $refundId,
                    'gateway'       => get_called_class(),
               ]);

            return null;
        }

        $refundEntity = $gatewayEntities->first();

        return $refundEntity;
    }

    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        return $row[self::COLUMN_ARN];
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
