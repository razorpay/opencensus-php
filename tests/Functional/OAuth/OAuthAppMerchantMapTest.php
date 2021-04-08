<?php

namespace RZP\Tests\Functional\OAuth;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthAppMerchantMapTest extends OAuthTestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthAppMerchantMapTestData.php';

        parent::setUp();

        $this->ba->authServiceAuth();
    }

    public function testOAuthAppMerchantMap()
    {
        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

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
        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvX']);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

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

    public function testOAuthSyncMerchantMap()
    {
        $this->ba->adminAuth();

        $application = $this->createOAuthApplication();

        $clients = $application->clients->all();

        $this->generateOAuthAccessTokenForClient([], $clients[0]);

        $this->generateOAuthAccessTokenForClient([], $clients[1]);

        $this->generateOAuthAccessToken();

        $this->startTest();
    }

    public function testGetConnectedApplications()
    {
        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvX']);

        $this->ba->storkAppAuth();
        $this->startTest();

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwner'];

        $this->runRequestResponseFlow($testData);

        $application = $this->createOAuthApplication();

        $clients = $application->clients->all();

        $this->generateOAuthAccessTokenForClient([], $clients[0]);

        $this->generateOAuthAccessTokenForClient([], $clients[1]);

        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvY', 'entity_id' => $application->getId()]);

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwnerAsApi'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwnerAsRx'];

        $this->runRequestResponseFlow($testData);
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
