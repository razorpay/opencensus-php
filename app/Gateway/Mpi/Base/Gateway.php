<?php

namespace RZP\Gateway\Mpi\Base;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    protected function createGatewayPaymentEntity(array $attributes, array $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateGatewayPaymentFromCallbackResponse(
        Entity $gatewayPayment,
        array $response)
    {
        $attributes = $this->getCallbackResponseAttributes($response);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'mpi';

        return $this->app['repo']->$gateway;
    }
}
