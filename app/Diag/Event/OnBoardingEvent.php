<?php

namespace RZP\Diag\Event;

class OnBoardingEvent extends Event
{
    const EVENT_TYPE   = 'onboarding-events';

    const EVENT_VERSION = 'v1';

    protected function addEventDetails()
    {
        $this->properties += $this->addSourceDetails();

        parent::addEventDetails();
    }

    protected function getEventProperties()
    {
        $properties = [];

        $this->addMerchantDetails($properties);

        return $properties;
    }

    private function addMerchantDetails(array &$properties)
    {
        $merchant = $this->entity;

        if ($merchant !== null)
        {
            $properties['merchant'] = [
                'id'    => $merchant->getId(),
                'email' => $merchant->getEmail(),
            ];
        }
    }

    private function addSourceDetails()
    {
        $properties = [];

        if (empty($this->app['basicauth']) === false)
        {
            $properties['source'] = [
                'product'    => $this->app['basicauth']->getRequestOriginProduct(),
            ];
        }

        return $properties;
    }
}
