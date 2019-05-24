<?php

namespace RZP\Reconciliator\CardlessEmiFlexMoney\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const PAYMENT_ID                = 'PG Transaction ID';
    const GATEWAY_TRANSACTION_ID    = 'Flexpay Transaction ID';
    const TRANSACTION_AMOUNT        = 'Transaction Amount';
    const TRANSACTION_DATE          = 'Transaction Date';

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::PAYMENT_ID]) === false)
        {
            return $row[self::PAYMENT_ID];
        }

        return null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (empty($row[self::GATEWAY_TRANSACTION_ID]) === false)
        {
            return $row[self::GATEWAY_TRANSACTION_ID];
        }

        return null;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[self::TRANSACTION_AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount(
                $row[self::TRANSACTION_AMOUNT]);
        }

        return null;
    }

    protected function getGatewayPaymentDate($row)
    {
        if (empty($row[self::TRANSACTION_DATE]) === false)
        {
            return $row[self::TRANSACTION_DATE];
        }

        return null;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->cardless_emi->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayReferenceId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayReferenceId($gatewayTransactionId);
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

    protected function setGatewayPaymentDateInGateway(string $gatewayPaymentDate, PublicEntity $gatewayPayment)
    {
        return;
    }
}
