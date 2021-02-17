<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const  ONUS_INDICATOR_VALUE = 'yes';

    public function getRefundId(array $row)
    {
        $columnRefundId = array_first(ReconciliationFields::MERCHANT_TRACK_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $refundId = $row[$columnRefundId] ?? null;
        
        if (empty($refundId) === false)
        {
            $refundId = trim(str_replace("'", '', $refundId));
            
            // Sometimes we get digits appended in refund ID
            // so take first 14 chars only.
            //
            return substr($refundId, 0, 14);
        }

        return null;
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
        $columnGatewaySettledDate = array_first(ReconciliationFields::GATEWAY_SETTLED_DATE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        if (empty($row[$columnGatewaySettledDate]) === true)
        {
            return null;
        }

        $gatewaySettledAtTimestamp = null;

        $settledAt = $row[$columnGatewaySettledDate];

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($settledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'        => $settledAt,
                    'refund_id'         => $this->refund->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    /**
     * Returns the card_fss trans id
     * @param $row
     * @return string|null
     */
    protected function getGatewayTransactionId(array $row)
    {
        $columnPgTranId = array_first(ReconciliationFields::PG_TRANSACTION_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim(str_replace("'", '', $row[$columnPgTranId] ?? null));
    }

    /**
     * As confirmed with Finops, we need to save ARN in
     * onus and offus both the cases. Save rrn in place
     * of ARN, Only when ARN is not available.
     * @param array $row
     * @return mixed|null
     *
     */
    protected function getArn(array $row)
    {
        $arn = $row[ReconciliationFields::ARN] ?? null;

        if (empty($arn) === false)
        {
            return $arn;
        }

        $this->reportMissingColumn($row, ReconciliationFields::ARN);

        $columnRrn = array_first(ReconciliationFields::RRN, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $rrn = trim(str_replace("'", '',  ($row[$columnRrn] ?? null)));

        if (empty($rrn) === true)
        {
            $this->reportMissingColumn($row, implode(',', ReconciliationFields::RRN));
        }

        return $rrn;
    }

    protected function getReconRefundAmount(array $row)
    {
        $columnAmount = array_first(ReconciliationFields::TRANSACTION_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        return Helper::getIntegerFormattedAmount($row[$columnAmount] ?? null);
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $refundAmount = ($convertCurrency === true) ? $this->refund->getBaseAmount() : $this->refund->getGatewayAmount();

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
        $columnReconCurrency = array_first(ReconciliationFields::TRANSACTION_CURRENCY_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim($row[$columnReconCurrency] ?? null);
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getGatewayCurrency();

        $reconCurrency = $this->getReconCurrencyCode($row);

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'refund_id'         => $this->refund->getId(),
                    'amount'            => $this->refund->getAmount(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
