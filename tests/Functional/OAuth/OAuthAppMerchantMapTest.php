<?php

namespace RZP\Tests\Functional\OAuth;

use Carbon\Carbon;
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

    public function testOAuthAppMerchantMapDuplicate()
    {
        $this->fixtures->create('merchant_access_map');

        $this->startTest();

        $liveMappings = $this->getMappings('live')['items'];

        $testMappings = $this->getMappings('test')['items'];

        $this->assertEquals(1, count($liveMappings));

        $this->assertEquals(1, count($testMappings));
    }

    public function testOAuthAppMerchantMapDuplicateWithDeleted()
    {
        $this->fixtures->create('merchant_access_map', ['deleted_at' => Carbon::now()->getTimestamp()]);

        $this->startTest();

        $liveMappings = $this->getMappings('live')['items'];

        $testMappings = $this->getMappings('test')['items'];

        $this->assertEquals(1, count($liveMappings));

        $this->assertEquals(1, count($testMappings));
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
        return $this->getLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                true,
                $mode);

    }

    protected function getMappings(string $mode)
    {
        return $this->getEntities(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                [],
                true,
                $mode);

    }
}
