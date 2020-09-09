<?php

namespace RZP\Reconciliator\UpiAxis\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const RRN                     = 'rrn';
    const VPA                     = 'vpa';
    const TXN_ID                  = 'txn_id';
    const PAYMENT_ID              = 'order_id';
    const RESPONSE                = 'response';
    const COLUMN_REFUND_AMOUNT    = 'refund_amount';
    const ACOUNT_CUST_NAME        = 'acnt_custname';

    const SUCCESS = ['success', 'refund accepted successfully'];

    const BLACKLISTED_COLUMNS = [
        self::ACOUNT_CUST_NAME,
        self::VPA,
    ];

    protected function getRefundId(array $row)
    {
        $refundId = null;

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getReconRefundAmount($row);

        $refunds = $this->repo->refund->findForPaymentAndAmount($paymentId, $refundAmount);

        if (count($refunds) === 1)
        {
            $refundId = $refunds[0]['id'];
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::RECON_UNIQUE_REFUND_NOT_FOUND,
                    'payment_id'            => $paymentId,
                    'refund_amount'         => $refundAmount,
                    'refund_count'          => count($refunds),
                    'gateway'               => $this->gateway,
                    'batch_id'              => $this->batchId,
                ]);
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getArn(array $row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = strtolower($row[self::RESPONSE] ?? null);

        if (in_array($rowStatus, self::SUCCESS) === true)
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
                    'refund_id'       => $this->refund->getId(),
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'recon_amount'    => $this->getReconRefundAmount($row),
                    'currency'        => $this->refund->getCurrency(),
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
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getBaseAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getBaseAmount(),
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
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getBaseAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getBaseAmount(),
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
