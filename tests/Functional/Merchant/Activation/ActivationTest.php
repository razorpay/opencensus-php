<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;

class ActivationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

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

    public function testUpdateActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('greylist', $liveMerchant->merchantdetail->getActivationFlow());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('greylist', $testMerchant->merchantdetail->getActivationFlow());
    }

    public function testUpdateCategoryDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame(6211, $liveMerchant->getCategory());
        $this->assertSame('mutual_funds', $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame(6211, $testMerchant->getCategory());
        $this->assertSame('mutual_funds', $testMerchant->getCategory2());
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

    /**
     * for blacklist activation flow
     */
    public function testBlacklistInstantActivation()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGreylistInstantActivation()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Blacklist merchant should be able to resubmit L1 activation form (basic activation form)
     */
    public function testL1ResubmissionForBlacklist()
    {
        $merchantDetail = $this->fixtures->create(
            'merchant_detail',
            [MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST,]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('whitelist', $liveMerchant->merchantdetail->getActivationFlow());
    }
}
