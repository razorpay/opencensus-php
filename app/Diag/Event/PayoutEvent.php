<?php

namespace RZP\Diag\Event;

use RZP\Models\Payout\Entity;

class PayoutEvent extends Event
{
    const EVENT_TYPE = 'payouts';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        //In case of entity specific events entity details will be included but for fetch_multiple entities won't be included
        if(isset($this->entity) === true)
        {
            $this->addPayoutDetails($properties);
        }

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->entity->merchant;

        $properties['merchant'] = [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
        ];
    }

    private function addPayoutDetails(array &$properties)
    {
        $payout = $this->entity;

        $properties['payout'] = [
                'id'           => $payout->getPublicId(),
                'amount'       => $payout->getAmount(),
                'status'       => $payout->getStatus(),
                'created_at'   => $payout[Entity::CREATED_AT],
                'created_by'   => $payout['user_id'] ?: 'api_user'
        ];
    }
}
