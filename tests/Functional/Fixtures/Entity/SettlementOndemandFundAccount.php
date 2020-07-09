<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class SettlementOndemandFundAccount extends Base
{
    protected $defaultAttributes = [
        'merchant_id'       => '10000000000000',
        'contact_id'        => 'cont_EwjVv4aprYdlR5',
        'fund_account_id'   => 'fa_EwjVzEQdVIqqxW',
    ];

    public function create(array $attributes = array())
    {
        return parent::create($this->defaultAttributes);
    }
}
