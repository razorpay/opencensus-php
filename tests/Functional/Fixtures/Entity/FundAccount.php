<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class FundAccount extends Base
{
    public function createBankAccount(array $attributes = [], array $bankAccountAttributes = null)
    {
        // Why ?: operator is used below & $bankAccountAttributes defaults to null, check Fixtures/Fixtures::create().
        $bankAccount = $this->fixtures->create('bank_account', $bankAccountAttributes ?: []);

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

    public function createCard(array $attributes = [], array $cardAttributes = null)
    {
        // Why ?: operator is used below & $bankAccountAttributes defaults to null, check Fixtures/Fixtures::create().
        $card = $this->fixtures->create('card', $cardAttributes ?: []);

        $defaultAttrs = [
            'account_id'   => $card['id'],
            'account_type' => 'card',
        ];

        return parent::create(array_merge($attributes, $defaultAttrs));
    }

    public function createWalletAccount(array $attributes = [])
    {
        $walletAccount = $this->fixtures->create('wallet_account');

        $defaultAttrs = [
            'account_id'   => $walletAccount['id'],
            'account_type' => 'wallet_account',
        ];

        return parent::create(array_merge($attributes, $defaultAttrs));
    }
}
