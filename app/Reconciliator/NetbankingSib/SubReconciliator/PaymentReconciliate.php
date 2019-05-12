<?php

namespace RZP\Reconciliator\NetbankingSib\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\NetbankingSib\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconFields::TRANSACTION_DATE] ?? null;
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
        if (empty($row[ReconFields::PAYMENT_AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper ::getIntegerFormattedAmount($row[ReconFields::PAYMENT_AMOUNT]);
        }
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['bank_payment_id'];

        //
        // Sometimes we have db reference number saved as string 'null'.
        // (we encountered few cases in Atom). We don't want to raise data
        // mismatch alert in such cases. so adding a check to compare
        // string 'null'
        //
        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== 'null') and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['bank_payment_id'] = $referenceNumber;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
    }
}
