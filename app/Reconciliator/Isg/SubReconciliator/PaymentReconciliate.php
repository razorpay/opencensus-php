<?php
namespace RZP\Reconciliator\Isg\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::FINAL_AMOUNT;

    const KOTAK_DEBIT_EMI       = 'kotak_debit_emi';

    public function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::ORDER_ID] ?? null;
    }

    public function getReferenceNumber($row)
    {
        return $row[ReconciliationFields::RRN] ?? null;
    }

    public function getArn($row)
    {
        return $this->getReferenceNumber($row);
    }

    protected function getGatewayServiceTax($row)
    {
        if (isset($row[ReconciliationFields::GST]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::GST);
        }

            return SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::GST]);
    }

    protected function getAuthCode($row)
    {
        $authCode = $row[ReconciliationFields::APP_CODE] ?? null;

        if ($authCode === null)
        {
            $this->reportMissingColumn($row, ReconciliationFields::APP_CODE);

            return null;
        }

        // If the value is 088232 in sheet, the parsed value would be 88232. This prepends the required 0s
        return sprintf("%06d", $authCode);
    }

    protected function getGatewayFee($row)
    {
        if (isset($row[ReconciliationFields::MDR]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::MDR);
        }
        $serviceTax = $this->getGatewayServiceTax($row);
        $fee = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::MDR]);

        return $serviceTax + $fee;
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        if($payment->getGateway() === self::KOTAK_DEBIT_EMI)
        {
            $this->allowForceAuthorization = true;
        }
    }

    protected function getGatewayTransactionId($row)
    {
        return $row[ReconciliationFields::APAC_ID] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference2' => $this->getGatewayTransactionId($row),
            ]
        ];
    }
}
