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

    public function testFindByMerchantAndIdSplitzDisable() {
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->mockCredcaseApiAndService([]);
        $this->mockAllSplitzResponseDisable();
        $actualKey = $this->repo->findByMerchantIdAndKeyId($merchant['id'], $key['id']);
        self::assertEquals($key->toArrayPublic(), $actualKey->toArrayPublic());
    }

    public function testFindByMerchantAndIdSplitzEnable() {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId($key->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($key->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($key->getUpdatedAt());
        $this->mockCredcaseApiAndService($apiKeyResponse);
        $this->mockAllSplitzTreatment();
        $actual = $this->repo->findByMerchantIdAndKeyId($merchant['id'], $key->getId());
        self::assertEquals($key->toArrayPublic(), $actual->toArrayPublic());
    }

    public function testGetLatestActiveKeyForMerchantSplitzDisable() {
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->mockCredcaseApiAndService([]);
        $this->mockAllSplitzResponseDisable();
        $actualKey = $this->repo->getLatestActiveKeyForMerchant($merchant['id']);
        self::assertEquals($key->toArrayPublic(), $actualKey->toArrayPublic());
    }

    public function testGetLatestActiveKeyForMerchantSplitzEnable() {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $key = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant->getId()
        ]);
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId($key->getId());
        $apiKeyResponse->setOwnerId($merchant->getId());
        $apiKeyResponse->setCreatedAt($key->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($key->getUpdatedAt());
        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(1);
        $apiKeyListResponse->setItems(array($apiKeyResponse));
        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();
        $actual = $this->repo->getLatestActiveKeyForMerchant($merchant['id']);
        self::assertEquals($key->toArrayPublic(), $actual->toArrayPublic());
    }

    public function testGetActiveKeysForMerchantsSplitzEnable() {
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant1 = $this->fixtures->create('merchant', [
            'id' => Entity::generateUniqueId(),
        ]);
        $key1 = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant1->getId()
        ]);
        $merchant2 = $this->fixtures->create('merchant', [
            'id' => Entity::generateUniqueId(),
        ]);
        $key2 = $this->fixtures->create('key', [
            'id' => Entity::generateUniqueId(),
            'merchant_id' => $merchant2->getId()
        ]);
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse->setId($key1->getId());
        $apiKeyResponse->setOwnerId($merchant1->getId());
        $apiKeyResponse->setCreatedAt($key1->getCreatedAt());
        $apiKeyResponse->setUpdatedAt($key1->getUpdatedAt());
        $apiKeyResponse2 = new ApiKeyResponse();
        $apiKeyResponse2->setMode($this->convertModeToEnum(Mode::TEST));
        $apiKeyResponse2->setId($key2->getId());
        $apiKeyResponse2->setOwnerId($merchant2->getId());
        $apiKeyResponse2->setCreatedAt($key2->getCreatedAt());
        $apiKeyResponse2->setUpdatedAt($key2->getUpdatedAt());
        $apiKeyListResponse = new ApiKeyListResponse();
        $apiKeyListResponse->setCount(2);
        $apiKeyListResponse->setItems(array($apiKeyResponse, $apiKeyResponse2));
        $this->mockCredcaseApiAndService($apiKeyListResponse);
        $this->mockAllSplitzTreatment();
        $actual = $this->repo->getActiveKeysForMerchants(array($merchant1['id'], $merchant2['id']));
        self::assertEquals($key1->toArrayPublic(),$actual->offsetGet(0)->toArrayPublic());
        self::assertEquals($key2->toArrayPublic(),$actual->offsetGet(1)->toArrayPublic());

    }

    protected function mockCredcaseApiAndService($output = null, $exception = null): void
    {
        $shouldReceive = $this->credcaseMock->shouldReceive('list','getKeyByMerchantAndId','getKeysForMerchants')->byDefault();

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
