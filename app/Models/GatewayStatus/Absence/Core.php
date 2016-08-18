<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;

class Core extends Base\Core
{
    public function create($input, $gateway)
    {
        $input['gateway'] = $gateway;

        $downWindow = (new Absence\Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        return $downWindow;
    }
}
