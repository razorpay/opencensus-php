<?php

namespace RZP\Reconciliator\NetbankingObc;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Obc\Status;
use RZP\Gateway\Netbanking\Obc\ReconciliationFields;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::MERCHANT_REFERENCE_NUMBER];
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconciliationFields::BANK_REFERENCE_NUMBER];
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconciliationFields::GATEWAY_TRANSACTION_DATE];
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        [Status::SUCCESS]
                    );
    }
}
