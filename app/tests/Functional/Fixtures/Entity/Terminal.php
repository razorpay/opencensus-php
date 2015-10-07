<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Terminal\Shared;

class Terminal extends Base
{
    public function createAllSharedTerminals()
    {
        $this->createSharedHdfcTerminal();
        $this->createSharedAtomTerminal();
        $this->createSharedAxisTerminal();
        $this->createSharedBilldeskTerminal();
        $this->createSharedAxisGeniusTerminal();
        $this->createSharedKotakTerminal();
        $this->createSharedPaytmTerminal();
        $this->createSharedMobikwikTerminal();
        $this->createSharedNetbankingHdfcTerminal();
    }

    public function createAtomTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'atom',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        return parent::create($attributes);
    }

    public function createDisableDefaultHdfcTerminal()
    {
        $term = \Models\Terminal\Entity::findOrFail('1n25f6uN5S1Z5a');
        $term->forceDelete();

        return $term;
    }

    public function createBilldeskTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 0);

        return parent::create($attributes);
    }

    public function createAxisGeniusTerminal(array $attributes = array())
    {
        $termId = \Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'razorpay_axis_genius_terminal',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomTerminal()
    {
        $termId = \Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'atom',
            'card'                  => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisTerminal(array $attributes = array())
    {
        $termId = \Models\Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'axis_migs',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_migs',
            'gateway_terminal_id'       => 'nodal account axis_migs',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedBilldeskTerminal(array $attributes = array())
    {
        $termId = \Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 1);

        return parent::create($attributes);
    }
    public function createSharedAxisGeniusTerminal()
    {
        $termId = \Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedKotakTerminal()
    {
        $termId = \Models\Terminal\Shared::KOTAK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
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

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
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

        return parent::create($attributes);
    }

    public function createSharedNetbankingHdfcTerminal(array $attributes = array())
    {
        $attributes = array(
            'id'                    => Shared::NETBANKING_HDFC_TERMINAL,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'netbanking_hdfc',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde');

        return parent::create($attributes);
    }

    public function createSharedSharpTerminal(array $attributes = array())
    {
        $termId = \Models\Terminal\Shared::SHARP_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'sharp',
            'gateway_merchant_id'   => 'abcd',
            'gateway_terminal_id'   => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        return parent::create($attributes);
    }

    public function createSharedMobikwikTerminal()
    {
        $termId = \Models\Terminal\Shared::MOBIKWIK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'mobikwik',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedHdfcTerminal()
    {
        $termId = \Models\Terminal\Shared::HDFC_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'hdfc',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay hdfc',
            'gateway_terminal_id'       => 'account hdfc',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPayzappTerminal()
    {
        $termId = '4r8Djlksdf0dGd';

        $attributes = array(
            'id'                        => '4r8Djlksdf0dGd',
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'payzapp',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay payzapp',
            'gateway_terminal_id'       => 'terminal payzapp',
            'gateway_terminal_password' => 'razorpay_password',
            'category'                  => '1000',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }
}