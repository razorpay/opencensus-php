<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    public function getRefundId(array $row)
    {
        return $row[ReconciliationFields::MERCHANT_TRACK_ID] ?? null;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        return $this->repo
                    ->card_fss
                    ->findOrFailRefundByRefundId($refundId);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[ReconciliationFields::PAYMENT_DATE]) === false)
        {
            $date = Carbon::createFromFormat('d-m-Y', $row[ReconciliationFields::PAYMENT_DATE], Timezone::IST)->timestamp;

            return $date;
        }
    }

    protected function getArn(array $row)
    {
        $onusIndicator = $row[ReconciliationFields::ONUS_INDICATOR];

        $rrn = $this->getReferenceNumber($row);

        if ((empty($rrn) === true) and
            ($onusIndicator === 'YES'))
        {
            $this->reportMissingColumn($row, $row[ReconciliationFields::RRN]);
        }

        if ($onusIndicator === 'YES')
        {
            return $row[ReconciliationFields::RRN] ?? null;

        }
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[ReconciliationFields::TRANSACTION_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);

        return abs($refundAmount);
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $refundAmount = ($convertCurrency === true) ? $this->refund->getBaseAmount() : $this->refund->getAmount();

        if ($refundAmount !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'message'           => 'Refund amount mismatch',
                    'expected_amount'   => $refundAmount,
                    'currency'          => $this->refund->getCurrency(),
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }
        return true;
    }

    /**
     * The card_fss entity ref column should be updated with arn
     * It should be set as ref setReferenceNumberInGateway
     * in the gateway entity.
     * @param string $arn
     * @param PublicEntity $gatewayRefund CardFss Entity
     * */
    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setRef($arn);
    }

    protected function getReconCurrencyCode($row)
    {
        if (empty(ReconciliationFields::TRANSACTION_CURRENCY_CODE) === true)
        {
            $this->reportMissingColumn($row, ReconciliationFields::TRANSACTION_CURRENCY_CODE);

            return null;
        }

        return $row[ReconciliationFields::TRANSACTION_CURRENCY_CODE];
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getCurrency();

        $reconCurrency = $this->getReconCurrencyCode($row);

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => Base\InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;

        }

        return true;
    }
}
