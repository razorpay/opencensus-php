<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->processStatementForAccount($input);

        return $response;
    }

    public function generateAccountStatement(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
                           ['input' => $input]);

        (new Validator)->setStrictFalse()->validateInput(Validator::ACCOUNT_STATEMENT_GENERATE, $input);

        $response = $this->core()->generateBankAccountStatement($input);

        return $response;
    }
}
