<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Axis;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO     = 'PRN No';
    const COLUMN_BANK_PAYMENT_ID    = 'BID';
    const COLUMN_BANK_CUSTOMER_ID   = 'User Id';
    const COLUMN_BANK_CUSTOMER_NAME = 'User Name';

    protected function getPaymentId($row)
    {
        return $row[self::COLUMN_PAYMENT_REF_NO];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_PAYMENT_ID];
    }

    protected function getCustomerId($row)
    {
        return $row[self::COLUMN_BANK_CUSTOMER_ID];
    }

    protected function getCustomerName($row)
    {
        return $row[self::COLUMN_BANK_CUSTOMER_NAME];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdActionAndStatus(
                                                            $paymentId,
                                                            Action::AUTHORIZE,
                                                            Axis\Gateway::getAuthorizedStatus());
    }
}
