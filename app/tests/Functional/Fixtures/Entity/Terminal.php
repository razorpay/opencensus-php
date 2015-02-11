<?php

namespace Tests\Functional\Fixtures\Entity;

class Terminal extends Base
{
    public function createAtomTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'atom',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        return $this->create('terminal', $attributes);
    }

    public function createDisableDefaultHdfcTerminal()
    {
        $term = \Models\Terminal\Entity::findOrFail('1n25f6uN5S1Z5a');
        $term->card = false;
        $term->saveOrFail();

        return $term;
    }

    public function createSharedAtomTerminal()
    {
        $termId = \Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant');

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => $merchant['id'],
            'card'                  => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $this->createEntityInTestAndLive('terminal', $attributes);
    }
}