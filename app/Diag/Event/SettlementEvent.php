<?php

namespace RZP\Diag\Event;

class SettlementEvent extends Event
{
    const EVENT_TYPE = 'payment-events';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addSettlementDetails($properties);

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

    private function addSettlementDetails(array &$properties)
    {
        $settlement = $this->entity;

        $properties['settlement'] = [
            'id'       => $settlement->getId(),
            'amount'   => $settlement->getAmountAttribute(),
            'fees' => $settlement->getFeesAttribute(),
            'settledOn' => $settlement->getSettledOnAttribute()
        ];
    }
}