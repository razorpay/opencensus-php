<?php


namespace RZP\Diag\Traits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Diag\Event\BankTransferEvent as BTEvent;

trait BankTransferEvent
{
    public function trackBankTransferRequestEvent(
        array $eventData,
        BankTransfer\Entity $bankTransfer = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new BTEvent($bankTransfer, $ex, $customProperties);

        $properties = $event->getProperties();

        $this->trackEvent(BTEvent::EVENT_TYPE, BTEvent::EVENT_VERSION, $eventData, $properties);
    }

    public function trackBankTransferEvent(
        array $eventData,
        BankTransfer\Entity $bankTransfer = null,
        \Throwable $ex = null,
        array $customProperties = []
    )
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $customProperties += ['timestamp' => $timestamp];

        $event = new BTEvent($bankTransfer, $ex, $customProperties);

        $event->addCustomProperties();

        $properties = $event->getProperties();

        $this->trackEvent(BTEvent::EVENT_TYPE, BTEvent::EVENT_VERSION, $eventData, $properties);
    }
}
