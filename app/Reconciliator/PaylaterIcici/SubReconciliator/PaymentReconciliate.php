<?php

// this is not being used currently as the bank is sending the recon file from the same mail as that of netbanking.
// since there is mapping between email and gateway, same email cannot have two gateways mapped.
// keeping this if bank changes the recon email.
namespace RZP\Reconciliator\PaylaterIcici\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Mozart\PaylaterIcici\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT = ReconFields::AMOUNT;

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[ReconFields::PAYMENT_ID]) === false)
        {
            return $row[ReconFields::PAYMENT_ID];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (empty($row[ReconFields::BANK_PAYMENT_ID]) === false)
        {
            return $row[ReconFields::BANK_PAYMENT_ID];
        }

        return null;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['bank_payment_id'] ?? null;

        if ((empty($dbReferenceNumber) === false) and
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

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, \RZP\Models\Payment\Action::AUTHORIZE);
    }
}
