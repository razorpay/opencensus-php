<?php


namespace RZP\Reconciliator\Icici\SubReconciliator;

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

            return substr($refundId, 0, 14);
        }

        return null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $columnGatewaySettledDate = array_first(ReconciliationFields::TRANSACTION_TIME, function ($col) use ($row)
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
     * Returns the ICICI trans id
     * @param $row
     * @return string|null
     */
    protected function getGatewayTransactionId(array $row)
    {
        $columnPgTranId = array_first(ReconciliationFields::TRANSACTION_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim(str_replace("'", '', $row[$columnPgTranId] ?? null));
    }


    protected function getReferenceNumber(array $row)
    {
        $columnRrn = array_first(ReconciliationFields::RRN, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $rrn = trim(str_replace("'", '',  ($row[$columnRrn] ?? null)));


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


    protected function getReconCurrencyCode($row)
    {
        $columnReconCurrency = array_first(ReconciliationFields::CURRENCY_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim($row[$columnReconCurrency] ?? null);
    }
    protected function getReconRefundStatus(array $row)
    {
        $success = "successful";
        $TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP = [
            $success => "processed"
        ];
        $transactionStatus = array_first(ReconciliationFields::TRANSACTION_STATUS, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });
        $paymentStatus = strtolower($row[$transactionStatus]);

        return $TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP[$paymentStatus] ?? "failed";
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {

        $expectedCurrency = Currency::INR ;

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
