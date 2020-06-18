<?php

namespace RZP\Diag\Event;

class PaymentConfigEvent extends Event
{
    const EVENT_TYPE = 'payment-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addPaymentConfigDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->entity->merchant;

        $properties['merchant'] = [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
                'tpv'       => $merchant->isTPVRequired()
        ];
    }

    private function addPaymentConfigDetails(array &$properties)
    {
        $config = $this->entity;

        $properties['payment_config'] = [
                'id'       => $config->getId(),
                'type'   => $config->getType(),
                'config' => $config->getConfig()
        ];
    }
}
