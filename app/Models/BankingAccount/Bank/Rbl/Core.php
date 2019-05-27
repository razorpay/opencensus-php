<?php

namespace RZP\Models\BankingAccount\Bank\Rbl;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function checkPincodeServiceable(string $pincode) : bool
    {
        $isAvailable = $this->app['redis']->sismember('rbl_pincode_set', $pincode);

        return $isAvailable;
    }

    public function validateAvailability(array $input)
    {
        (new Validator)->validateInput('availability', $input);
    }
}
