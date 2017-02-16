<?php

namespace RZP\Events;

use App;
use RZP\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AuditLogEntry extends Event
{
    use SerializesModels;

    public $admin;

    public $action;

    public $customProperties;

    /**
     * Create a new event instance.
     *
     * @param $admin
     * @param $action
     * @param $customProperties
     */
    public function __construct($admin, $action, $customProperties = null)
    {
        $this->admin = $admin;

        $this->action = $action;

        $this->customProperties = $customProperties;
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
}
