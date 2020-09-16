<?php

namespace RZP\Reconciliator\Cred\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::P1_TRANSACTION_ID] ?? null;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if (parent::validatePaymentAmountEqualsReconAmount($row) === false)
        {
            return false;
        }

        $totalAmount  = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);
        $amount       = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::AMOUNT]);
        $credCoinBurn = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::CRED_COIN_BURN]);

        if ($totalAmount !== ($amount + $credCoinBurn))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'     => TraceCode::RECON_INFO_ALERT,
                    'info_code'      => InfoCode::AMOUNT_MISMATCH,
                    'message'        => 'Amount in order does not equal given amount and cred coin burn',
                    'order_amount'   => $totalAmount,
                    'payment_id'     => $this->payment->getId(),
                    'amount'         => $amount,
                    'cred_coin_burn' => $credCoinBurn,
                    'gateway'        => $this->gateway,
                    'batch_id'       => $this->batchId,
                ]
            );
        }

        return true;
    }
}
