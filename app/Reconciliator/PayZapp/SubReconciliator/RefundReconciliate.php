<?php

namespace RZP\Reconciliator\PayZapp\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'track_id';
    const COLUMN_REFUND_AMOUNT      = 'gross_amt';
    const COLUMN_GATEWAY_REFUND_ID  = 'pg_txn_id';

    protected function getRefundId($row)
    {
        if (isset($row[self::COLUMN_REFUND_ID]) === false)
        {
            return null;
        }

        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function createRefundOnApi(array $row, string $refundId, \Exception $ex)
    {
        return false;
    }

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
                    'gateway'           => $this->refund->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === false)
        {
            return 0;
        }

        return Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefund = $this->repo->wallet->findByRefundId($refundId);

        return $gatewayRefund;
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (isset($row[self::COLUMN_GATEWAY_REFUND_ID]) === false)
        {
            return null;
        }

        return $row[self::COLUMN_GATEWAY_REFUND_ID];
    }

    protected function setGatewayTransactionId(string $gatewayRefundId, PublicEntity $gatewayRefund)
    {
        $dbGatewayRefundId = $gatewayRefund->getGatewayRefundId();

        if ((empty($dbGatewayRefundId) === false) and
            ($dbGatewayRefundId !== $gatewayRefundId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getBaseAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayRefundId,
                    'recon_reference_number'    => $gatewayRefundId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setGatewayRefundId($gatewayRefundId);
    }
}
