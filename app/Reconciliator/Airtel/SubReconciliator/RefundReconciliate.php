<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';
    const COLUMN_PAYMENT_ID         = 'partner_txn_id';
    const COLUMN_REFUND_AMOUNT      = 'original_input_amt';

    protected function getRefundId($row)
    {
        if ((isset($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === false) or
            (isset($row[self::COLUMN_PAYMENT_ID]) === false))
        {
            return null;
        }

        $gatewayRefundId = $row[self::COLUMN_GATEWAY_PAYMENT_ID];

        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $payment = $this->repo->payment->findOrFail($paymentId);

        if ($payment['gateway'] === 'wallet_airtelmoney')
        {
            $airtelRepo = $this->app['repo']->wallet;
        }
        else
        {
            $airtelRepo = $this->app['repo']->netbanking_airtel;
        }

        $refundId = $airtelRepo->findByGatewayPaymentId($gatewayRefundId)->getRefundId();

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
                    'message'           => 'Refund amount mismatch',
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'actual_amount'     => $this->getReconRefundAmount($row),
                    'row'               => $row,
                    'gateway'           => $this->refund->getGateway(),
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
