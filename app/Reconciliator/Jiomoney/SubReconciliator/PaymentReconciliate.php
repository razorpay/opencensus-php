<?php

namespace RZP\Reconciliator\Jiomoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Jiomoney\Gateway as JiomoneyGateway;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'external_reference_number';
    const COLUMN_PAYMENT_AMOUNT     = 'transaction_amount';
    const COLUMN_PAYMENT_DATE       = 'tran_datetime';
    const COLUMN_GATEWAY_PAYMENT_ID = 'retrieval_ref_number';

    const GATEWAY_PAYMENT_DATE_FORMAT = 'm/d/Y H:i:s';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    protected function getGatewayPaymentDate($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_DATE]) === true)
        {
            return null;
        }

        $gatewayPaymentDate = null;

        try
        {
            $gatewayPaymentDate = Carbon::createFromFormat(
                                    self::GATEWAY_PAYMENT_DATE_FORMAT,
                                    $row[self::COLUMN_PAYMENT_DATE],
                                    Timezone::IST);

            $gatewayPaymentDate = $gatewayPaymentDate->format(JiomoneyGateway::DATE_FORMAT);
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Unable to parse gateway payment date -> ' . $ex->getMessage(),
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            $this->app['trace']->traceException($ex);
        }

        return $gatewayPaymentDate;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getAmount() !== $this->getGatewayPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getAmount(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->wallet_jiomoney
                    ->findSuccessfulPaymentsByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function forceAuthorizeFailed(array $row)
    {
        $paymentId = $this->payment->getPublicId();

        $input = [
            'gateway_payment_id'   => (string) $row[self::COLUMN_GATEWAY_PAYMENT_ID],
            'gateway_payment_date' => $this->getGatewayPaymentDate($row)
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

    protected function setGatewayPaymentDateInGateway(string $gatewayPaymentDate, PublicEntity $gatewayPayment)
    {
        $validDate = $this->validateGatewayPaymentDate($gatewayPayment->getDate());

        if ($validDate === false)
        {
            $gatewayPayment->setDate($gatewayPaymentDate);
        }
    }

    /**
     * Validates that the date in gateway entity is not null and in the format
     * sent by Jiomoney
     *
     * @param  string $date gateway payment date
     * @return bool
     */
    protected function validateGatewayPaymentDate($date)
    {
        if ($date !== null)
        {
            try
            {
                $formattedDate = Carbon::createFromFormat(JiomoneyGateway::DATE_FORMAT, $date, Timezone::IST);

                return ($formattedDate !== null) ? true : false;
            }
            catch (\Exception $e)
            {
                return false;
            }
        }

        return false;
    }
}
