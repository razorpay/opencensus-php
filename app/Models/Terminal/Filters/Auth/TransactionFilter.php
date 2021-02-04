<?php

namespace RZP\Models\Terminal\Filters\Auth;

use RZP\Models\Terminal;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'gateway',
        'capability',
        'google_pay',
        'visa_safe_click',
        'visa_safe_click_step_up',
    ];

    public function gatewayFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === false)
        {
            return true;
        }

        return ($payment->getGateway() === $terminal['gateway']);
    }

    public function capabilityFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === false)
        {
            return true;
        }

        if (isset($terminal['capability']) === false)
        {
            return true;
        }

        return ($payment->terminal->getCapability() === $terminal['capability']);
    }

    public function googlePayFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isGooglePayCard() === true)
        {
            if ($terminal['authentication_gateway'] === 'google_pay')
            {
                return true;
            }

            return false;
        }
        elseif ($terminal['authentication_gateway'] === 'google_pay')
        {
            return false;
        }

        return true;
    }

    public function visaSafeClickFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isVisaSafeClickPayment() === true)
        {
            if ($terminal['authentication_gateway'] === 'visasafeclick')
            {
                return true;
            }

            return false;
        }
        else
        {
            if ($terminal['authentication_gateway'] === 'visasafeclick')
            {
                return false;
            }
        }

        return true;
    }

    public function visaSafeClickStepUpFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->getApplication() === 'visasafeclick_stepup')
        {
            if ($terminal['authentication_gateway'] === null)
            {
                return true;
            }

            return false;
        }

        return true;
    }
}
