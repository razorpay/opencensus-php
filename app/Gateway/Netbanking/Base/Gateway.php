<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Models\Payment;
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

    public function generateClaims(array $input)
    {
        $paymentIds = array_map(function($row)
        {
            return $row['payment']['id'];
        }, $input['data']);

        $gatewayPayments = $this->repo->fetchByPaymentIdsAndAction(
                                $paymentIds, Action::AUTHORIZE);

        // payment id is key and gatewayPayment entity is value
        $gatewayPayments = $gatewayPayments->getDictionaryByAttribute(Entity::PAYMENT_ID);

        // Adding relevant information to each gateway row in $input['data']
        $input['data'] = array_map(function($row) use ($gatewayPayments)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayPayments[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayPayments[$paymentId]->toArray();
            }

            return $row;
        }, $input['data']);

        $namespace = $this->getGatewayNamespace();

        $class = $namespace . '\\' . 'ClaimsFile';

        return (new $class)->generate($input);
    }

    protected function shouldStatusBeUpdated(Entity $gatewayPayment)
    {
        //
        // If the authorize status is set to Y,
        // we are not saving the verify response status
        //
        if ((isset($gatewayPayment[Entity::STATUS]) === true) and
            ($gatewayPayment[Entity::STATUS] === $this->getAuthSuccessStatus()))
        {
            return false;
        }

        return true;
    }

    protected function getAcquirerData($gatewayPayment)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $gatewayPayment->getBankPaymentId()
            ]
        ];
    }
}
