<?php

namespace RZP\Modules\Acs;

use RZP\Constants\Mode;
use RZP\Events\Event;

class RecordSyncEvent extends Event
{
    public $eventId;
    public $accountId;
    public $mode;

    public function __construct($accountId, $mode = Mode::LIVE)
    {
        $this->eventId = uniqid();
        $this->accountId = $accountId;
        $this->mode = $mode;
    }
}
