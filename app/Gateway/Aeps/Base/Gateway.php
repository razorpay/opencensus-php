<?php

namespace RZP\Gateway\Aeps\Base;

use RZP\Gateway\Base\Gateway as BaseGateway;

class Gateway extends BaseGateway
{
    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'aeps';

        return $this->app['repo']->$gateway;
    }

    protected function createGatewayPaymentEntity($input, $requestData)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        $gatewayPayment->setAadhaarNumber($input['aadhaar_number']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->setCounter($requestData[RequestConstants::COUNTER]);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }
}