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
            'min_amount'    => 0,
            'max_amount'    => 4294967295,
            'load'          => 50
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function createNetbanking(array $attributes = [])
    {
        $ruleAttributes = [
            'type'        => 'sorter',
            'method'      => 'netbanking',
            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
            'min_amount'  => 0,
            'max_amount'  => 4294967295,
            'load'        => 50,
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function createWallet(array $attributes = [])
    {
        $ruleAttributes = [
            'type'        => 'sorter',
            'method'      => 'wallet',
            'min_amount'  => 0,
            'max_amount'  => 4294967295,
            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
            'load'        => 50,
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function delete(array $ruleIds)
    {
        foreach ($ruleIds as $id)
        {
            $rule = \RZP\Models\Gateway\Rule\Entity::findOrFail($id);

            $rule->forceDelete();
        }
    }
}
