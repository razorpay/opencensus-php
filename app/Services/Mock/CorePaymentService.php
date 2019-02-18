<?php

namespace RZP\Services\Mock;

use RZP\Exception\BaseException;
use RZP\Services\CorePaymentService as BaseCorePaymentService;

class CorePaymentService extends BaseCorePaymentService
{
    public function action(string $gateway, string $action, array $input): array
    {
        if ($action === 'fail')
        {
            $this->throwServiceErrorException(new BaseException('timed out or something'));
        }

        return $input;
    }
}
