<?php

namespace Tests\Functional\Fixtures\Entity;

class Merchant extends Base
{
    public function createDefaultTestMerchant()
    {
        $this->createEntity('merchant', ['id' => '10000000000000']);
        $this->createEntity('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->createEntity('balance', ['id' => '10000000000000']);
        $this->on('test')->createEntity('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->on('live')->createEntity('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');
        $this->on('live')->createEntity('bank_account', ['merchant_id' => '10000000000000']);
    }

    public function createNodalAccount()
    {
        $apiMerchant = $this->create(['id' => Merchant\Account::NODAL_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Merchant\Account::NODAL_ACCOUNT]);
    }
}