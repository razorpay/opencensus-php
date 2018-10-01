<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;

class ActivationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

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
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPopulateActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();
        $subCategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData(BusinessSubcategory::MUTUAL_FUND);

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame($subCategoryMetaData[MerchantDetails::ACTIVATION_FLOW], $liveMerchant->merchantdetail->getActivationFlow());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame($subCategoryMetaData[MerchantDetails::ACTIVATION_FLOW], $testMerchant->merchantdetail->getActivationFlow());
    }

    public function testPopulateCategoryDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $this->startTest();

        $subCategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData(BusinessSubcategory::MUTUAL_FUND);

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame($subCategoryMetaData[Merchant::CATEGORY], $liveMerchant->getCategory());
        $this->assertSame($subCategoryMetaData[Merchant::CATEGORY2], $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame($subCategoryMetaData[Merchant::CATEGORY], $testMerchant->getCategory());
        $this->assertSame($subCategoryMetaData[Merchant::CATEGORY2], $testMerchant->getCategory2());
    }
}
