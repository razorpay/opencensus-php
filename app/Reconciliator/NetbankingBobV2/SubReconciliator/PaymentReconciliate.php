<?php

namespace RZP\Reconciliator\NetbankingBobV2\SubReconciliator;

use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Mozart\NetbankingBob\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::BANK_REFERENCE_NUMBER] ?? null;
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
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }


    protected function getReconPaymentAmount(array $row)
    {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount(substr($row[ReconFields::AMOUNT],4) ?? null);
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }


    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getBankPaymentId());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode:: RECON_MISMATCH,
                [
                    'info_code'              => $infoCode,
                    'payment_id'             => $this->payment->getId(),
                    'amount'                 => $this->payment->getAmount(),
                    'db_reference_number'    => $dbReferenceNumber,
                    'recon_reference_number' => $referenceNumber,
                    'gateway'                => $this->gateway
                ]
            );
        }

        $gatewayPayment->setBankPaymentId($referenceNumber);
    }
}
