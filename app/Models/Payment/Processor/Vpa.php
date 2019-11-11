<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

trait Vpa
{
    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function validateVpa(array $input)
    {
        $action = Payment\Action::VALIDATE_VPA;

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);

        $terminalIds = Payment\Gateway::getTerminalsForValidateVpaForMode($this->mode);

        $terminals = $this->repo->terminal->findManyEnabledByIds($terminalIds);

        $count = count($terminals);

        if ($count < 1)
        {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR);
        }

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;

        $response = $input;

        $success = false;

        $gatewayResponse = null;

        foreach ($terminals as $index => $terminal)
        {
            try
            {
                $gateway = $terminal->getGateway();

                // Invalid vpa on MindGate and SBI thrown back with GatewayError
                $gatewayResponse = $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);

                $success = true;

                break;
            }
            catch (Exception\GatewayErrorException $exception)
            {
                // As of now, MindGate sends INVALID VPA code when gateway returns code VN.
                // SBI does not have this check, but we currently do not need that.
                // Now, If the code is INVALID VPA, we can skip calling next VPA.
                if ($exception->getCode() === ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA)
                {
                    break;
                }

                $this->trace->traceException($exception, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION);

                // Throwing gateway exception now as we couldn't validate vpa on any of the applicable terminals
                if ($index === ($count - 1))
                {
                    throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
                }
            }
        }

        $response['success'] = $success;

        $response['customer_name'] = $gatewayResponse;

        return $response;
    }
}
