<?php

namespace RZP\Diag\Event;

use RZP\Constants\Entity;

class PaymentPageEvent extends Event
{
    const EVENT_TYPE = 'payment_page_events';

    const EVENT_VERSION = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addMerchantDetails($properties);

        $this->addPaymentPageDetails($properties);

        return $properties;
    }

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->entity->merchant;

        $properties[Entity::MERCHANT] = [
            'id'        => $merchant->getId(),
        ];
    }

    private function addPaymentPageDetails(array &$properties)
    {
        $paymentPage = $this->entity;

        $properties['payment_page'] = [
            'id'       => $paymentPage->getPublicId(),
        ];
    }
}
