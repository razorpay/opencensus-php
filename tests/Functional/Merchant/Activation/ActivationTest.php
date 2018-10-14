<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ActivationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ActivationTestData.php';

        parent::setUp();
    }

    public function testMerchantActivationCategoriesResponseForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $this->startTest();
    }

    public function testMerchantActivationCategoriesResponseForNonAdminAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostInstantActivationRequiredField()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->getHoldFunds());

        $this->assertFalse($merchant->merchantDetail->isSubmitted());
    }

    public function testPostInstantActivationLinkedAccount()
    {
        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $merchantId = $linkedAccount->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    /**
     * The instant activation route should not accept the request if the merchant is already activated
     */
    public function testPostInstantActivationByActivatedMerchant()
    {
        $this->fixtures->merchant->activate(self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
