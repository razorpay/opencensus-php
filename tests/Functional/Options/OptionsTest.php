<?php

namespace RZP\Tests\Functional\Options;

use RZP\Models\Options\Entity;
use RZP\Models\Options\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OptionsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    const TEST_OPTION_ID       = 'Ddl2qTGP3uHL2O';
    const TEST_REFERENCE_ID    = 'Ddl2pnoDXM59rH';
    const TEST_NAMESPACE       = 'payment_links';
    const TEST_SERVICE         = 'invoices';
    const TEST_OPTIONS_JSON    = '{"checkout":{"label":{"min_amount":"Some first amount"}}}';
    const TEST_MERCHANT_ID     = '100DemoAccount';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/OptionsTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateOptionsForMerchant()
    {
        $this->startTest();
    }

    public function testCreateOptionsForMerchantWithReferenceId()
    {
        $this->startTest();
    }

    public function testCreateOptionsForMerchantWithDefaultNamespaceAndServiceType()
    {
        $this->startTest();
    }

    public function testDuplicateCreateOptionsForMerchant()
    {
        $this->createOption();
        $this->startTest();
    }

    public function testNoOptionsSentOnCreate()
    {
        $this->startTest();
    }

    public function testOptionsFetchById1()
    {
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsFetchById2()
    {
        $this->createOptionWithReferenceId();
        $this->startTest();
    }

    public function testOptionsDeleteByIdSuccess()
    {
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsDeleteByIdFailure()
    {
        $this->startTest();
    }

    public function testOptionsPatchById()
    {
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsFetchByNamespaceAndService()
    {
        $this->createOptionWithNamespaceAndService();
        $this->startTest();
    }

    public function testOptionsFetchByInvalidNamespace()
    {
        $this->startTest();
    }

    public function testOptionsFetchByInvalidService()
    {
        $this->startTest();
    }

    public function testCreateOptionsForMerchantAdmin()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->startTest();
    }

    public function testOptionsFetchByAdmin()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsFetchByAdminFailure1()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsFetchByAdminFailure2()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsFetchByAdminFailure3()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsDeleteSuccessAdmin()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOptionWithNamespaceAndServiceAndMerchantId();
        $this->startTest();
    }

    public function testOptionsDeleteByAdminFailure1()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsDeleteByAdminFailure2()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsDeleteByAdminFailure3()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsPatchAdmin()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOptionWithNamespaceAndServiceAndMerchantId();
        $this->startTest();
    }

    public function testOptionsUpdateByAdminFailure1()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsUpdateByAdminFailure2()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsUpdateByAdminFailure3()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOption();
        $this->startTest();
    }

    public function testOptionsUpdateByAdminForMissingEntityFailure()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->startTest();
    }

    public function testOptionsDeleteByAdminForMissingEntityFailure()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->startTest();
    }

    public function testDuplicateCreateOptionsForMerchantAdmin()
    {
        $this->ba->adminAuth();
        $this->createMerchant();
        $this->createOptionWithNamespaceAndServiceAndMerchantId();
        $this->startTest();
    }

    protected function createOption()
    {
        $attributes[Entity::ID]      		= self::TEST_OPTION_ID;
        $attributes[Entity::OPTIONS_JSON]   = self::TEST_OPTIONS_JSON;

        return $this->fixtures->create('options', $attributes);
    }

    protected function createOptionWithNamespaceAndService()
    {
        $attributes[Entity::ID]      		= self::TEST_OPTION_ID;
        $attributes[Entity::OPTIONS_JSON]   = self::TEST_OPTIONS_JSON;
        $attributes[Entity::NAMESPACE]      = self::TEST_NAMESPACE;
        $attributes[Entity::SERVICE_TYPE]   = self::TEST_SERVICE;

        return $this->fixtures->create('options', $attributes);
    }

    protected function createOptionWithNamespaceAndServiceAndMerchantId()
    {
        $attributes[Entity::ID]      		= self::TEST_OPTION_ID;
        $attributes[Entity::OPTIONS_JSON]   = self::TEST_OPTIONS_JSON;
        $attributes[Entity::NAMESPACE]      = self::TEST_NAMESPACE;
        $attributes[Entity::SERVICE_TYPE]   = self::TEST_SERVICE;
        $attributes[Entity::MERCHANT_ID]    = self::TEST_MERCHANT_ID;

        return $this->fixtures->create('options', $attributes);
    }

    protected function createOptionWithReferenceId()
    {
        $attributes[Entity::ID]      		= self::TEST_OPTION_ID;
        $attributes[Entity::REFERENCE_ID]   = self::TEST_REFERENCE_ID;
        $attributes[Entity::SCOPE]          = Constants::SCOPE_ENTITY;
        $attributes[Entity::OPTIONS_JSON]   = self::TEST_OPTIONS_JSON;

        return $this->fixtures->create('options', $attributes);
    }

    protected function createMerchant()
    {
        $attributes[Entity::ID]      		= self::TEST_MERCHANT_ID;

        return $this->fixtures->create('merchant', $attributes);
    }
}
