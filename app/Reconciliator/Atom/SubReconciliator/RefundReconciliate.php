<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_PAYMENT_ID                 = 'merchant_txn_id';
    const COLUMN_REFUND_AMOUNT              = 'gross_txn_amount';
    const COLUMN_BANK_REFERENCE_NO          = 'bank_ref_no';
    const COLUMN_ATOM_TRANSACTION_ID        = 'atom_txn_id';

    protected function getRefundId($row)
    {
        $refundId = null;

        $paymentId = $row[self::COLUMN_PAYMENT_ID] ?? null;

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

    protected function getArn(array $row)
    {
        return $row[self::COLUMN_BANK_REFERENCE_NO] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_ATOM_TRANSACTION_ID] ?? null;
    }
}
