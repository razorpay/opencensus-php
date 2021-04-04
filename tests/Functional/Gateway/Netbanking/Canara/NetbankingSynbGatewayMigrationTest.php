<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Canara;

use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingSynbGatewayMigrationTest extends NetbankingCanaraGatewayTest
{
    use PaymentTrait;
    use PartnerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/NetbankingSynbGatewayMigrationTestData.php';

        $this->gateway = 'netbanking_canara';

        $this->bank = 'SYNB';
    }
}
