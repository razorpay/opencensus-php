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

        $payment->generate($attr);

        $payment->fill($attr);

        $this->repo->saveOrFail($payment);

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $refund = $this->getNewGatewayPaymentEntity();

        $refund->setPaymentId($this->input['payment']['id']);

        $refund->setAmount($this->input['refund']['amount']);

        $refund->generate($attr);

        $refund->setAction(Base\Action::REFUND);

        $refund->setAcquirer(static::ACQUIRER);

        $refund->fill($attr);

        $this->repo->saveOrFail($refund);

        return $refund;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }
}
