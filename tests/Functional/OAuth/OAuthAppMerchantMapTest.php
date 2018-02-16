<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthAppMerchantMapTest extends OAuthTestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthAppMerchantMapTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testOAuthAppMerchantMap()
    {
        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals('10000000000App', $liveMapping['entity_id']);

        $this->assertEquals('10000000000App', $testMapping['entity_id']);
    }

    public function testOAuthAppMerchantMapIncorrectEntityId()
    {
        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    public function testOAuthAppDeleteMerchantMap()
    {
        $this->fixtures->create('merchant_access_map');

        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    public function testOAuthAppDeleteMerchantMapNoEntries()
    {
        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    protected function getMapping(string $mode)
    {
        \Database\DefaultConnection::set($mode);

        return $this->getLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                true);

    }
}
