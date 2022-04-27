<?php

namespace RZP\Reconciliator\UpiAirtel\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\SubReconciliator\Upi;

class PaymentReconciliate extends Upi\UpiPaymentServiceReconciliate
{
    const COLUMN_RRN                = 'partner_txn_id';
    const COLUMN_PAYMENT_ID         = 'till_id';
    const COLUMN_PAYMENT_AMOUNT     = 'original_input_amt';
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';

    protected $method;

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID] ?? null;

        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            $referenceNumber = $this->getReferenceNumber($row);

            $this->formatUpiRrn($referenceNumber);

            $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndGateway($referenceNumber, $gateway = Gateway::UPI_AIRTEL);

            if (empty($upiEntity) === false)
            {
                $paymentId = $upiEntity->getPaymentId();
            }
        }

        return $paymentId;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        try
        {
            return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
        }

        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function getGatewayPaymentId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID] ?? null;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $npciRefId = $gatewayPayment->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'message'                   => 'Npci Reference id is not same as in recon',
                    'info_code'                 => $infoCode,
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'payment_status'            => $this->payment->getStatus(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        // We will only update the RRN if it is empty
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function setGatewayPaymentId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayPaymentId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id'  => $row[self::COLUMN_GATEWAY_PAYMENT_ID],
            'acquirer' => [
                Payment\Entity::REFERENCE16 => $this->payment->getReference16(),
            ]
        ];
    }
}
