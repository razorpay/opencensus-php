<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

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

        $this->repo->saveOrFail($reason);

        return $reason;
    }
}
