<?php


namespace RZP\Diag\Event;


class RewardEvent extends Event
{
    const EVENT_TYPE        = 'payment-event';

    const EVENT_VERSION     = 'v2';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addRewardDetails($properties);

        return $properties;
    }

    private function addRewardDetails(array & $properties)
    {
        $reward = $this->entity;

        if ($reward !== null)
        {
            $properties['reward'] = [
                'id'        => $reward->getPublicId(),

                'advertiser_id' => $reward->getAdvertiserId(),
            ];
        }
    }

}
