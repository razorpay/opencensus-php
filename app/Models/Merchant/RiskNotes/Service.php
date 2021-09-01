<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(string $merchantId, array $input)
    {
        $riskNote = $this->core()->createRiskNote($merchantId, $input);

        return $riskNote->toArrayPublicWithExpand();
    }

    public function getAll(string $merchantId, $input)
    {
        $riskNotes = $this->core()->getAll($merchantId, $input);

        return $riskNotes->toArrayPublicWithExpand();
    }

    public function delete(string $merchantId, string $id)
    {
        $this->core()->delete($merchantId, $id);

        return [];
    }
}
