<?php

namespace RZP\Gateway\Esigner\Base;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $action = $action ?: $this->action;

        $attr = $this->getMappedAttributes($attributes);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setGateway($this->gateway);

        $gatewayPayment->setAction($action);

        $gatewayPayment->fill($attr);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'esigner';

        return $this->app['repo']->$gateway;
    }
}
