<?php

namespace RZP\Gateway\UPI\Base;

use RZP\Gateway\Base;
use RZP\Gateway\UPI;

class Gateway extends Base\Gateway
{
    const BANK = null;

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAmount($this->input['payment']['amount']);

        $payment->setAction($this->action);

        $payment->setBank(static::BANK);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes)
    {
        $refund = $this->getNewGatewayPaymentEntity();

        $refund->fill($attributes);

        $refund->saveOrFail();

        return $refund;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new UPI\Base\Entity;
    }

    protected function getReverseMappedAttributes($attributes)
    {
        $attr = [];

        $map = array_flip($this->map);

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
}