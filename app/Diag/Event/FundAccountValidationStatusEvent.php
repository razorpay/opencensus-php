<?php

namespace RZP\Diag\Event;

use RZP\Models\FundAccount\Validation as Fav;

class FundAccountValidationStatusEvent extends Event
{
    const EVENT_TYPE = 'payouts';
    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addFavDetails($properties);

        return $properties;
    }

    private function addFavDetails(array &$properties)
    {
        /** @var Fav\Entity $fav */
        $fav = $this->entity;

        $merchant = $fav->merchant;

        $properties['fav'] = [
            'id'              => $fav->getPublicId(),
            'merchant_id'     => $merchant->getId(),
            'status'          => $fav->getStatus(),
            'account_status'  => $fav->getAccountStatus()
        ];

    }
}
