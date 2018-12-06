<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode as TraceCode;
use Razorpay\Trace\Logger as Trace;

trait AuthorizePush
{
    public function authorizePush(array $callbackData, string $referenceId, string $gateway)
    {
        try
        {
            $gatewayClass = $this->app['gateway']->gateway($gateway);

            $data = $gatewayClass->getParsedDataFromUnexptectedCallback($callbackData);

            $paymentInput = $data['payment'];

            $terminalData = $data['terminal'];

            $terminal = $this->repo->terminal->findByGatewayAndTerminalData($terminalData, $gateway);

            $mutexResource = 'unexpected_' . $gateway . '_' . $referenceId;

            $success = $this->app['api.mutex']->acquireAndRelease(
                $mutexResource,
                function() use ($gateway, $callbackData, $terminal, $paymentInput)
                {
                    $this->validatePushPayment($gateway, $callbackData, $terminal);

                    $this->createPaymentFromS2SCallback($gateway, $callbackData, $paymentInput, $terminal);

                    return $this->authorizePushPayment($this->payment, $callbackData);
                });
        }
        catch (\Throwable $ex)
        {
            $this->app['trace']->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::GATEWAY_UNEXPECTED_PAYMENT_ERROR,
                [
                    'gateway'    => $gateway,
                    'payment_id' => $referenceId
                ]
            );

            $success = false;
        }

        return ['success' => $success];
    }

    protected function validatePushPayment(string $gateway, array $callbackData, Terminal\Entity $terminal)
    {
        $mode = $this->app['basicauth']->getMode();

        $this->app['gateway']->call($gateway, Payment\Action::VALIDATE_PUSH, $callbackData, $mode, $terminal);
    }

    protected function createPaymentFromS2SCallback(
        string $gateway,
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

    protected function authorizePushPayment(
        Payment\Entity $payment,
        array $callbackData)
    {
        $success = false;

        try
        {
            $this->repo->transaction(function() use ($payment, $callbackData)
            {
                // authorize on gateway
                $input = [$payment->getId(), $callbackData];

                $this->callGatewayFunction(Payment\Action::AUTHORIZE_PUSH, $input);

                // authorize on api
                $this->processAuth($payment);
            });

            $success = true;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            $ex = new Exception\BadRequestException($errorCode);

            $this->updatePaymentFailed($ex, TraceCode::PAYMENT_AUTH_FAILURE);
        }

        return $success;
    }
}
