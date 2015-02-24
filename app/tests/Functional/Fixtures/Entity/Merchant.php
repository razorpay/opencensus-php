<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Merchant extends Base
{
    public function createDefaultTestMerchant()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000000']);
        $this->fixtures->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('test')->create('balance', ['id' => '10000000000000', 'balance' => '1000000']);
        $this->fixtures->on('live')->create('balance', ['id' => '10000000000000', 'balance' => '0']);
        $this->fixtures->on('test')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');
        $this->fixtures->on('live')->create('bank_account', ['merchant_id' => '10000000000000']);

        $this->fixtures->on('test')->create('merchant:add_payment_banks', ['merchant_id' => '10000000000000']);
    }

    public function createNodalAccount()
    {
        $apiMerchant = $this->create('merchant', ['id' => Account::NODAL_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::NODAL_ACCOUNT, 'balance' => '1000000']);
    }

    public function createAtomAccount()
    {
        $apiMerchant = $this->create('merchant', ['id' => Account::ATOM_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::ATOM_ACCOUNT, 'balance' => '1000000']);
    }

    public function createWithBalanceTerminalsStandardPricing()
    {
        $merchant = $this->create('merchant', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);
        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

        $this->fixtures->create('terminal', ['merchant_id' => $merchantId]);
        $this->fixtures->create('terminal:atom_terminal', ['merchant_id' => $merchantId]);

        return $merchant;
    }

    public function createWithKeys()
    {
        $merchant = $this->create('merchant', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

        $this->fixtures->on('test')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltLiveAuthKey'], 'live');
        $this->fixtures->setDefaultConn();

        return $merchant;
    }

    public function createBankAccount($attributes)
    {
        $name = random_alpha_string(10);

        $code = substr(strtoupper($name), 0, 4);

        $defaultValues = array(
            'beneficiary_name' => $name,
            'beneficiary_code' => $code,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('bank_account', $attributes);
    }

    public function createAddPaymentBanks(array $attributes = array())
    {
        $banks = \Models\Payment\Processor\NetBanking::getAllBanks();

        $defaultValues = array(
            'merchant_id' => '10000000000000',
            'banks' => $banks,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $this->fixtures->create('merchant_banks', $attributes);
    }

    public function activate($id)
    {
        $repo = new \Models\Merchant\Repository;
        $merchant = $repo->findOrFail($id);
        $merchant->activated = 1;
        $repo->saveOrFail($merchant);
        return $merchant;
    }
}