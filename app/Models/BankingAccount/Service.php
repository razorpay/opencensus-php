<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        (new Validator)->validateInput('availability', $input);

        $bankStatus = $this->core->getBankAvailabilityStatusForMerchant($input);

        $data = $this->core->createBankingAccount($bankStatus, $input, $this->merchant);

        return $data;
    }
}
