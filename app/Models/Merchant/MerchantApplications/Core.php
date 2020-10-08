<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $merchant,
        array $input = null)
    {
        $merchantApplication = (new Entity)->build($input);

        $merchantApplication->generateId();

        $merchantApplication->merchant()->associate($merchant);

        $this->repo->saveOrFail($merchantApplication);

        return $merchantApplication;
    }
}
