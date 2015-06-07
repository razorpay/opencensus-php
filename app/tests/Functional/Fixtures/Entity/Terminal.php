<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Terminal\Shared;

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
        $term->forceDelete();

        return $term;
    }

    public function createSharedAtomTerminal()
    {
        $termId = \Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant', ['id' => '10AtomRazorpay']);

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => $merchant['id'],
            'gateway'               => 'atom',
            'card'                  => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisTerminal()
    {
        $termId = \Models\Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant', ['id' => '10AxisRazorpay']);

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => $merchant['id'],
            'gateway'                   => 'axis_migs',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_migs',
            'gateway_terminal_id'       => 'nodal account axis_migs',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisGeniusTerminal()
    {
        $termId = \Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant', ['id' => '10AxisRazorpay']);

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => $merchant['id'],
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedKotakTerminal()
    {
        $termId = \Models\Terminal\Shared::KOTAK_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant', ['id' => '10AxisRazorpay']);

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => $merchant['id'],
            'gateway'                   => 'kotak',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay kotak',
            'gateway_terminal_id'       => 'nodal account kotak',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPaytmTerminal()
    {
        $termId = \Models\Terminal\Shared::PAYTM_RAZORPAY_TERMINAL;

        $merchant = $this->fixtures->create('merchant', ['id' => '10AxisRazorpay']);

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => $merchant['id'],
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNetbankingHdfcTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'netbanking_hdfc',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        return $this->create('terminal', $attributes);
    }

    public function createSharedNetbankingHdfcTerminal(array $attributes = array())
    {
        $attributes = array(
            'id'                    => Shared::NETBANKING_HDFC_TERMINAL,
            'merchant_id'           => '10000000000000',
            'gateway'               => 'netbanking_hdfc',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde');

        return $this->create('terminal', $attributes);
    }

    public function createSharedBilldeskTerminal(array $attributes = array())
    {
        $termId = \Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        return $this->create('terminal', $attributes);
    }
}