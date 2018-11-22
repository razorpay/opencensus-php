<?php

namespace RZP\Reconciliator\NetbankingEquitas\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingEquitas\Constants;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    public function getPaymentId(array $row)
    {
        return $row[Constants::GATEWAY_REFERENCE_NUMBER] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Constants::STATUS];

        if ($status === Constants::PAYMENT_STATUS_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    public function getGatewayPaymentDate($row)
    {
        return $row[Constants::DATE_OF_TRANSACTION] ?? null;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Constants::ACCOUNT_NUMBER]
        ];
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[Constants::AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::AMOUNT]);
        }
    }
}
