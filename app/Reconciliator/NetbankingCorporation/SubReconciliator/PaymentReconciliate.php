<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Gateway\Netbanking\Corporation\ReconcilationFields;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TXN_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconcilationFields::BANK_TXN_ID] ?? null;
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
        if (empty($row[ReconcilationFields::TXN_ORG_AMOUNT]) === false)
        {
            return Base\Helper::getIntegerFormattedAmount($row[ReconcilationFields::TXN_ORG_AMOUNT]);
        }
    }

    protected function setAllowForceAuthorization()
    {
        return true;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $row[ReconcilationFields::BANK_TXN_ID],
        ];
    }

    protected function getReconPaymentStatus(array $row)
    {
        return (strtolower($row[ReconcilationFields::STATUS]) === 's') ? Status::AUTHORIZED : Status::FAILED;
    }
}
