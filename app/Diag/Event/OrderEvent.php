<?php

namespace RZP\Diag\Event;

class OrderEvent extends Event
{
    const EVENT_TYPE = 'payment-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addOrderDetails($properties);

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

    private function addOrderDetails(array &$properties)
    {
        $order = $this->entity;

        $properties['order'] = [
                'id'       => $order->getPublicId(),
                'amount'   => $order->getAmount(),
                'currency' => $order->getCurrency()
        ];
    }
}
