<?php

namespace RZP\Reconciliator\Mpesa\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_GATEWAY_PAYMENT_ID = 'parent_m_pesa_txn_id';
    const COLUMN_REFUND_AMOUNT      = 'txn_amount_rs';

    protected function getRefundId($row)
    {
        $gatewayPaymentId = $row[self::COLUMN_GATEWAY_PAYMENT_ID];

        if (empty($gatewayPaymentId) === true)
        {
            return null;
        }

        $mpesaRepo = $this->app['repo']->wallet;

        $refundId = $mpesaRepo->findByGatewayPaymentIdAndAction($gatewayPaymentId, 'refund', 'mpesa')->getRefundId();

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
                    'gateway'           => $this->payment->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = parent::getReconRefundAmount($row);

        return intval(number_format($refundAmount, 2, '.', ''));
    }
}
