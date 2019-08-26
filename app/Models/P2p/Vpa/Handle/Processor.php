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

    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input, true);

        $handle = $this->core->add($this->input->toArray());

        return $handle->toArrayPublic();
    }

    public function update(array $input): array
    {
        $this->initialize(Action::UPDATE, $input, true);

        $handle = $this->core->find($this->input->pull(Entity::CODE));

        $handle = $this->core->update($handle, $this->input->toArray());

        return $handle->toArrayPublic();
    }
}
