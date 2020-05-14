<?php


namespace RZP\Diag\Event;


class VirtualAccountEvent extends Event
{
    const EVENT_TYPE        = 'virtual-account-events';

    const EVENT_VERSION     = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addVirtualAccountDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addVirtualAccountDetails(array & $properties)
    {
        $virtualAccount = $this->entity;

        if ($virtualAccount !== null)
        {
            $properties['virtual_account'] = [
                'id' => $virtualAccount->getId(),
            ];
        }
    }

    private function addMerchantDetails(array & $properties)
    {
        $merchant = $this->entity->merchant;

        if ($merchant !== null)
        {
            $properties['merchant'] = [
                'id'        => $merchant->getId(),
                'email'     => $merchant->getEmail(),
            ];
        }
    }
}
