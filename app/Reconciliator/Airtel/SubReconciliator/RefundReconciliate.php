<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Wallet;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';
    const COLUMN_PAYMENT_ID         = 'partner_txn_id';
    const COLUMN_REFUND_AMOUNT      = 'original_input_amt';

    protected function getRefundId($row)
    {
        $refundId = null;

        if ((isset($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === false) or
            (isset($row[self::COLUMN_PAYMENT_ID]) === false))
        {
            return $refundId;
        }

        $gatewayPaymentId = $row[self::COLUMN_GATEWAY_PAYMENT_ID];

        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $method = $payment->getMethod();

            if ($method === Method::WALLET)
            {
                $gatewayRefundEntity = $this->repo
                                            ->wallet
                                            ->findbyGatewayPaymentIdAndAction(
                                                                        $gatewayPaymentId,
                                                                        Action::REFUND,
                                                                        Wallet::AIRTELMONEY);
            }
            else
            {
                $gatewayRefundEntity = $this->repo
                                            ->netbanking_airtel
                                            ->findbyGatewayPaymentIdAndAction($gatewayPaymentId, Action::REFUND);
            }

            $refundId = $gatewayRefundEntity->getRefundId();
        }
        catch (DbQueryException $exception)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::REFUND_ABSENT,
                    'row'               => $row,
                    'gateway'           => $this->gateway,
                ]);
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
