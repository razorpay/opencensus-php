<?php

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const ORDER_NUMBER = 'order_no';

    const TRANS_REF_NUMBER = 'trans_ref_no';

    const TRANSACTION_DATE = 'transaction_date';

    const CUSTOMER_REF_NUM = 'customer_ref_no';

    protected function getPaymentId(array $row)
    {
        return $row[self::ORDER_NUMBER];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::TRANS_REF_NUMBER];
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::TRANSACTION_DATE];
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID => $row[self::CUSTOMER_REF_NUM]
        ];
    }
}