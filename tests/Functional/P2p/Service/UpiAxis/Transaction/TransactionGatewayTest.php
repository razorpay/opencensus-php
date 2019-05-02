<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class TransactionGatewayTest extends TestCase
{
    public function testCallbackIncomingCollect()
    {
        $helper = $this->getTransactionHelper();

        $response = $helper->callbackIncomingCollect($this->gateway);

        // Assertion might be changed based on gateway requirement
        $this->assertTrue($response['success']);
    }
}
