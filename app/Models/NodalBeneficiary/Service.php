<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    /**
     * @param array $input
     * @return array
     */
    public function update(array $input): array
    {
        $this->trace->info(TraceCode::UPDATE_NODAL_BENEFICIARY, $input);

        $nodalBeneficiary = $this->core()->update($input);

        return $nodalBeneficiary->toArrayAdmin();
    }
}
