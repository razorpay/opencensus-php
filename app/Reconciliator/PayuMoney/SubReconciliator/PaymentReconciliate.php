<?php

namespace RZP\Reconciliator\PayuMoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Wallet\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID        = 'Merchant Transaction ID';

    const BANK_PAYMENT_ID   = 'Payment Id';

    const DATE              = 'SucceededOn Date';

    const CUSTOMER_NAME     = 'Customer Name';

    const AMOUNT            = 'Amount';

    const SETTLEMENT_AMOUNT = 'Settlement Amount';

    const SERVICE_TAX       = 'Service Tax';

    const SETTLEMENT_DATE   = 'Settlement Date';

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    /**
     * This will be saved in the wallet entity's date column
     * @param $row
     * @return null
     */
    protected function getGatewayPaymentDate($row)
    {
        return $row[self::DATE] ?? null;
    }

    /**
     * This will be saved as gateway_service_tax in the payment's transaction entity
     * @param $row
     * @return int|null
     */
    protected function getGatewayServiceTax($row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::SERVICE_TAX]) ?? null;
    }

    /**
     * This will be saved as gateway_fee in the payment's transaction entity
     * @param $row
     * @return int|null
     */
    protected function getGatewayFee($row)
    {
        //
        // The total gateway fee can be calculated as the difference between
        // the payment amount and the amount that will be settled to our nodal account.
        //

        $amount = Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);

        $settlementAmount = Base\Helper::getIntegerFormattedAmount($row[self::SETTLEMENT_AMOUNT]);

        return ($amount - $settlementAmount) ?? null;
    }

    /**
     * Will be persisted into the transaction entity's gateway_settled_at column
     * @param array $row
     * @return int|null
     */
    protected function getGatewaySettledAt(array $row)
    {
        $settledAt = $row[self::SETTLEMENT_DATE];

        if (empty($settledAt) === true)
        {
            return null;
        }

        return Carbon::createFromFormat('d-M-y H:i:s', $settledAt, Timezone::IST)->getTimestamp();
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    /**
     * We fetch the gateway payment entity to be persisted into
     * @param $paymentId
     * @return mixed
     */
    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->wallet->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }
}
