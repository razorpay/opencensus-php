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

    public function updateOrCreate(array $input)
    {
        $address = strtolower(array_pull($input, Entity::VPA));

        // We can now add username and handle to input
        $parsed = Entity::getUsernameAndHandle($address);

        $vpa = $this->repo()->firstByUsernameAndHandle($parsed[Entity::USERNAME], $parsed[Entity::HANDLE]);

        if ($vpa instanceof Entity)
        {
            if ($vpa->getReceivedAt() >= $input[Entity::RECEIVED_AT])
            {
                return $vpa;
            }

            // In case of failures, where we are not sure about VPA's status
            // We will need to save that vpa, but if it is already saved, we
            // will not update the VPA
            if ($input[Entity::STATUS] === Status::UNKNOWN)
            {
                return $vpa;
            }

            $vpa->edit($input);

            $this->repo->saveOrFail($vpa);

            return $vpa;
        }

        $vpa = new Entity();

        $vpa->build(array_merge($input, $parsed));

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    /**
     * @param $address
     * @return Entity
     */
    public function firstByAddress($address)
    {
        $parsed = Entity::getUsernameAndHandle($address);

        $vpa = $this->repo()->firstByUsernameAndHandle($parsed[Entity::USERNAME], $parsed[Entity::HANDLE]);

        return $vpa;
    }

    public function repo(): Repository
    {
        return $this->repo->payments_upi_vpa;
    }
}
