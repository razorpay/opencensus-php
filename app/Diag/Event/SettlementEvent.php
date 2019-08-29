<?php

namespace RZP\Diag\Event;

class SettlementEvent extends Event
{
    const EVENT_TYPE = 'settlement-events';
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
            'merchant_id'        => $merchant->getId(),
        ];
    }

    private function addSettlementDetails(array &$properties)
    {
        $settlement = $this->entity;

        $properties['settlement'] = [
            'id'            => $settlement->getId(),
            'amount'        => $settlement->getAmount(),
            'fees'          => $settlement->getFees(),
            'settled_on'    => $settlement->getSettledOn()
        ];
    }
}