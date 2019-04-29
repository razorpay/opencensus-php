<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;


class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const  ONUS_INDICATOR_VALUE = 'yes';

    public function getRefundId(array $row)
    {
        $refundId = $row[ReconciliationFields::MERCHANT_TRACK_ID] ?? null;

        return trim(str_replace("'", '', $refundId));
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

    /**
     * Returns the card_fss trans id
     * @param $row
     * @return string|null
     */
    protected function getGatewayTransactionId(array $row)
    {
        return trim(str_replace("'", '', $row[ReconciliationFields::PG_TRANSACTION_ID] ?? null));
    }

    protected function getArn(array $row)
    {
        $onusIndicator = $row[ReconciliationFields::ONUS_INDICATOR];

        $rrn = trim(str_replace("'", '',  ($row[ReconciliationFields::RRN] ?? null)));

        if (empty($rrn) === true)
        {
            $this->reportMissingColumn($row, ReconciliationFields::RRN);
        }

        if (strtolower($onusIndicator) === self::ONUS_INDICATOR_VALUE)
        {
           return $rrn;
        }

        return null;
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
                    'info_code'         => InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $refundAmount,
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
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
        return $row[ReconciliationFields::TRANSACTION_CURRENCY_CODE] ?? null;
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
                    'info_code'         => InfoCode::CURRENCY_MISMATCH,
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
