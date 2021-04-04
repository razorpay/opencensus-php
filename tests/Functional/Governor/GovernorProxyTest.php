<?php

namespace RZP\Tests\Functional\Governor;

use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class GovernorProxyTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/GovernorProxyTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateNamespace()
    {
        $this->startTest();
    }
}
