<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Axis;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN No';
    const COLUMN_BANK_PAYMENT_ID = 'BID';

    protected function getPaymentId($row)
    {
        return $row[self::COLUMN_PAYMENT_REF_NO];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_PAYMENT_ID];
    }

    protected function getGatewayPayment()
    {
        return $this->netbankingRepo->findByPaymentIdActionAndStatus(
                                                            $this->payment->getId(),
                                                            Action::AUTHORIZE,
                                                            Axis\Gateway::getAuthorizedStatus());
    }
}
