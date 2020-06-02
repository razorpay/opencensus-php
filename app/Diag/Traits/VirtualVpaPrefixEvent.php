<?php


namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Diag\Event\VirtualVpaPrefixEvent as VVPEvent;

trait VirtualVpaPrefixEvent
{
    public function trackVirtualVpaPrefixEvent(
        array $eventData,
        Merchant\Entity $merchant = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new VVPEvent($merchant, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(VVPEvent::EVENT_TYPE, VVPEvent::EVENT_VERSION, $eventData, $properties);
    }
}
