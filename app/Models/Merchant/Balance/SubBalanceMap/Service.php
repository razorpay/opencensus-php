<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function createSubBalanceAndMap(array $input): array
    {
        $response = $this->core->createSubBalanceAndMap($input);

        return $response;
    }
}
