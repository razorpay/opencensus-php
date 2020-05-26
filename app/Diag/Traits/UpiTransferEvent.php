<?php


namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\UpiTransfer;
use RZP\Diag\Event\UpiTransferEvent as UTEvent;

trait UpiTransferEvent
{
    public function trackUpiTransferRequestEvent(
        array $eventData,
        UpiTransfer\Entity $upiTransfer = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new UTEvent($upiTransfer, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(UTEvent::EVENT_TYPE, UTEvent::EVENT_VERSION, $eventData, $properties);
    }

    public function trackUpiTransferEvent(
        array $eventData,
        UpiTransfer\Entity $upiTransfer = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new UTEvent($upiTransfer, $ex, $customProperties);

        $event->addCustomProperties();

        $properties = $event->getProperties();

        $this->trackEvent(UTEvent::EVENT_TYPE, UTEvent::EVENT_VERSION, $eventData, $properties);
    }
}
