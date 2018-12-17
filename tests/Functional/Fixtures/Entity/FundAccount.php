<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class FundAccount extends Base
{
    public function createBankAccount(array $attributes = [])
    {
        $bankAccount = $this->fixtures->create('bank_account');

        $defaultAttrs = [
            'account_id'   => $bankAccount['id'],
            'account_type' => 'bank_account',
        ];

        return parent::create(array_merge($attributes, $defaultAttrs));
    }

    public function createVpa(array $attributes = [])
    {
        $vpa = $this->fixtures->create('vpa');

        $defaultAttrs = [
            'account_id'   => $vpa['id'],
            'account_type' => 'vpa',
        ];

        return parent::create(array_merge($attributes, $defaultAttrs));
    }
}
