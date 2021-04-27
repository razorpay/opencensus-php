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

        $nodalBeneficiary = $this->core()->updateNodalBeneficiaryWithBankAccount($input);

        return $nodalBeneficiary->toArrayAdmin();
    }

    public function createOrUpdateNodalBeneficiary(array $input): array
    {
        $this->trace->info(TraceCode::FTS_CREATE_OR_UPDATE_NODAL_BENEFICIARY, $input);

        $nodalBeneficiary = $this->core()->createOrUpdateBeneficiaryForFTS($input);

        return $nodalBeneficiary->toArrayAdmin();
    }

    public function fetchNodalBeneficiaryCode(array $input): array
    {
        $this->trace->info(TraceCode::FTS_FETCH_NODAL_BENEFICIARY, $input);

        return $this->core()->fetchNodalBeneficiaryCode($input);
    }

}
