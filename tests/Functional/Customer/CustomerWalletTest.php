<?php

namespace RZP\Tests\Functional\Customer;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CustomerWalletTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CustomerWalletTestData.php';

        parent::setUp();
    }

    public function testCreateNewWallet()
    {

    }
}
