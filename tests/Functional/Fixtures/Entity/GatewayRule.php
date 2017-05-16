<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant;

class GatewayRule extends Base
{
    public function createCard(array $attributes = [])
    {
        $ruleAttributes = [
            'method'        => 'card',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'gateway'       => 'hdfc',
            'network'       => 'VISA',
            'load'          => 5000
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function createNetbanking(array $attributes = [])
    {
        $ruleAttributes = [
            'method'        => 'netbanking',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'load'          => 5000,
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function createWallet(array $attributes = [])
    {
        $ruleAttributes = [
            'method'        => 'wallet',
            'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
            'load'          => 5000,
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }
}
