<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\BankingAccountTpv\Entity;

class BankingAccountTpv extends Base
{
    public function create(array $attributes = [])
    {
        $trimmedPayerAccountNumber = $attributes[Entity::PAYER_ACCOUNT_NUMBER] ?? '9876543210123456789';

        $trimmedPayerAccountNumber = ltrim($trimmedPayerAccountNumber, '0');

        $defaultValues = [
            Entity::TRIMMED_PAYER_ACCOUNT_NUMBER => $trimmedPayerAccountNumber,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $bankingAccountTpv = parent::create($attributes);

        return $bankingAccountTpv;
    }
}
