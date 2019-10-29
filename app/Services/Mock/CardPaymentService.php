<?php

namespace RZP\Services\Mock;

use RZP\Exception\BaseException;
use RZP\Services\CardPaymentService as BaseCardPaymentService;

class CardPaymentService extends BaseCardPaymentService
{
    public function action(string $gateway, string $action, array $input): array
    {
        if ($action === 'fail')
        {
            $this->throwServiceErrorException(new BaseException('timed out or something'));
        }

        return $input;
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }
}
