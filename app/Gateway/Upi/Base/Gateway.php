<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    const ACQUIRER = null;

    /**
     * Used in Mock\GatewayTrait, but defined here because
     * traits can't define constants
     */
    const MOCK_ROUTE    = 'mock_upi_payment';

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $attr = $this->getMappedAttributes($attributes);

        $entity = $this->getNewGatewayPaymentEntity();

        $action = $action ?? $this->action;

        if (isset($this->input['payment']['id']) === true)
        {
            $entity->setPaymentId($this->input['payment']['id']);
        }

        switch ($action)
        {
            case Base\Action::REFUND:

                $entity->setRefundId($this->input['refund']['id']);

                $entity->setAmount($this->input['refund']['amount']);

                break;

            case Base\Action::AUTHORIZE:

                $entity->setAmount($this->input['payment']['amount']);
            default:
        }

        $entity->setAction($action);

        $entity->setAcquirer(static::ACQUIRER);

        $entity->generate($attr);

        $entity->fill($attr);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }
}
