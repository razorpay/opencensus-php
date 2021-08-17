<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(string $merchantId, array $input)
    {
        $riskNotes = $this->core()->createRiskNote($merchantId, $input);

        return $riskNotes;
    }

    public function getAll(string $merchantId, $input)
    {
        $response = $this->core()->getAll($merchantId, $input);

        return $response;
    }

    public function delete(string $merchantId, string $id)
    {
        $this->core()->delete($merchantId, $id);

        return [];
    }
}
