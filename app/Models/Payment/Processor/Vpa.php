<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

trait Vpa
{
    public function validateVpa(array $input)
    {
        $action = Payment\Action::VALIDATE_VPA;

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);

        $terminalIds = Payment\Gateway::getTerminalsForValidateVpaForMode($this->mode);

        $terminals = $this->repo->terminal->findManyByPublicIds($terminalIds);

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;

        $response = $input;

        $success = false;

        foreach ($terminals as $terminal)
        {
            try
            {
                $gateway = $terminal->getGateway();

                // Invalid vpa on MindGate and SBI thrown back with GatewayError
                $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);

                $success = true;
                break;
            }
            catch (Exception\GatewayErrorException $exception)
            {
                $this->trace->traceException($exception, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION);
            }
        }

        $response['success'] = $success;

        return $response;
    }
}
