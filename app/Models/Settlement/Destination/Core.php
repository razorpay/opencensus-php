<?php

namespace RZP\Models\Settlement\Destination;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Core extends Base\Core
{
    public function register(Settlement\Entity $settlement, Base\Entity $destination)
    {
        $this->markPreviousDestinationAsDeleted($settlement);

        $entity = new Entity;

        $entity->settlement()->associate($settlement);

        $entity->destination()->associate($destination);

        $entity->generateId();

        $this->repo->saveOrFail($entity);
    }

    protected function markPreviousDestinationAsDeleted(Settlement\Entity $settlement)
    {
        $destination = $this->repo
                            ->settlement_destination
                            ->fetchActiveDestination($settlement->getId());

        if ($destination->isEmpty() !== true)
        {
            $this->repo
                 ->settlement_destination
                 ->deleteOrFail($destination);
        }
    }
}
