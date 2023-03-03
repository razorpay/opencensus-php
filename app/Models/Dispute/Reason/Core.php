<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Dispute\Constants;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_REASON_CREATE,
            [
                'input'       => $input,
            ]);

        $reason = (new Entity)->build($input);

        return $this->repo->transaction(function() use ($reason){
             $this->repo->saveOrFail($reason);

             $reason->refresh();
             $this->app['disputes']->sendDualWriteToDisputesService($reason->toDualWriteArray(), Table::DISPUTE_REASON, Constants::CREATE);
             return $reason;
        });
    }
}
