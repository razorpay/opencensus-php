<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function manageBulk(array $input): array
    {
        return $this->retrieveSuccess([Entity::BANKS => $input]);
    }

    protected function retrieveSuccess(array $input): array
    {
        $this->initialize(Action::RETRIEVE_SUCCESS, $input, true);

        $banks = $this->core->createOrUpdateMany($this->input->get(Entity::BANKS));

        return $banks->toArrayPublic();
    }
}
