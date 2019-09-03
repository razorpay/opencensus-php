<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->processStatementForAccount($input);

        return $response;
    }

    public function generateAccountStatement(array $input)
    {
        $response = $this->core()->generateBankAccountStatement($input);

        return $response;
    }
}