<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Emi\MerchantSubvention;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $emiPlan->generateId();

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }
}
