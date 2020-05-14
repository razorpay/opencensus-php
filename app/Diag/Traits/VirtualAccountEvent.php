<?php


namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount;
use RZP\Diag\Event\VirtualAccountEvent as VAEvent;

trait VirtualAccountEvent
{
    public function trackVirtualAccountEvent(
        array $eventData,
        VirtualAccount\Entity $virtualAccount = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new VAEvent($virtualAccount, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(VAEvent::EVENT_TYPE, VAEvent::EVENT_VERSION, $eventData, $properties);
    }
}
