<?php

namespace RZP\Tests\Functional\SuccessRate;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class SuccessRateConfigTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SuccessRateConfigTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testSuccessRateGetConfig()
    {
        $this->startTest();
    }

    public function testSuccessRateUpdateConfig()
    {
        $this->startTest();
    }
}
