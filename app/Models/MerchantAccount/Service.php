<?php

namespace RZP\Models\MerchantAccount;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createMerchantAccount(array $input)
    {
        (new Validator)->validateInput('availability', $input);

        $this->core->validateBankAccountForMerchant($input);

        $mid = $this->merchant->getMerchantId();

        $input[Entity::MERCHANT_ID] = $mid;

        $data = $this->core->createMerchantAccount($input);

        return $data;
    }
}
