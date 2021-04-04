<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Ibk;

class NetbankingAllaGatewayMigrationTest extends NbplusNetbankingIbkGatewayTest
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/NetbankingAllaGatewayMigrationTestData.php';

        $this->gateway = 'netbanking_ibk';

        $this->bank = 'ALLA';
    }

}
