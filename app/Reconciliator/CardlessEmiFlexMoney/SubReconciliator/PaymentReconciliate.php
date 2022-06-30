<?php

namespace RZP\Reconciliator\CardlessEmiFlexMoney\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const PAYMENT_ID                = 'pg_transaction_id';
    const GATEWAY_TRANSACTION_ID    = 'flexpay_transaction_id';
    const TRANSACTION_AMOUNT        = 'transaction_amount';
    const TRANSACTION_DATE          = 'transaction_date';
    const LENDER_ID                 = 'lender_id';
    const FLEXMONEY_MDR_SHARE       = 'flexmoney_mdr_share';
    const GST_ON_MDR                = 'gst_on_mdr';
    const SETTLEMENT_AMOUNT         = 'settlement_amount';

    const BLACKLISTED_COLUMNS = [];

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

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency. (ex: paise)
        return floatval($row[self::FLEXMONEY_MDR_SHARE]) * 100 ?? null;

    }

    protected function getGatewayServiceTax($row)
    {
        // Convert fee into basic unit of currency. (ex: paise)
        return floatval($row[self::GST_ON_MDR]) * 100 ?? null;
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

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->cardless_emi->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayReferenceId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
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
