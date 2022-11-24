<?php

namespace RZP\Events\P2p;

use App;
use RZP\Events;
use RZP\Models\P2p\Base;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use RZP\Models\P2p\Base\Libraries\Context;

abstract class Event extends Events\Event
{
    use SerializesModels;

    /**
     * @var Context
     */
    public $context;

    /**
     * @var Base\Entity
     */
    public $entity;

    public $original;
    abstract public function getWebhookPaylaod();

    abstract public function getNotificationPayload();

    abstract public function getReminderPayload();

    public function postHandle() { return; }

    public function __construct(Context $context, Base\Entity $entity)
    {
        $this->context = $context;

        $this->entity  = $entity;

        $this->setOriginal($this->entity->getRawOriginal());
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getName()
    {
        return static::class;
    }

    protected function setOriginal(array $original = null)
    {
        return $this;
    }
}
