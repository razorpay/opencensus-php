<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property Core $core
 * @property Validator $validator
 */
class Processor extends Base\Processor
{
    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input, true);

        $input[Entity::ACTIVE] = true;

        $handles = $this->core->fetchAll($input);

        return $handles->toArrayPublic();
    }
}
