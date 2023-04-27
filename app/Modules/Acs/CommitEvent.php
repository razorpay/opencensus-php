<?php

namespace RZP\Modules\Acs;

use RZP\Events\Event;
use RZP\Models\Base\PublicEntity;

class CommitEvent extends Event
{
    public $eventId;
    public $entity;
    public $operation;

    public function __construct(PublicEntity $entity, $operation)
    {
        $this->eventId = uniqid();
        $this->entity = $entity;
        $this->operation = $operation;
    }
}
