<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;

use RZP\Models\Base;
use RZP\Models\BankingAccountStatement;

class Service extends Base\Service
{
    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->processStatementForAccount($input);

        return $response;
    }
}
