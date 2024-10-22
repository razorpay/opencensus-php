<?php

namespace RZP\Models\Payout;

use Database\Connection;
use RZP\Trace\TraceCode;

class ManualStateTransitionMachine extends StateMachine {

    public function __construct($currentState) {

        parent::__construct($currentState);

        // Initialization of the Manual transition map
        $this->initializeTransitionMap();
    }

    // Initialize transition map specific to Manual State Transitions.
    // For deleting any entity through Manual State Transition,
    // the entity should be transitioned to 'deleted' state
    protected function initializeTransitionMap() {
        $this->transitionMap = [
            'payouts' => [
                'processed' => ['initiated'],
            ],
            'fund_transfer_attempts' => [
                'processed' => ['initiated'],
            ],
            'payouts_status_details' => [
                'processed' => ['deleted'],
            ],
        ];
    }
    public function transition($entity, $nextState, $postTransitionCallback = null) {

        if ($nextState === 'deleted') {

            $this->deleteEntity($entity);
        }
        else {

            parent::transition($entity, $nextState);
        }

        // If a post-transition callback is provided, invoke it
        if ($postTransitionCallback) {
            call_user_func($postTransitionCallback);
        }
    }
    protected function deleteEntity($entity) {

        $entityType = $entity->getTable();

        // Log the Entity before deleting
        $this->trace->info(
            TraceCode::STATE_MACHINE_ENTITY_DELETION,
            [
                'entity_type' => $entityType,
                'entity' => $entity,
            ]
        );

        $connection = \DB::connection();

        // Delete the entity from the database
        // Hard Deleting the entity from the database as there is no "is_deleted" column in the table
        $connection->table($entityType)
            ->where(Entity::ID, $entity->id)
            ->delete();

        $this->currentState = 'deleted';
    }
}
