<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface ClientGateway extends GatewayInterface
{
    public function getGatewayConfig(Response $response);
}
