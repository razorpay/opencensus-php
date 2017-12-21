<?php

namespace RZP\Reconciliator\NetbankingPnb;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Models\Payment\Status as PaymentStatus;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PRN                 = 'prn';
    const COLUMN_PAYMENT_ID          = 'payment_id';
    const COLUMN_GATEWAY_PAYMENT_ID  = 'bank_reference';
    const COLUMN_PAYMENT_AMOUNT      = 'amount';
    const COLUMN_DATE                = 'date';


    protected function getPrn($row)
    {
        if (empty($row[self::COLUMN_PRN]) === false)
        {
            return trim($row[self::COLUMN_PRN]);
        }

        return null;
    }

    protected function getPaymentId($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return trim($row[self::COLUMN_PAYMENT_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === true)
        {
            $referenceNumber = $row[self::COLUMN_GATEWAY_PAYMENT_ID];

            return $referenceNumber;
        }
        return null;
    }

    protected function getGatewayPayment($paymentId)
    {
        $status = [Status::SUCCESS];

        return $this->repo->netbanking
                          ->findByPaymentIdActionAndStatus($paymentId,
                                                           Action::AUTHORIZE,
                                                           $status);
    }

    protected function getReconPaymentAmount($row)
    {
        $paymentAmount = floatval(trim($row[self::COLUMN_PAYMENT_AMOUNT])) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    protected function shouldAttemptForceAuthorizeFailed()
    {
        return true;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => (string) $row[self::COLUMN_GATEWAY_PAYMENT_ID]
        ];
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
}
