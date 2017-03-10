<?php

namespace RZP\Reconciliator\Jiomoney;

use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'external_reference_number';
    const COLUMN_REFUND_AMOUNT = 'ntwk_recon_amt';

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->wallet_jiomoney->findSuccessfulRefundByRefundId(
                                                            $refundId,
                                                            Wallet::JIOMONEY);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
    }

    protected function getRefundAmount(array $row)
    {
        $refundAmount = parent::getRefundAmount($row);

        $refundAmount = intval(number_format($refundAmount, 2, '.', ''));

        // Jiomoney returns refund amount as a negative value in the report file.
        // This step handles that by converting it to a positive number. In case
        // we get positive refund amount we use it as is
        $refundAmount = ($refundAmount < 0) ? ($refundAmount * -1) : $refundAmount;

        return $refundAmount;
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getAmount() !== $this->getRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Refund amount mismatch',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
