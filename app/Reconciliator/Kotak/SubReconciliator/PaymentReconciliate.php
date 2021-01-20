<?php

namespace RZP\Reconciliator\Kotak\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
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
        catch (DbQueryException $ex)
        {
            //
            // Only tracing as error, not sending slack messages as this can happen in three cases
            // 1. file with wrong format has been uploaded and all payment_ids is mapped to some other data.
            // 2. only few rows are not in proper format (extra data or less data in a row) and mapping goes wrong.
            // 3. Int payment id present in file is genuinely not present in our database.
            //
            $this->messenger->setSkipSlack(true)->raiseReconAlert(
                [
                    'trace_code'            => TraceCode::RECON_MISMATCH,
                    'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_reference_id'  => $intPaymentId,
                    'gateway'               => $this->gateway
                ]);
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
