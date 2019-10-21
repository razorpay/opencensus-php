<?php

namespace RZP\Reconciliator\CardlessEmiFlexMoney\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const REFUND_ID                = 'pg_refund_id';
    const GATEWAY_TRANSACTION_ID   = 'flexpay_transaction_id';
    const TRANSACTION_AMOUNT       = 'transaction_amount';
    const REFUND_AMOUNT            = 'refund_amount';
    const REFUND_DATE              = 'refund_date';

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        if (empty($row[self::REFUND_ID]) === false)
        {
            return $row[self::REFUND_ID];
        }

        return null;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        if ($refundId === null)
        {
            return null;
        }

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (empty($row[self::GATEWAY_TRANSACTION_ID]) === false)
        {
            return $row[self::GATEWAY_TRANSACTION_ID];
        }

        return null;
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefunds = $this->repo->cardless_emi->findByRefundIdAndAction($refundId, Action::REFUND);

        return $gatewayRefunds->first();
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'recon_amount'    => $this->getReconRefundAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway,
                    'refund_id'       => $this->getRefundId($row),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[self::REFUND_AMOUNT] ?? 0);
    }

    protected function setGatewayTransactionId(string $gatewayRefundId, PublicEntity $gatewayRefund)
    {
        $dbGatewayRefundId = $gatewayRefund->getGatewayReferenceId();

        if ((empty($dbGatewayRefundId) === false) and
            ($dbGatewayRefundId !== $gatewayRefundId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => Base\InfoCode::DATA_MISMATCH,
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

        $gatewayRefund->setGatewayReferenceId($gatewayRefundId);
    }
}
