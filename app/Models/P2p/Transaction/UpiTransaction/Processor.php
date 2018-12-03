<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function initiatePay(array $input): array
    {
        $this->initialize(Action::INITIATE_PAY, $input);

        return [];
    }

    public function initiateCollect(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT, $input);

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

    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input);

        return [];
    }

    public function authorize(array $input): array
    {
        $this->initialize(Action::AUTHORIZE, $input);

        return [];
    }

    public function reject(array $input): array
    {
        $this->initialize(Action::REJECT, $input);

        return [];
    }
}
