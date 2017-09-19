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
    const COLUMN_PAYMENT_ID          = 'payment_id';
    const COLUMN_GATEWAY_PAYMENT_ID  = 'bank_reference';
    const COLUMN_BANK_ACCOUNT_NUMBER = 'account_number';
    const COLUMN_PAYMENT_AMOUNT      = 'amount';
    const COLUMN_DATE                = 'date';

    protected function getPaymentId($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return trim($row[self::COLUMN_PAYMENT_ID]);
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

    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval(trim($row[self::COLUMN_PAYMENT_AMOUNT])) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Convering to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    protected function getNbAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $this->getDebitAccountNumber($row)
        ];
    }

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[self::COLUMN_BANK_ACCOUNT_NUMBER]) === false)
        {
            return trim($row[self::COLUMN_BANK_ACCOUNT_NUMBER]);
        }

        return null;
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
}
