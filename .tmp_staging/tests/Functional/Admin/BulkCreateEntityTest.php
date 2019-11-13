<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BulkCreateEntityTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BulkCreateEntityTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateMerchantEmailsBulk()
    {
        $this->startTest();
    }

    public function testUpdateMerchantEmailsBulk()
    {
        $this->startTest();
    }

    public function testUpdateFailureMerchantEmailsBulk()
    {
        $this->startTest();
    }
}
