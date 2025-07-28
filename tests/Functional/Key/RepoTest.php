<?php

namespace Functional\Key;

use Mockery;
use RZP\Constants\Mode;
use Rzp\Credcase\Apikey\V1\ApiKeyListResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyResponse;
use RZP\Models\Key\CredcaseApi;
use RZP\Models\Key\CredcaseService;
use RZP\Models\Key\Entity;
use RZP\Models\Key\Repository;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use Exception;

class RepoTest extends TestCase
{
    use MocksSplitz;

    protected $credcaseMock;
    protected $credcaseService;
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('key.keys_with_country_codes_whitelist', "SG");
        $this->repo = new Repository();
        $this->credcaseMock = Mockery::mock(CredcaseApi::class, [$this->app])->makePartial();
        $this->credcaseService = Mockery::mock(CredcaseService::class, [$this->app])->makePartial();
        $this->app->instance('credcase', $this->credcaseMock);
    }

    public function testFindByMerchantAndIdSplitzDisabled()
    {
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->mockCredcaseApiAndService(null);
        $this->mockAllSplitzResponseDisable();

        $actualKey = $this->repo->findByMerchantIdAndKeyId($merchant['id'], $key['id']);
        self::assertEquals($key->toArrayPublic(), $actualKey->toArrayPublic());
    }

    public function testFindByMerchantAndIdSplitzEnabled()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $dbKey = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        // Create a different Credcase response
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $dbKey->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($dbKey->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($dbKey->getUpdatedAt());

        $this->mockCredcaseApiAndService($apiKeyResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->findByMerchantIdAndKeyId($merchant['id'], $dbKey->getId());
        // Should return Credcase key even though it's different from DB key
        self::assertEquals( $dbKey->getId(), $actual->getId());
    }

    public function testFindByMerchantAndIdSplitzEnabledWithError()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        $this->mockCredcaseApiAndService(null, new Exception('Credcase Error'));
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->findByMerchantIdAndKeyId($merchant['id'], $key->getId());
        // Should fallback to DB key on error
        self::assertEquals($key->toArrayPublic(), $actual->toArrayPublic());
    }

    public function testGetLatestActiveKeyForMerchantSplitzEnabled()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $dbKey = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        // Create different Credcase response
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $dbKey->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($dbKey->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($dbKey->getUpdatedAt());

        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(1);
        $apiKeyListResponse->setItems(array($apiKeyResponse));

        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->getLatestActiveKeyForMerchant($merchant['id']);
        // Should return Credcase key even though different from DB
        self::assertEquals($dbKey->getId(), $actual->getId());
    }

    public function testGetActiveKeysForMerchantsSplitzEnabled()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant1 = $this->fixtures->create('merchant', [
            'id' => Entity::generateUniqueId(),
        ]);
        $dbKey1 = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant1->getId()
        ]);
        $merchant2 = $this->fixtures->create('merchant', [
            'id' => Entity::generateUniqueId(),
        ]);
        $dbKey2 = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant2->getId()
        ]);

        // Create different Credcase responses
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $dbKey1->getId());
        $apiKeyResponse->setOwnerId($merchant1->getId());
        $apiKeyResponse->setCreatedAt($dbKey1->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($dbKey1->getUpdatedAt());

        $apiKeyResponse2 = new ApiKeyResponse();
        $apiKeyResponse2->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse2->setId( $dbKey2->getId());
        $apiKeyResponse2->setOwnerId($merchant2->getId());
        $apiKeyResponse2->setCreatedAt($dbKey2->getCreatedAt());
        $apiKeyResponse2->setUpdatedAt($dbKey2->getUpdatedAt());

        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(2);
        $apiKeyListResponse->setItems(array($apiKeyResponse, $apiKeyResponse2));

        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->getActiveKeysForMerchants(array($merchant1['id'], $merchant2['id']));
        // Should return Credcase keys even though different from DB
        self::assertEquals( $dbKey1->getId(), $actual->offsetGet(0)->getId());
        self::assertEquals( $dbKey2->getId(), $actual->offsetGet(1)->getId());
    }

    public function testFindNotExpiredSplitzDisabled()
    {
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->mockCredcaseApiAndService(null);
        $this->mockAllSplitzResponseDisable();

        $actualKey = $this->repo->findNotExpired($key['id']);
        self::assertEquals($key->toArrayPublic(), $actualKey->toArrayPublic());
    }

    public function testFindNotExpiredSplitzEnabled()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $dbKey = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        // Create a different Credcase response
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $dbKey->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($dbKey->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($dbKey->getUpdatedAt());

        $this->mockCredcaseApiAndService($apiKeyResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->findNotExpired($dbKey->getId());
        // Should return Credcase key even though it's different from DB key
        self::assertEquals( $dbKey->getId(), $actual->getId());
    }

    public function testFindNotExpiredSplitzEnabledWithError()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        $this->mockCredcaseApiAndService(null, new Exception('Credcase Error'));
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->findNotExpired($key->getId());
        // Should fallback to DB key on error
        self::assertEquals($key->toArrayPublic(), $actual->toArrayPublic());
    }

    public function testFindNotExpiredWithIncludeApiResponse()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        // Even with Splitz enabled, should return DB key when includeApiResponse is true
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->findNotExpired($key->getId(), true);
        self::assertEquals($key->toArrayPublic(), $actual->toArrayPublic());
    }

    public function testGetKeysForMerchantForLiveAndTestModeSplitzDisabled()
    {
        $this->app['rzp.mode'] = Mode::LIVE;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->on(Mode::LIVE)->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId(),
            'expired_at' => time() + 3600
        ]);

        $this->mockCredcaseApiAndService(output: null);
        $this->mockAllSplitzResponseDisable();

        $actualKeys = $this->repo->getKeysForMerchantForLiveAndTestMode($merchant['id'], Mode::LIVE);
        self::assertEquals($key->toArrayPublic(), $actualKeys[0]->toArrayPublic());
    }

    public function testGetKeysForMerchantForLiveAndTestModeSplitzEnabled()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $dbKey = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        // Create different Credcase response
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $dbKey->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($dbKey->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($dbKey->getUpdatedAt());

        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(1);
        $apiKeyListResponse->setItems(array($apiKeyResponse));

        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->getKeysForMerchantForLiveAndTestMode($merchant['id'], Mode::TEST);
        // Should return Credcase key even though different from DB
        self::assertEquals( $dbKey->getId(), $actual->first()->getId());
    }

    public function testGetKeysForMerchantForLiveAndTestModeSplitzEnabledWithError()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);

        $this->mockCredcaseApiAndService(null, new Exception('Credcase Error'));
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->getKeysForMerchantForLiveAndTestMode($merchant['id'], Mode::TEST);
        // Should fallback to DB key on error
        self::assertEquals($key->toArrayPublic(), $actual->first()->toArrayPublic());
    }

    public function testGetKeysForMerchantForLiveAndTestModeWithExpired()
    {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $expiredKey = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId(),
            'expired_at' => time() - 3600 // expired 1 hour ago
        ]);

        // Create different Credcase response for expired key
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId( $expiredKey->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($expiredKey->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($expiredKey->getUpdatedAt());
        $apiKeyResponse->setExpiredAt(time() - 3600);

        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(1);
        $apiKeyListResponse->setItems(array($apiKeyResponse));

        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();

        $actual = $this->repo->getKeysForMerchantForLiveAndTestMode($merchant['id'], Mode::TEST, true);
        // Should return Credcase expired key
        self::assertEquals( $expiredKey->getId(), $actual->first()->getId());
        self::assertTrue($actual->first()->isExpired());
    }

    protected function mockCredcaseApiAndService($output = null, $exception = null): void
    {
        $shouldReceive = $this->credcaseMock->shouldReceive('list', 'getKeyByMerchantAndId', 'getKeysForMerchants')->byDefault();

        if ($exception) {
            $shouldReceive->andThrow($exception);
        } else {
            $shouldReceive->andReturn($output);
        }
    }

    protected function convertModeToEnum($mode): int
    {
        return match ($mode) {
            "test" => 1,
            "live" => 2,
            default => 0,
        };
    }
}
