<?php

namespace RZP\Reconciliator\NetbankingCorporation\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Gateway\Netbanking\Corporation\ReconciliationFields;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    protected function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::MERCHANT_TXN_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconciliationFields::BANK_TXN_ID] ?? null;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }
        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TXN_ORG_AMOUNT]);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconciliationFields::TXN_EXECUTED_DATE] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        return (strtolower($row[ReconciliationFields::STATUS]) === 's') ? Status::AUTHORIZED : Status::FAILED;
    }
}
