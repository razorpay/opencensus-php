<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface TransactionGateway extends GatewayInterface
{
    public function initiatePay(Response $response);

    public function initiateCollect(Response $response);

    public function fetchAll(Response $response);

    public function fetch(Response $response);

    public function initiateAuthorize(Response $response);

    public function authorizeTransaction(Response $response);

    public function reject(Response $response);
}
