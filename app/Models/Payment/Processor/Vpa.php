<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Gateway\Upi\Base\Entity;
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

        $gatewayResponse = null;

        foreach ($terminals as $terminal)
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
            }
        }

        $response['success'] = $success;
        $response['customer_name'] = $gatewayResponse;

        return $response;
    }
}
