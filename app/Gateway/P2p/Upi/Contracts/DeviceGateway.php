<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface DeviceGateway extends GatewayInterface
{
    public function startVerification(): Response;

    public function getVerificationStatus(): Response;

    public function refreshClToken(): Response;

    public function deregister(): Response;
}
