<?php

namespace Gateway\Wallet\Base;

use Gateway\Wallet;

class Gateway extends \Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAction($this->action);

        $payment->setBank($this->input['payment']['wallet']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Wallet\Base\Entity;
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

    protected function getRepo()
    {
        return new Repository();
    }
}
