<?php

namespace RZP\Models\P2p\Upi;

use RZP\Exception;
use RZP\Models\P2p\Entity;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Transaction;

/**
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function initiateGatewayCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::INITIATE_GATEWAY_CALLBACK, $input);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function initiateGatewayCallbackSuccess(array $input): array
    {
        $this->initializeApplicationTrait(Action::INITIATE_GATEWAY_CALLBACK_SUCCESS, $input);

        $this->resolveContext();

        return $this->input->toArray();
    }

    public function gatewayCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::GATEWAY_CALLBACK, $input);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function gatewayCallbackSuccess(array $input): array
    {
        $this->initializeApplicationTrait(Action::GATEWAY_CALLBACK_SUCCESS, $input);

        return $this->input->toArray();
    }

    protected function resolveContext()
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ENTITY])
        {
            case Transaction\Entity::TRANSACTION:
                $this->resolveContextFromTransaction($context[Base\Entity::ACTION]);
        }
    }

    public function resolveContextFromTransaction(string $action)
    {
        $context =$this->input->get(Base\Entity::CONTEXT);

        switch ($context[Base\Entity::ACTION])
        {
            case Transaction\Action::INCOMING_COLLECT:

                $payer = $this->input->get(Transaction\Entity::TRANSACTION)[Transaction\Entity::PAYER];

                $this->context()->setHandleAndMode($payer[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromVpa($payer);

                break;

            case Transaction\Action::INCOMING_PAY:

                $payee = $this->input->get(Transaction\Entity::TRANSACTION)[Transaction\Entity::PAYEE];

                $this->context()->setHandleAndMode($payee[Vpa\Entity::HANDLE]);

                $device = $this->resolveDeviceFromVpa($payee);

                break;
        }

        $this->context()->setMerchant($device->merchant);
        $this->context()->setDevice($device);
    }

    protected function resolveDeviceFromVpa(array $input)
    {
        $vpa = (new Vpa\Core)->findByUsernameHandle($input);

        return $vpa->device;
    }

    public function fetchVpaFromTransaction(string $type)
    {
        $plucked = $this->input->get(Transaction\Entity::TRANSACTION)[$type];

        $vpa = (new Vpa\Core)->findByUsernameHandle($plucked);

        return $vpa;
    }

    protected function getGateway()
    {
        if ($this->context()->getHandle() instanceof Vpa\Handle\Entity)
        {
            return parent::getGateway();
        }

        return $this->input->get(Base\Entity::GATEWAY);
    }
}
