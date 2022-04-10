<?php

namespace Unit\Models\PaymentLink\NocodeCustomUrl;

use Event;
use RZP\Models\PaymentLink;
use RZP\Exception\AssertionException;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;

class CoreTest extends BaseTest
{
    use PaymentLinkTestTrait;

    const TEST_SLUG         = 'TESTSLUG';
    const TEST_DOMAIN       = 'razorpay.com';
    const TEST_PRODUCT      = PaymentLink\ViewType::PAGE;
    const TEST_PL_ID        = '100000000000pl';
    const TEST_PL_ID1       = '100000000001pl';
    const TEST_META_DATA    = [];

    const TEST_SLUG1        = 'TESTSLUG1';
    const TEST_DOMAIN1      = 'google.com';

    /**
     * @var \RZP\Models\PaymentLink\NocodeCustomUrl\Core
     */
    protected $core;

    /**
     * @var \RZP\Models\PaymentLink\NocodeCustomUrl\Repository
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->core = new PaymentLink\NocodeCustomUrl\Core();

        $this->repo = new PaymentLink\NocodeCustomUrl\Repository();
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_core
     */
    public function testUpsertWithoutActiveTransactionShouldFail()
    {
        $merchant = $this->fixtures->merchant->create();
        $paymentlink = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $this->expectException(AssertionException::class);

        $this->core->upsert($this->getDefaultUpsertData(), $merchant, $paymentlink);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_core
     */
    public function testUpsertInitialSuccess()
    {
        Event::fake();

        $merchant = $this->fixtures->merchant->create();
        $paymentlink = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $entity = $this->repo->transaction(function () use ($merchant, $paymentlink) {
            return $this->core->upsert($this->getDefaultUpsertData(), $merchant, $paymentlink);
        });

        $this->assertEquals($entity->getSlug(), self::TEST_SLUG);
        $this->assertEquals($entity->getDomain(), self::TEST_DOMAIN);
        $this->assertEquals($entity->getMerchantId(), $merchant->getId());
        $this->assertEquals($entity->getProductId(), $paymentlink->getId());

        $this->assertCaching($entity);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_core
     */
    public function testUpsertSecondEntrySuccess()
    {
        Event::fake();

        $merchant = $this->fixtures->merchant->create();
        $paymentlink = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $data = $this->getDefaultUpsertData();

        $old = $this->repo->transaction(function () use ($data, $merchant, $paymentlink) {
            return $this->core->upsert($data, $merchant, $paymentlink);
        });

        $this->assertCaching($old);

        $data[PaymentLink\NocodeCustomUrl\Entity::SLUG]     = self::TEST_SLUG1;
        $data[PaymentLink\NocodeCustomUrl\Entity::DOMAIN]   = self::TEST_DOMAIN1;

        $latest = $this->repo->transaction(function () use ($data, $merchant, $paymentlink) {
            return $this->core->upsert($data, $merchant, $paymentlink);
        });

        $this->assertCaching($latest);

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => $paymentlink->getId(),
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $this->assertEquals($latest->getId(), $entity->getId());

        $data[PaymentLink\NocodeCustomUrl\Entity::SLUG]     = self::TEST_SLUG;
        $data[PaymentLink\NocodeCustomUrl\Entity::DOMAIN]   = self::TEST_DOMAIN;

        $this->repo->transaction(function () use ($data, $merchant, $paymentlink) {
            return $this->core->upsert($data, $merchant, $paymentlink);
        });

        $this->assertCaching($old);

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => $paymentlink->getId(),
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $this->assertEquals($old->getId(), $entity->getId());

        $entries = $this->repo->fetchByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => $paymentlink->getId()
        ], true);

        $this->assertCount(2, $entries);
    }


    /**
     * @group nocode_ncu
     * @group nocode_ncu_core
     */
    public function testUpsertSecondEntryWithSameProductDifferentProductId()
    {
        $merchant = $this->fixtures->merchant->create();
        $paymentlink = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $data = $this->getDefaultUpsertData();

        $this->repo->transaction(function () use ($data, $merchant, $paymentlink) {
            return $this->core->upsert($data, $merchant, $paymentlink);
        });

        $paymentlink2 = $this->createPaymentLink(self::TEST_PL_ID1, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectErrorMessage(PaymentLink\NocodeCustomUrl\Core::ENTITY_DUPLICATE_ERROR);

        $this->repo->transaction(function () use ($data, $merchant, $paymentlink2) {
            return $this->core->upsert($data, $merchant, $paymentlink2);
        });
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_core
     */
    public function testUpsertSecondEntryWithSameSlugDifferentMerchant()
    {
        $merchant = $this->fixtures->merchant->create();
        $paymentlink = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $data = $this->getDefaultUpsertData();

        $this->repo->transaction(function () use ($data, $merchant, $paymentlink) {
            return $this->core->upsert($data, $merchant, $paymentlink);
        });

        $merchant = $this->fixtures->merchant->create();

        $paymentlink2 = $this->createPaymentLink(self::TEST_PL_ID1, [
            PaymentLink\Entity::VIEW_TYPE   => self::TEST_PRODUCT,
            PaymentLink\Entity::MERCHANT_ID => $merchant->getId(),
        ]);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectErrorMessage(PaymentLink\NocodeCustomUrl\Core::ENTITY_DUPLICATE_ERROR);

        $this->repo->transaction(function () use ($data, $merchant, $paymentlink2) {
            return $this->core->upsert($data, $merchant, $paymentlink2);
        });
    }

    /**
     * @return array
     */
    private function getDefaultUpsertData(): array
    {
        return [
            PaymentLink\NocodeCustomUrl\Entity::SLUG        => self::TEST_SLUG,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN      => self::TEST_DOMAIN,
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT     => self::TEST_PRODUCT,
            PaymentLink\NocodeCustomUrl\Entity::META_DATA   => self::TEST_META_DATA,
        ];
    }

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    private function assertCaching(PaymentLink\NocodeCustomUrl\Entity $entity): void
    {
        $uniqueEntity = $this->core->getUniqueEntity($entity->getSlug(), $entity->getDomain());

        Event::assertDispatched(CacheMissed::class, function (CacheMissed $missed) use ($entity) {
            return $missed->key === PaymentLink\NocodeCustomUrl\Entity::getCacheKeyBySlugAndDomain($entity->getSlug(), $entity->getDomain());
        });

        $this->assertEquals($entity->getSlug(), $uniqueEntity->getSlug());
        $this->assertEquals($entity->getDomain(), $uniqueEntity->getDomain());
        $this->assertEquals($entity->getMerchantId(), $uniqueEntity->getMerchantId());
        $this->assertEquals($entity->getProductId(), $uniqueEntity->getProductId());
        $this->assertEquals($entity->getId(), $uniqueEntity->getId());

        $uniqueEntity = $this->core->getUniqueEntity($entity->getSlug(), $entity->getDomain());

        Event::assertDispatched(CacheHit::class, function (CacheHit $hit) use ($entity) {
            return $hit->key === PaymentLink\NocodeCustomUrl\Entity::getCacheKeyBySlugAndDomain($entity->getSlug(), $entity->getDomain());
        });

        $this->assertEquals($entity->getSlug(), $uniqueEntity->getSlug());
        $this->assertEquals($entity->getDomain(), $uniqueEntity->getDomain());
        $this->assertEquals($entity->getMerchantId(), $uniqueEntity->getMerchantId());
        $this->assertEquals($entity->getProductId(), $uniqueEntity->getProductId());
        $this->assertEquals($entity->getId(), $uniqueEntity->getId());
    }
}
