<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface TurboGateway extends GatewayInterface
{
    public function initiateTurboCallback(Response $response);
}
