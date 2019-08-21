<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use RZP\Models\BankingAccountStatement\StatementGenerator\Factory;


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
        $x = Factory::getStatementGenerator($account_number, $channel, $format);
        return $x->pdf();


    }

}
