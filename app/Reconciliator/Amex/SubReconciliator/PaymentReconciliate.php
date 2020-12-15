<?php

namespace RZP\Reconciliator\Amex\SubReconciliator;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT             = 'charge_amount';
    const COLUMN_SETTLED_AT_DATE            = 'settlement_date';
    const COLUMN_MERCHANT_ACCOUNT_NUMBER    = 'merchant_account_number';
    const COLUMN_CHARGE_REFERENCE_NUMBER    = 'charge_reference_number';

    /**
     * In preprocessing step, we have replaced ref
     * column to now contain actual payment ids.
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getPaymentId(array $row)
    {
        return trim($row[self::COLUMN_CHARGE_REFERENCE_NUMBER] ?? null);
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
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    /**
     * For majority of the cases, we get payment
     * id in charge_reference_number column, in such
     * cases, we need not save gateway transaction id
     * as it could be misleading. So, only saving in
     * case when it's not a unique id.
     *
     * @param array $row
     * @return mixed|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function getGatewayTransactionId(array $row)
    {
        $isValidPaymentId = Entity::verifyUniqueId(($row[self::COLUMN_CHARGE_REFERENCE_NUMBER] ?? null), false);

        return $isValidPaymentId ? null : $row[self::COLUMN_CHARGE_REFERENCE_NUMBER];
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT_DATE]) === true)
        {
            return null;
        }

        $gatewaySettledAt = null;

        $settledAt = $row[self::COLUMN_SETTLED_AT_DATE];

        if (strpos($settledAt, '-') !== false)
        {
            $settledAt = str_replace('-', '/', $settledAt);
        }

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat('d/m/Y', $settledAt, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'        => $settledAt,
                    'payment'           => $this->payment->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $gatewaySettledAt;
    }
}
