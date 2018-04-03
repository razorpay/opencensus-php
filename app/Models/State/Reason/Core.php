<?php

namespace RZP\Models\State\Reason;

use RZP\Models\Base;
use RZP\Models\State\Entity as StateEntity;

class Core extends Base\Core
{
    public function create(array $input, StateEntity $state): Entity
    {
        (new Validator)->checkValidRejectionReason($input, $state);

        $reason = (new Entity)->build($input);

        $reason->state()->associate($state);

        $this->repo->saveOrFail($reason);

        return $reason;
    }

    public function addRejectionReasons(array $rejectionReasons, StateEntity $state)
    {
        foreach ($rejectionReasons as $rejectionReason)
        {
            $rejectionReason[Entity::REASON_TYPE] = ReasonType::REJECTION;

            $this->create($rejectionReason, $state);
        }
    }
}
