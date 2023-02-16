<?php

namespace RZP\Models\Payment\Processor;

use RZP\Error\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode as TraceCode;
use RZP\Reconciliator\Base\Reconciliate;

trait AuthorizePush
{
    use UpiUnexpectedPaymentRefundHandler;

    public function authorizePush(array $callbackData, string $referenceId, array $data, Terminal\Entity $terminal, $isCallback = false)
    {
        $paymentInput = $data['payment'];

        $mutexResource = 'unexpected_' . $terminal->getGateway() . '_' . $referenceId;

        $success = $this->app['api.mutex']->acquireAndRelease(
            $mutexResource,
            function() use ($callbackData, $terminal, $paymentInput, $referenceId) {
                try
                {
                    $this->validateAuthorizePushPayment($callbackData, $terminal);

                    $this->createPaymentFromS2SCallback($callbackData, $paymentInput, $terminal);

                    $this->authorizePushPayment($this->payment, $callbackData);

                    return true;
                }
                catch (\Throwable $ex)
                {
                    $this->app['trace']->traceException(
                        $ex,
                        Trace::CRITICAL,
                        TraceCode::GATEWAY_UNEXPECTED_PAYMENT_ERROR,
                        [
                            'gateway'       => $terminal->getGateway(),
                            'payment_id'    => $referenceId
                        ]);

                    return false;
                }
            });

        $response =  ['success' => $success];

        if ($success === true)
        {
            $response['payment_id'] = $this->payment->getId();

            $this->handleUnExpectedPaymentRefundInCallback($this->payment, $isCallback);
        }

        return $response;
    }

    /**
     * A wrapper over the validatePushPayment, which check for pending payment
     * We need to create pending payment in our system with later will be
     * marked Failed by authorizePush function
     */
    protected function validateAuthorizePushPayment(array $callbackData, Terminal\Entity $terminal)
    {
        try
        {
            // We do not need to validate the payment back to gateway if it is callback form recon
            // The problem is that we might not be able to verify the payment if this is multiple credit
            if (Reconciliate::$isReconRunning === true)
            {
                return;
            }

            $this->validatePushPayment($callbackData, $terminal);
        }
        catch (Exception\GatewayErrorException $exception)
        {
            // Currently MindGate is throwing the exception for pending, and Axis does not
            // In both cases, it will be considered valid authorize push payment
            if ($exception->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING)
            {
                return;
            }

            throw $exception;
        }
    }

    public function validatePushPayment(array $callbackData, Terminal\Entity $terminal)
    {
        $mode = $this->app['basicauth']->getMode();

        $gateway = $terminal->getGateway();

        // We validate duplicate unexpected payment creation for amount mismatch in authorizePush
        // We might not be able to verify the payment with gateway if this is multiple credit
        if ((empty($callbackData['meta']['version']) === false) and
            ($callbackData['meta']['version'] === 'api_v2') and
            $gateway === 'upi_icici')
        {
            return;
        }

        $this->app['gateway']->call($gateway, Payment\Action::VALIDATE_PUSH, $callbackData, $mode, $terminal);
    }

    public function createPaymentFromS2SCallback(
        array $callbackData,
        array $paymentInput,
        Terminal\Entity $terminal)
    {
        $gatewayInput = [
            'terminal_id'       => $terminal->getId(),
            'skip_gateway_call' => true,
        ];

        $this->process($paymentInput, $gatewayInput);

        $payment = $this->getPayment();

        return $payment;
    }

    public function authorizePushPayment(
        Payment\Entity $payment,
        array $callbackData)
    {
        try
        {
            $input = [$payment->getId(), $callbackData];

            $data = $this->callGatewayFunction(Payment\Action::AUTHORIZE_PUSH, $input);

            $this->processAuth($payment, $data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            $ex = new Exception\BadRequestException($errorCode);

            $this->updatePaymentFailed($ex, TraceCode::PAYMENT_AUTH_FAILURE);

            throw $ex;
        }
    }
}
