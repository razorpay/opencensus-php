<?php

namespace RZP\Reconciliator\PayuMoney;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Wallet\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID        = 'merchant_transaction_id';

    const BANK_PAYMENT_ID   = 'payment_id';

    const DATE              = 'succeededon_date';

    const CUSTOMER_NAME     = 'customer_name';

    const AMOUNT            = 'amount';

    const SETTLEMENT_AMOUNT = 'settlement_amount';

    const SERVICE_TAX       = 'service_tax';

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::DATE] ?? null;
    }

    protected function getGatewayServiceTax($row)
    {
        return (int) ($row[self::SERVICE_TAX] * 100) ?? null;
    }

    protected function getGatewayFee($row)
    {
        return (int) (($row[self::AMOUNT] - $row[self::SETTLEMENT_AMOUNT]) * 100) ?? null;
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

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->wallet->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }
}
