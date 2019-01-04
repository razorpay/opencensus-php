<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface DeviceGateway extends GatewayInterface
{
    public function startVerification(Response $response);

    public function getVerificationStatus(Response $response);

    public function refreshClToken(Response $response);

    public function deregister(Response $response);
}
