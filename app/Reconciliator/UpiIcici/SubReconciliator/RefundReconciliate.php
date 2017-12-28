<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    const REFUND_ID = 'merchanttranid';

    const COLUMN_REFUND_AMOUNT = 'refund_amount';

    const ORIGINAL_BANK_RRN = 'original_bank_rrn';

    protected function getRefundId(array $row)
    {
        return $row[self::REFUND_ID];
    }

    protected function getPaymentId(array $row)
    {
        $rrn = $row[self::ORIGINAL_BANK_RRN];

        $upiEntity = $this->repo->upi->fetchByGatewayPaymentIdAndAction($rrn);

        return $upiEntity->getPaymentId();
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $reconAmount = $this->getReconRefundAmount($row);

        $refundAmount = $this->refund->getBaseAmount();

        if ($reconAmount !== $refundAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'currency'        => $this->refund->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
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

    // TODO: Add getter persister methods for this refund reconciliate class
}