<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface UpiGateway extends GatewayInterface
{
    public function initiateGatewayCallback(Response $response);
}
