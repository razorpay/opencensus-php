<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantActivationCategoriesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantActivationCategoriesTestData.php';

        parent::setUp();
    }

    public function testMerchantActivationCategoriesResponse()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
