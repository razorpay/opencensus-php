<?php

namespace  RZP\Models\Promotion\Event;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'promotion_event';

    public function getExistingEventWithSimilarDetails(array $input)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $input[Entity::NAME])
                    ->get();
    }

    public function getEventByName(string $name)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $name)
                    ->first();
    }
}
