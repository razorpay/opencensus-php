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

    protected function createGatewayPaymentEntity($input, $requestData = [])
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        if (isset($input['aadhaar']) === true)
        {
            $gatewayPayment->setAadhaarNumber($input['aadhaar']['number']);
        }

        if ($this->action === 'refund')
        {
            $gatewayPayment->setAmount($input['refund']['amount']);
        }
        else
        {
            $gatewayPayment->setAmount($input['payment']['amount']);
        }

        $gatewayPayment->setAcquirer($input['terminal']['gateway_acquirer']);

        $gatewayPayment->setAction($this->action);

        if (isset($requestData[RequestConstants::COUNTER]) === true)
        {
            $gatewayPayment->setCounter($requestData[RequestConstants::COUNTER]);
        }

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }
}
