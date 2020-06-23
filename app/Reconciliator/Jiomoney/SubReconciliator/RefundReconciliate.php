<?php

namespace RZP\Reconciliator\Jiomoney\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Processor\Wallet;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'merchant_ref_id';
    const COLUMN_REFUND_AMOUNT      = 'gross_amount';
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';

    protected function getRefundId(array $row)
    {
        return ltrim($row[self::COLUMN_REFUND_ID] ?? null);
    }

    protected function getPaymentId(array $row)
    {
        $gatewayPaymentId = (string) $row[self::COLUMN_GATEWAY_PAYMENT_ID];

        $gatewayEntity = $this->repo->wallet_jiomoney->findByGatewayPaymentIdAndAction(
                                                            $gatewayPaymentId,
                                                            Action::AUTHORIZE,
                                                            Wallet::JIOMONEY);

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = parent::getReconRefundAmount($row);

        $refundAmount = intval(number_format($refundAmount, 2, '.', ''));

        // Jiomoney sometimes gives negative value for refund amount so we take the absolute value here
        $refundAmount = abs($refundAmount);

        return $refundAmount;
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
                    'gateway'           => $this->gateway,
                ]);

            return false;
        }

        return true;
    }
}
