<?php
namespace RZP\Reconciliator\Icici\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Card\Fss\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{

    public function getPaymentId(array $row)
    {
        $columnPaymentId = array_first(ReconciliationFields::MERCHANT_TRACK_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $paymentId = $row[$columnPaymentId] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    protected function getReconPaymentAmount(array $row)
    {
        $columnAmount = array_first(ReconciliationFields::TRANSACTION_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        return Helper::getIntegerFormattedAmount($row[$columnAmount] ?? null);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $success = "successful";
        $TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP = [
            $success => "success"
        ];
        $transactionStatus = array_first(ReconciliationFields::TRANSACTION_STATUS, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });
        $paymentStatus = strtolower($row[$transactionStatus]);

        return $TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP[$paymentStatus] ?? "failed";

    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = Currency::INR;

        $reconCurrency = $this->getReconCurrency($row);

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconCurrency($row)
    {
        $columnReconCurrency = array_first(ReconciliationFields::CURRENCY_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim($row[$columnReconCurrency] ?? null);
    }

    public function getReferenceNumber($row)
    {
        $columnRrn = array_first(ReconciliationFields::RRN, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $rrn = $row[$columnRrn] ?? null;

        return trim(str_replace("'", '', $rrn ?? null));
    }

    protected function getGatewayPaymentDate($row)
    {
        $columnTranDate = array_first(ReconciliationFields::TRANSACTION_DATE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return $row[$columnTranDate] ?? null;
    }

    /**
     * Gets the card details from settlement file. Not updating trivia since it is inconsistent
     * @param $row
     * @return array
     */
    protected function getCardDetails($row)
    {
        return [
            BaseReconciliate::CARD_TYPE   => $this->getCardType($row),
        ];
    }

    /**
     * Returns if the card is debit or credit from Payment Method
     * @param array $row Card type would be Debit Card
     * @return string|null if any card type is present
     */
    protected function getCardType($row)
    {
        $columnPaymentMethod = array_first(ReconciliationFields::CARD_TYPE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $cardType = explode(' ', strtolower($row[$columnPaymentMethod] ?? null))[0];

        if (in_array($cardType, [BaseReconciliate::DEBIT_TYPE]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => InfoCode::UNKNOWN_CARD_TYPE,
                    'recon_card_type' => $cardType,
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => $this->gateway
                ]);

            return null;
        }
        if($cardType == 'd'){
            return BaseReconciliate::DEBIT;
        }

        return $cardType;
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
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }



}
