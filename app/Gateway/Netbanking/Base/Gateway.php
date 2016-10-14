<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment;

class Gateway extends \RZP\Gateway\Base\Gateway
{
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

    protected function getRepository()
    {
        $gateway = 'netbanking';

        return $this->app['repo']->$gateway;
    }

    protected function getCallbackResponseData()
    {
        return [Payment\Entity::TWO_FA_STATUS => Payment\TwoFaStatus::NOT_APPLICABLE];
    }
}
