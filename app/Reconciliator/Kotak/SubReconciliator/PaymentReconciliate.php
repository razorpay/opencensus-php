<?php

namespace RZP\Reconciliator\Kotak\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_INT_PAYMENT_ID = 'int_payment_id';
    const COLUMN_PAYMENT_AMOUNT = 'amount';
    const BANK_REFERENCE_NO     = 'bank_reference_no';

    const BLACKLISTED_COLUMNS = [
        'contact_no',
        'customer_name',
    ];

    protected function getPaymentId(array $row)
    {
        $intPaymentId = $row[self::COLUMN_INT_PAYMENT_ID] ?? null;

        $paymentId = null;

        try
        {
            $gatewayPayment = $this->repo->netbanking->findByVerificationIdAndAction($intPaymentId, Action::AUTHORIZE);

            if ($gatewayPayment === null)
            {
                $gatewayPayment = $this->repo->netbanking->findByIntPaymentId($intPaymentId);
            }

            $paymentId = $gatewayPayment->getPaymentId();
        }
        catch (DbQueryException $ex) {
            $paymentId = $intPaymentId;
        }

        return $paymentId;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_REFERENCE_NO] ?? null;
    }

    protected function getArn($row)
    {
        return $row[self::BANK_REFERENCE_NO] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
