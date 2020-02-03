<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function firstOrCreate(array $input): Entity
    {
        $vpaEntity = new Entity();

        $vpa = $input['vpa'];

        $vpaInput = $vpaEntity->getUsernameAndHandle($vpa);

        $vpa = $this->repo->payments_upi_vpa->firstByUsernameAndHandle($vpaInput['username'], $vpaInput['handle']);

        if ($vpa !== null)
        {
            return $vpa;
        }

        $vpa = $vpaEntity->build($vpaInput);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }
}
