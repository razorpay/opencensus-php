<?php

namespace RZP\Models\Merchant\Detail;

use Jitendra\Lqext\TransactionAware;

class Event
{
    use TransactionAware;

    /** @var Entity */
    public $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}
