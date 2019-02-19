<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface BankAccountGateway extends GatewayInterface
{
    public function retrieve(Response $response);

    public function initiateSetUpiPin(Response $response);

    public function setUpiPin(Response $response);

    public function initiateFetchBalance(Response $response);

    public function fetchBalance(Response $response);
}
