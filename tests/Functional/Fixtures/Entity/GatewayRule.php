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
            'load'          => 50,
            'step'          => 'authorization',
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
            'load'        => 50,
            'step'        => 'authorization',
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
            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
            'load'        => 50,
            'step'        => 'authorization',
        ];

        $attributes = array_merge($ruleAttributes, $attributes);

        $rule = $this->fixtures->create('gateway_rule', $attributes);

        return $rule;
    }

    public function createHitachi(array $attributes = [])
    {
        $rules = [
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'min_amount'    => 0,
                'group'         => 'mpi_reject_rupay',
                'auth_type'     => '3ds',
                'network'       => 'RUPAY',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'min_amount'    => 0,
                'group'         => 'mpi_reject_rupay',
                'auth_type'     => 'headless_otp',
                'network'       => 'RUPAY',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'min_amount'    => 0,
                'group'         => 'mpi_reject_rupay',
                'auth_type'     => 'ivr',
                'network'       => 'RUPAY',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'min_amount'    => 0,
                'group'         => 'mpi_reject_rupay',
                'auth_type'     => 'otp',
                'network'       => 'RUPAY',
                'authentication_gateway' => 'mpi_enstage',
                'step'          => 'authentication',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => '3ds',
                'network'       => 'RUPAY',
                'step'          => 'authentication',
                'authentication_gateway' => 'paysecure',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => 'headless_otp',
                'network'       => 'RUPAY',
                'step'          => 'authentication',
                'authentication_gateway' => 'paysecure',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => '3ds',
                'network'       => null,
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => 'headless_otp',
                'network'       => null,
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => 'ivr',
                'network'       => null,
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'hitachi',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'min_amount'    => 0,
                'group'         => 'authentication',
                'auth_type'     => 'otp',
                'network'       => null,
                'issuer'        => 'UTIB',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_enstage',
            ],
        ];

        foreach ($rules as $gatewayRule)
        {
            $this->fixtures->create('gateway_rule', $gatewayRule);
        }
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
