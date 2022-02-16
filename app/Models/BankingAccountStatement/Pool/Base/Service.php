<?php

namespace RZP\Models\BankingAccountStatement\Pool\Base;

use Cache;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->fetchAccountStatementV2($input);

        return $response;
    }
}
