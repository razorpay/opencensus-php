<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Payment\Processor;

class Methods extends Base
{
    public function createDefaultMethods(array $attributes = [])
    {
        $defaultAttributes = [
            'merchant_id'    => '10000000000000',
            'credit_card'    => '1',
            'debit_card'     => '1',
            'prepaid_card'   => '1',
            'mobikwik'       => '1',
            'payzapp'        => '1',
            'payumoney'      => '1',
            'olamoney'       => '1',
            'airtelmoney'    => '1',
            'bank_transfer'  => '1',
            'banks'          => '[]',
            'disabled_banks' => Processor\Netbanking::DEFAULT_DISABLED_BANKS,
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        $this->fixtures->create('methods', $attributes);
    }
}
