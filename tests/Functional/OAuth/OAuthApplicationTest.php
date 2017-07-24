<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthApplicationTest extends OAuthTestCase
{
    use RequestResponseFlowTrait;
    use OAuthTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthApplicationTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testFetchApplications()
    {
        $this->createOAuthApplication(['id' => '10000000000App', 'name' => 'Test App']);

        $this->startTest();
    }
}
