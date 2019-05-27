<?php

namespace RZP\Models\MerchantAccount\Bank\Rbl;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function checkPincodeServiceable(string $pincode)
    {
        $pincodeList = $this->app['redis']->smembers('rbl_pincode_set');

        return (in_array($pincode, $pincodeList, true) === true);
    }

    public function validateBankAccountForMerchant(array $input)
    {
        (new Validator)->validateInput('availability', $input);
    }
}
