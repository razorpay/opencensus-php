<?php

namespace RZP\Reconciliator\PayuMoney;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID = 'Merchant Transaction ID';

    const BANK_PAYMENT_ID = 'Payment Id';

    const DATE = 'SucceededOn Date';

    const CUSTOMER_NAME = 'Customer Name';

    const AMOUNT = 'Amount';

    const SETTLEMENT_AMOUNT = 'Settlement Amount';

    const SERVICE_TAX = 'Service Tax';

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_PAYMENT_ID];
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::DATE];
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_NAME => $row[self::CUSTOMER_NAME]
        ];
    }

    protected function getGatewayFee($row)
    {
        return (int) (($row[self::AMOUNT] - $row[self::SETTLEMENT_AMOUNT]) * 100);
    }

    protected function getGatewayServiceTax($row)
    {
        return (int) ($row[self::SERVICE_TAX] * 100);
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $paymentAmount = $this->payment->getAmount();

        $reconAmount = (int) ($row[self::AMOUNT] * 100);

        return ($paymentAmount === $reconAmount);
    }
}