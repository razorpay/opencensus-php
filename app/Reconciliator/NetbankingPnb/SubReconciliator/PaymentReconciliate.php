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
    const COLUMN_AMOUNT              = 'amount';
    const COLUMN_DATE                = 'date';

    protected function getPaymentId($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return ltrim($row[self::COLUMN_PAYMENT_ID]);
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
            return ltrim($row[self::COLUMN_BANK_ACCOUNT_NUMBER]);
        }

        return null;
    }

    protected function shouldAttemptForceAuthorizeFailed()
    {
        return true;
    }

    protected function forceAuthorizeFailed(array $row)
    {
        $paymentId = $this->payment->getPublicId();

        $input = [
            'gateway_payment_id'   => (string) $row[self::COLUMN_GATEWAY_PAYMENT_ID],
        ];

        $this->messenger->raiseReconAlert(
            [
                'trace_code'      => TraceCode::RECON_INFO_ALERT,
                'message'         => 'Payment status is still failed after verify. Doing force authorize now.',
                'payment_id'      => $this->payment->getId(),
                'gateway'         => get_called_class()
            ]);

        // If there's any issue during authorize, the function throws an exception.
        $response = (new Payment\Service)->forceAuthorizeFailed($paymentId, $input);

        $this->app['trace']->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => 'FORCE_AUTHORIZATION_RESPONSE',
                'message'   => 'Response received from force authorization',
                'response'  => $response
            ]
        );

        if ((empty($response['status']) === false) and
            ($response['status'] === PaymentStatus::AUTHORIZED))
        {
            return true;
        }

        return false;
    }
}
