<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;

trait Vpa
{
    public function validateVpa(array $input)
    {
        $action = Payment\Action::VALIDATE_VPA;

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);

        // Currently we are only using MindGate, later when we have
        // more gateways, we can introduce terminal selection logic
        $gateway = Payment\Gateway::UPI_MINDGATE;

        $terminal = null;

        // For test mode we do not use terminal for MindGate, Later for other
        // gateways we can check if valid terminal is required
        if ($this->mode === Mode::TEST)
        {
            $terminal = $this->repo->terminal->getSharedTerminalForGateway($gateway)
                                             ->first();
        }

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;

        $response = $input;

        try
        {
            // Invalid vpa on MindGate and SBI thrown back with GatewayError
            $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);

            $response['success'] = true;
        }
        catch (Exception\GatewayErrorException $exception)
        {
            $response['success'] = false;
        }

        return $response;
    }
}
