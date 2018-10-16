<?php

namespace RZP\Reconciliator\NetbankingIdfc\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID             = 'aggregatormerchant_txn_reference_no';
    const COLUMN_BANK_REFERENCE_NUMBER  = 'rib_txn_id';
    const COLUMN_SERVICE_TAX            = 'service_tax';
    const COLUMN_GATEWAY_FEE            = [ 'service_charge', 'commission' ];
    const COLUMN_PAYMENT_STATUS         = 'e_comm_payment_status';

    public function getPaymentId(array $row)
    {
        if ((empty($row[self::COLUMN_PAYMENT_ID]) === false) or
            ($row[self::COLUMN_PAYMENT_STATUS] === 'SUCCESS'))
        {
            return $row[self::COLUMN_PAYMENT_ID];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_REFERENCE_NUMBER];
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = 0;

        foreach(self::COLUMN_GATEWAY_FEE as $fee)
        {
            if (isset($row[$fee]) === true)
            {
                $gatewayFee += Helper::getIntegerFormattedAmount($row[$fee]);
            }
        }

        return $gatewayFee;
    }

    protected function getGatewayServiceTax($row)
    {
        $gatewayServiceTax = 0;

        if (isset($row[self::COLUMN_SERVICE_TAX]) === true)
        {
            $gatewayServiceTax += Helper::getIntegerFormattedAmount($row[self::COLUMN_SERVICE_TAX]);
        }

        return $gatewayServiceTax;
    }

    public function getGatewayPayment($paymentId)
    {
        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction($paymentId, 'authorize');

        return $gatewayPayment;
    }
}
