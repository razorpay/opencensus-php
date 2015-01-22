<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class Merchant extends Base
{
    public function createDefaultTestMerchant()
    {
        $this->create('merchant', ['id' => '10000000000000']);
        $this->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->create('balance', ['id' => '10000000000000']);
        $this->on('test')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->on('live')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');
        $this->on('live')->create('bank_account', ['merchant_id' => '10000000000000']);
    }

    public function createNodalAccount()
    {
        $apiMerchant = $this->create('merchant', ['id' => Account::NODAL_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::NODAL_ACCOUNT]);
    }
}