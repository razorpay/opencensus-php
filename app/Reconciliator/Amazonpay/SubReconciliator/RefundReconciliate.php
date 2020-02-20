<?php

namespace RZP\Reconciliator\Amazonpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID          = 'merchantorderreferenceid';
    const COLUMN_REFUND_AMOUNT      = 'orderamount';
    const COLUMN_GATEWAY_REFUND_ID  = 'amazonorderreferenceid';

    protected function getRefundId($row)
    {
        $refundId = null;

        if (isset($row[self::COLUMN_REFUND_ID]) === true)
        {
            $refundId = $row[self::COLUMN_REFUND_ID];
        }

        return $refundId;
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
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === false)
        {
            return null;
        }

        return abs(Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]));
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (isset($row[self::COLUMN_GATEWAY_REFUND_ID]) === false)
        {
            return null;
        }

        return $row[self::COLUMN_GATEWAY_REFUND_ID];
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefund = $this->repo->wallet->findByRefundId($refundId);

        return $gatewayRefund;
    }

    protected function setGatewayTransactionId(string $gatewayRefundId, PublicEntity $gatewayRefund)
    {
        $dbGatewayRefundId = (string) $gatewayRefund->getGatewayRefundId();

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
