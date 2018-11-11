<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function create(array $input): array
    {
        $this->initialize(Action::CREATE, $input);

        return [];
    }

    public function validate(array $input): array
    {
        $this->initialize(Action::VALIDATE, $input);

        return [];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [];
    }
}
