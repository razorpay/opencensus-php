<?php

namespace RZP\Reconciliator\Bob;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    public function getRefundId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TRACK_ID];
    }

    protected function getGatewayRefund(string $refundId)
    {
        return $this->repo
            ->card_fss
            ->findOrFailRefundByRefundId($refundId);
    }

    protected function getGatewaySettledAt($row)
    {
        if(empty($row[ReconcilationFields::PAYMENT_DATE]) === false)
        {
            $date = Carbon::createFromFormat('d-m-Y', $row[ReconcilationFields::PAYMENT_DATE], Timezone::IST)->timestamp;

        }
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[ReconcilationFields::RRN] ?? null;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[ReconcilationFields::TRANSACTION_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = Base\Helper::getIntegerFormattedAmount($row[ReconcilationFields::TRANSACTION_AMOUNT]);

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
     * The card_fss entity ref column should be updated with rrn
     * It should be set as ref setReferenceNumberInGateway
     * in the gateway entity.
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayPayment CardFss Entity
     * */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setRef($referenceNumber);
    }

    protected function getReconCurrencyCode($row)
    {
        if (empty(ReconcilationFields::TRANSACTION_CURRENCY_CODE) === true)
        {
            $this->reportMissingColumn($row, ReconcilationFields::TRANSACTION_CURRENCY_CODE);

            return null;
        }

        return $row[ReconcilationFields::TRANSACTION_CURRENCY_CODE];
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? "INR" : $this->payment->getCurrency();

        $reconCurrency = $this->getReconCurrencyCode($row);

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund currency mismatch',
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
