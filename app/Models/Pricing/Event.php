<?php

namespace RZP\Models\Pricing;

class Event
{
    /** @var Entity */
    public $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}
