<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Gateway\P2p\Upi;
use RZP\Gateway\P2p\Upi\Axis\Library\Request;

class Gateway extends Upi\Gateway
{
    protected function initiateSdkRequest(string $action, $map)
    {
        $request = new Request($action, $map);

        return $request;
    }
}
