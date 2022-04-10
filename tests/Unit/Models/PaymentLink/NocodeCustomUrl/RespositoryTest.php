<?php

namespace Unit\Models\PaymentLink\NocodeCustomUrl;

use Carbon\Carbon;
use RZP\Models\PaymentLink;
use Faker\Generator as Faker;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;

class RespositoryTest extends BaseTest
{
    const TEST_NCU_ID       = '10000000000NCU';
    const TEST_SLUG         = 'TESTSLUG';
    const TEST_DOMAIN       = 'razorpay.com';
    const TEST_PRODUCT      = PaymentLink\ViewType::PAGE;
    const TEST_PRODUCT_ID   = '100000000000pl';
    const TEST_MERCHANT_ID  = '10000000000000';
    const TEST_META_DATA    = [];

    const TEST_NCU_ID1      = '10000000001NCU';
    const TEST_SLUG1        = 'TESTSLUG1';
    const TEST_DOMAIN1      = 'google.com';
    const TEST_PRODUCT_ID1  = '100000000001pl';

    /**
     * @var \RZP\Models\PaymentLink\NocodeCustomUrl\Repository
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new PaymentLink\NocodeCustomUrl\Repository();
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFetchByAttributesWithNoInputThrowsException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->repo->fetchByAttributes([]);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFetchByAttributesWithNotAllowedKeyInputThrowsException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $now = Carbon::now()->getTimestamp();

        $this->repo->fetchByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::META_DATA => [],
            PaymentLink\NocodeCustomUrl\Entity::CREATED_AT => $now,
            PaymentLink\NocodeCustomUrl\Entity::UPDATED_AT => $now,
            PaymentLink\NocodeCustomUrl\Entity::DELETED_AT => $now,
        ]);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithId()
    {
        $this->createNocodeUrls();

        $entity = $this->repo->findByAttributes([PaymentLink\NocodeCustomUrl\Entity::ID => self::TEST_NCU_ID]);

        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(), self::TEST_NCU_ID);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithSlugAndDomain()
    {
        $this->createNocodeUrls();

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => self::TEST_SLUG,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => self::TEST_DOMAIN,
        ]);

        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(), self::TEST_NCU_ID);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithProductIdAndMerchantId()
    {
        $this->createNocodeUrls();

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => self::TEST_PRODUCT_ID,
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID => self::TEST_MERCHANT_ID,
        ]);

        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(), self::TEST_NCU_ID);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithDeleted()
    {
        $this->createNocodeUrls([
            PaymentLink\NocodeCustomUrl\Entity::DELETED_AT => Carbon::now()->getTimestamp()
        ]);

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => self::TEST_SLUG,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => self::TEST_DOMAIN,
        ]);

        $this->assertNull($entity);

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => self::TEST_SLUG,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => self::TEST_DOMAIN,
        ], true);

        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(), self::TEST_NCU_ID);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithMultipleENtriesAndDeleted()
    {
        $this->createNocodeUrls([
            PaymentLink\NocodeCustomUrl\Entity::DELETED_AT => Carbon::now()->getTimestamp()
        ]);

        $this->createNocodeUrls([
            PaymentLink\NocodeCustomUrl\Entity::ID          => self::TEST_NCU_ID1,
            PaymentLink\NocodeCustomUrl\Entity::SLUG        => self::TEST_SLUG1,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN      => self::TEST_DOMAIN1,
        ]);

        $entity = $this->repo->findByAttributes([
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => self::TEST_PRODUCT_ID,
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID => self::TEST_MERCHANT_ID,
        ]);

        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(), self::TEST_NCU_ID1);
    }

    /**
     * @group nocode_ncu
     * @group nocode_ncu_repository
     */
    public function testFindByAttributesWithNoInputThrowsException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->repo->findByAttributes([]);
    }

    /**
     * @param array $attributes
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity
     */
    private function createNocodeUrls(array $attributes = []): PaymentLink\NocodeCustomUrl\Entity
    {
        $data = [
            PaymentLink\NocodeCustomUrl\Entity::ID          => self::TEST_NCU_ID,
            PaymentLink\NocodeCustomUrl\Entity::SLUG        => self::TEST_SLUG,
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN      => self::TEST_DOMAIN,
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT     => self::TEST_PRODUCT,
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID  => self::TEST_PRODUCT_ID,
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID => self::TEST_MERCHANT_ID,
            PaymentLink\NocodeCustomUrl\Entity::META_DATA   => self::TEST_META_DATA,
        ];

        $data = array_merge($data, $attributes);

        return $this->fixtures->create('nocode_custom_url', $data);
    }
}
