<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Models\Merchant;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setBank($this->input['payment']['bank']);

        if (($this->action === Action::AUTHORIZE) and
            ($this->input['merchant']->isTPVRequired()))
        {
            $gatewayPayment->setAccountNumber($this->input['order']['account_number']);
        }

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $paymentId)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->fill($attributes);

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setBank($this->bank);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getRepository()
    {
        $gateway = 'netbanking';

        return $this->app['repo']->$gateway;
    }

    protected function setTpv(Entity $gatewayPayment)
    {
        $this->tpv = $gatewayPayment->isTpv();
    }

    public function isPaymentTpvEnabled(Entity $gatewayPayment, Merchant\Entity $merchant)
    {
        if (($gatewayPayment->isTpv()) or ($merchant->isTPVRequired()))
        {
            return true;
        }

        return false;
    }
}
