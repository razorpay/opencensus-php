<?php

namespace RZP\Reconciliator\NetbankingObc\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Obc\ReconciliationFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::MERCHANT_REFERENCE_NUMBER] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconciliationFields::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
       return $row[ReconciliationFields::GATEWAY_TRANSACTION_DATE] ?? null;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[ReconciliationFields::TRANSACTION_AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper ::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);
        }
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction(
                        $paymentId,
                        Action::AUTHORIZE
                    );
    }

    /**
     * We are force authorizing this because their verify API depends on bank reference number.
     *
     * @param Payment\Entity $payment
     */
    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $row[ReconciliationFields::BANK_REFERENCE_NUMBER],
            'acquirer'           =>       [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
