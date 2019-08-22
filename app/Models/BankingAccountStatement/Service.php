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
        $account_number = array_pull($input, Entity::ACCOUNT_NUMBER);
        $channel = array_pull($input, Entity::CHANNEL);
        $format = array_pull($input, Entity::FORMAT);
        $file_handle = $this->core()->generateBankAccountStatementPdf($account_number, $channel, $format);
        return $file_handle;

    }

}
