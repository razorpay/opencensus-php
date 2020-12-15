<?php

namespace RZP\Reconciliator\NetbankingIdfc\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingIdfc\Constants;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    public function getPaymentId(array $row)
    {
        if (empty($row[Constants::RZP_PAYMENT_ID]) === false)
        {
            return $row[Constants::RZP_PAYMENT_ID];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_REFERENCE_NO] ?? null;
    }

    protected function getArn($row)
    {
        return $row[Constants::BANK_REFERENCE_NO] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);

        return $gatewayPayment;
    }

    protected function getReconPaymentStatus(array $row)
    {
        return ($row[Constants::STATUS] === 'SUCCESS') ? Status::AUTHORIZED : Status::FAILED;
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
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[Constants::TRANSACTION_AMOUNT] ?? null);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
