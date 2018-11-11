<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function fetchHandles(array $input): array
    {
        $this->initialize(Action::FETCH_HANDLES, $input);

        return [];
    }

    public function create(array $input): array
    {
        $this->initialize(Action::CREATE, $input);

        return [];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [];
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

        return [];
    }

    public function assignBankAccount(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT, $input);

        return [];
    }

    public function checkAvailability(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY, $input);

        return [];
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input);

        return [];
    }
}
