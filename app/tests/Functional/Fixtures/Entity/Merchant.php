<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Merchant extends Base
{
    public function createDefaultTestMerchant()
    {
        $this->create('merchant', ['id' => '10000000000000']);
        $this->fixtures->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->create('balance', ['id' => '10000000000000', 'balance' => '1000000']);
        $this->fixtures->on('test')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');
        $this->fixtures->on('live')->create('bank_account', ['merchant_id' => '10000000000000']);
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

    public function activate($id)
    {
        $this->db->connection('test')->table('merchants')->where('id', $id)->update(['activated' => true]);
        $this->db->connection('live')->table('merchants')->where('id', $id)->update(['activated' => true]);
    }
}