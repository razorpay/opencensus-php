<?php

namespace RZP\Models\State\Reason;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'state_reason';

    public function getRejectionReasonAndRejectionCode($merchantId)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->select('action_state_reasons.*') // Select all columns from action_state_reasons
                    ->from('action_state_reasons')
                    ->join('action_state', 'action_state_reasons.state_id', '=', 'action_state.id')
                    ->where('action_state.entity_id', $merchantId)
                    ->where('action_state.name', 'rejected')
                    ->get()
                    ->toArray();
    }

}
