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
            'gateway_terminal_password' => 'abcdef');

        return $this->create('terminal', $attributes);
    }
}