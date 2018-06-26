<?php

namespace RZP\Reconciliator\CardFss;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    const COLUMN_GATEWAY_TRANSACTION_ID   = 'aggregator_transaction_id';
    const COLUMN_REFUND_ID                = 'merchant_track_id';
    const COLUMN_REFUND_AMOUNT            = 'transaction_amount';
    const COLUMN_REFERENCE_TRANSACTION_ID = 'reference_tran_id';

    protected function getRefundId(array $row)
    {
        return $row[self::COLUMN_REFUND_ID];
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        return $this->repo
                    ->card_fss
                    ->findOrFailRefundByRefundId($refundId);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setRef($referenceNumber);
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = $row[self::COLUMN_REFUND_AMOUNT];

        return Base\Helper::getIntegerFormattedAmount($refundAmount);
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund amount mismatch',
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'currency'          => $this->refund->getCurrency(),
                    'row'               => $row,
                    'gateway'           => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_TRANSACTION_ID] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::COLUMN_REFERENCE_TRANSACTION_ID] ?? null;
    }

    protected function getArn(array $row)
    {
        //
        // In MIS file, we are not receiving ARN hence storing RRN in ARN field of refund entity.
        // This is done because for reporting purposes, we need reference number in refund entity.
        //
        return $row[self::COLUMN_REFERENCE_TRANSACTION_ID] ?? null;
    }
}
