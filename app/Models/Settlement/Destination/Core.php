<?php

namespace RZP\Models\Settlement\Destination;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;

class Core extends Base\Core
{
    /**
     * registers an entry representing the destination for given settlement
     * this will create a new entry in the settlement_destination table
     * while doing this, it will mark the older entities related to given settlement as deleted
     * ensuring there is always one active destination for settlement
     *
     * @param Settlement\Entity $settlement
     * @param Base\Entity $destination
     */
    public function register(Settlement\Entity $settlement, Base\Entity $destination)
    {
        $this->markPreviousDestinationAsDeleted($settlement);

        $entity = new Entity;

        $entity->settlement()->associate($settlement);

        $entity->destination()->associate($destination);

        $entity->generateId();

        $this->repo->saveOrFail($entity);

        $this->trace->info(TraceCode::SETTLEMENT_DESTINATION,
            [
                'action'           => 'create',
                'settlement_id'    => $entity->getSettlementId(),
                'destination_type' => $entity->getDestinationType(),
                'destination_id'   => $entity->getDestinationId(),
            ]);
    }

    /**
     * It will mark the latest entry for given settlement as deleted
     *
     * @param Settlement\Entity $settlement
     */
    protected function markPreviousDestinationAsDeleted(Settlement\Entity $settlement)
    {
        $destination = $this->repo
                            ->settlement_destination
                            ->fetchActiveDestination($settlement->getId());

        if ($destination !== null)
        {
            $this->repo
                 ->settlement_destination
                 ->deleteOrFail($destination);

            $this->trace->info(TraceCode::SETTLEMENT_DESTINATION,
                [
                    'action'           => 'delete',
                    'settlement_id'    => $destination->getSettlementId(),
                    'destination_type' => $destination->getDestinationType(),
                    'destination_id'   => $destination->getDestinationId(),
                ]);
        }
    }
}
