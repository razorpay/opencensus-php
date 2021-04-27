<?php

namespace RZP\Reconciliator\NetbankingSbi\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Sbi\Status;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Gateway\Netbanking\Sbi\ReconFields\PaymentReconFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[PaymentReconFields::GATEWAY_REF_NO] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[PaymentReconFields::BANK_TRAN_REF_NO] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[PaymentReconFields::TRANSACTION_DATE] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[PaymentReconFields::STATUS] ?? Status::SUCCESS;

        return $this->getApiPaymentStatus($status);
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
        return Helper::getIntegerFormattedAmount($row[PaymentReconFields::TRANSACTION_AMOUNT]);
    }

    private function getApiPaymentStatus(string $status)
    {
        if ($status === Status::SUCCESS)
        {
            return PaymentStatus::CAPTURED;
        }

        return PaymentStatus::FAILED;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id'  => $this->getReferenceNumber($row),
            'acquirer'            =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
