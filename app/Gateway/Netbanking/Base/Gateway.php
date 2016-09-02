<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    protected $gateway = 'netbanking';

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAction($this->action);

        $payment->setBank($this->input['payment']['bank']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function getRepository()
    {
        $gateway = 'netbanking';

        return $this->app['repo']->$gateway;
    }
}
