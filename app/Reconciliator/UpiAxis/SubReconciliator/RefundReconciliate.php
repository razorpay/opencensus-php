<?php

namespace RZP\Reconciliator\UpiAxis\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const RRN                     = 'rrn';
    const TXN_ID                  = 'txn_id';
    const REFUND_ID               = 'order_id';
    const RESPONSE                = 'response';
    const COLUMN_REFUND_AMOUNT    = 'refund_amount';

    const SUCCESS = 'Success';

    protected function getRefundId(array $row)
    {
       return $row[self::REFUND_ID] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = $row[self::RESPONSE] ?? null;

        if ($rowStatus === self::SUCCESS)
        {
            return Payment\Refund\Status::PROCESSED;
        }

        return Payment\Refund\Status::FAILED;
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefunds = $this->repo->upi->findByRefundIdAndAction($refundId, Payment\Action::REFUND);

        return $gatewayRefunds->first();
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
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'currency'        => $this->refund->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayRefund)
    {
        $dbGatewayTransactionId = (string) $gatewayRefund->getNpciTransactionId();

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => 'DUPLICATE_ROW or DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setNpciTransactionId($gatewayTransactionId);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $dbReferenceNumber = (string) $gatewayRefund->getNpciReferenceId();

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setNpciReferenceId($referenceNumber);
    }

    protected function getReconRefundAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT] ?? null);
    }
}
