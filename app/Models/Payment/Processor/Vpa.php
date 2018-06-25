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

        $gateway = Payment\Gateway::getGatewayForValidateVpaForMode($this->mode);

        $terminal = $this->repo->terminal->getSharedTerminalForGateway($gateway)
                                         ->first();

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
