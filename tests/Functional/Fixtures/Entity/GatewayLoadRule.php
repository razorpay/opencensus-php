<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant;

class GatewayLoadRule extends Base
{
    public function createCard(array $attributes = [])
    {
        $loadAttributes = [
            'method'        => 'card',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'gateway'       => 'hdfc',
            'network'       => 'VISA',
            'international' => false,
            'load'          => 5000
        ];

        $attributes = array_merge($loadAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_load_rule', $attributes);

        return $rule;
    }

    public function createNetbanking(array $attributes = [])
    {
        $loadAttributes = [
            'method'        => 'netbanking',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'international' => false,
            'load'          => 5000,
        ];

        $attributes = array_merge($loadAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_load_rule', $attributes);

        return $rule;
    }

    public function createWallet(array $attributes = [])
    {
        $loadAttributes = [
            'method'        => 'wallet',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'international' => false,
            'load'          => 5000,
        ];

        $attributes = array_merge($loadAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_load_rule', $attributes);

        return $rule;
    }
}
