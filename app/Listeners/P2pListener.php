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

        // Why? When the event is constructed(somewhere) the entity is set(e.g.
        // to transaction). Then before actually firing event from there the
        // entity is mutated(e.g. associating upi) and which is needed to
        // handle the event. Such interference or dependency should not exist
        // in first place. We can not take entity id and reload or refresh as
        // it requires fixes(for p2p connection name and primary key is
        // overridden for entities) and is not optimal for in-sync event
        // listeners. Now this clone is required because this event handler too
        // mutates entity states(e.g. loaded relationship) which affect final
        // api response(unravel..). Hope this comment helps.
        $this->event->entity = clone $this->event->entity;

        $this->mode = $event->context->getMode();

        $this->merchant = $event->context->getMerchant();

        $this->device   = $event->context->getDevice();
    }

    protected function getMerchant()
    {
        return $this->merchant;
    }

    protected function getMode()
    {
        return $this->mode;
    }
}
