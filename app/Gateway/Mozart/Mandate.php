<?php

namespace RZP\Gateway\Mozart;

class Mandate extends Gateway
{
    public function mandateCreate($input)
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        return $gateway->mandateCreate($input);
    }

    public function setMandateParams($action)
    {
        $this->action = $action;

        return $this;
    }

    public function preProcessMandateCallback($input, $gateway)
    {
        $gatewayClass = $this->app['gateway']->gateway('mozart');

        return $gatewayClass->preProcessMandateCallback($input, $gateway);
    }

    public function getPaymentIdFromMandateCallback($input, $gateway)
    {
        $gatewayClass = $this->app['gateway']->gateway('mozart');

        return $gatewayClass->getPaymentIdFromMandateCallback($input, $gateway);
    }

    public function callback($input)
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        return $gateway->callback($input);
    }

    public function mandateExecute($input)
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        return $gateway->mandateExecute($input);
    }

    public function mandateUpdate($input)
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        return $gateway->mandateUpdate($input);
    }
}
