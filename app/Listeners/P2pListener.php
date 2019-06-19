<?php

namespace RZP\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Events;
use RZP\Models\P2p;
use RZP\Models\Merchant;
use RZP\Foundation\Application;

class P2pListener
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Events\P2p\Event
     */
    protected $event;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var P2p\Device\Entity
     */
    protected $device;

    /**
     * @var P2p\Transaction\Entity
     */
    protected $transaction;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = app();
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $this->event = $event;

        $this->mode = $event->context->getMode();

        $this->merchant = $event->context->getMerchant();

        $this->device   = $event->context->getDevice();
    }

    protected function getMerchant()
    {
        return $this->merchant;
    }

    protected function getWebhook()
    {
        return $this->merchant->webhooks()->where('active', 1)->latest()->first();
    }

    protected function getMode()
    {
        return $this->mode;
    }
}
