<?php


namespace RZP\Diag\Event;


class IINEvent extends Event
{
    const EVENT_TYPE        = 'iin-events';

    const EVENT_VERSION     = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addIINDetails($properties);

        return $properties;
    }

    private function addIINDetails(array & $properties)
    {
        $iin = $this->entity;

        if ($iin !== null)
        {
            $properties['iin'] = [
                'id'   => $iin->getIin(),
            ];
        }
    }
}
