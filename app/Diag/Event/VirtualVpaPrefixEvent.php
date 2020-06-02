<?php


namespace RZP\Diag\Event;


class VirtualVpaPrefixEvent extends Event
{
    const EVENT_TYPE        = 'virtual-vpa-prefix-events';

    const EVENT_VERSION     = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addMerchantDetails(array & $properties)
    {
        $merchant = $this->entity;

        if ($merchant !== null)
        {
            $properties['merchant'] = [
                'id' => $merchant->getId(),
            ];
        }
    }
}
