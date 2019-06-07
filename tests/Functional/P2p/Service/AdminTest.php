<?php

namespace RZP\Tests\P2p\Service\UpiSharp;

use RZP\Tests\Functional\RequestResponseFlowTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function testAdminAuthHandle()
    {
        $vpa = $this->getEntities('p2p_handle', [], true);

        $this->assertCount(6, $vpa['items']);
    }

    public function testAdminAuthUpiTransactions()
    {
        $vpa = $this->getEntities('p2p_upi_transaction', [], true);

        $this->assertCount(0, $vpa['items']);
    }
}
