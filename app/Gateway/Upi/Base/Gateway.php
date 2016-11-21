<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    const ACQUIRER = null;

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $action = $action ? $action : $this->action;

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAmount($this->input['payment']['amount']);

        $payment->setAction($action);

        $payment->setAcquirer(static::ACQUIRER);

        $payment->fill($attr);

        $this->repo->saveOrFail($payment);

        return $payment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }
}
