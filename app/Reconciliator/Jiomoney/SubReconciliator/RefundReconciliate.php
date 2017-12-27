<?php

namespace RZP\Reconciliator\Jiomoney;

use RZP\Exception\ReconciliationException;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'external_reference_number';
    const COLUMN_REFUND_AMOUNT      = 'transaction_amount';
    const COLUMN_GATEWAY_PAYMENT_ID = 'retrieval_ref_number';

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
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
