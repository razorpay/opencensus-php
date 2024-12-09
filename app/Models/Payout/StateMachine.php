<?php

namespace RZP\Models\Payout;

use App;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use Database\Connection;
use RZP\Exception\InvalidArgumentException;

class StateMachine {
    protected $app;
    protected $trace;
    protected $transitionMap;
    protected $currentState;


    public function __construct($currentState) {

        $this->app = App::getFacadeRoot();

        $this->trace  = $this->app['trace'];

        $this->currentState = $currentState;
    }

    // Perform the state transition
    /**
     * @throws InvalidArgumentException
     */
    public function transition($entity, $nextState){

        $entityType = $entity->getTable();

        // Check if the transition is valid
        if (!$this->canTransition($entityType, $this->currentState, $nextState)) {
            throw new InvalidArgumentException("Invalid transition from {$this->currentState} to $nextState for entity type: $entityType");
        }

        // Execute the state change
        $this->updateStatus($entity, $entityType, $nextState);

    }

    protected function updateStatus($entity, $entityType, $nextState) {

        $connection = \DB::connection();
        $connection->table($entityType)
            ->where(Entity::ID, $entity->id)
            ->update([Entity::STATUS => $nextState]);

        $this->trace->info(
            TraceCode::STATE_MACHINE_STATUS_UPDATE_SUCCESS,
            [
                'message' => 'State transition successful',
                'entity' => $entityType,
                'id' => $entity->id,
                'from' => $this->currentState,
                'to' => $nextState,
            ]);

        $this->currentState = $nextState;
    }

    protected function canTransition($entityType, $currentState, $nextState): bool
    {
        return isset($this->transitionMap[$entityType][$currentState]) &&
            in_array($nextState, $this->transitionMap[$entityType][$currentState]);
    }

    public function getCurrentState() {
        return $this->currentState;
    }
}
