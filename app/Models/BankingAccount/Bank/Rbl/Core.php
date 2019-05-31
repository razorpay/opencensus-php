<?php

namespace RZP\Models\BankingAccount\Bank\Rbl;

use RZP\Models\Base;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;

class Core extends Base\Core
{
    public function getBankAvailabilityStatus(array $input)
    {
        (new Validator)->validateInput('availability', $input);

        $isAvailable = $this->checkPincodeServiceable($input[Entity::PINCODE]);

        $status =  $isAvailable ? Status::CREATED : Status::UNSERVICEABLE;

        return $status;
    }

    protected function checkPincodeServiceable(string $pincode) : bool
    {
        $isAvailable = $this->app['redis']->sismember('rbl_pincode_set', $pincode);

        return $isAvailable;
    }
}
