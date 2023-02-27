<?php

namespace Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;

class MerchantAuthorizePartnerTest extends TestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use CreateLegalDocumentsTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAuthorizePartnerTestData.php';

        parent::setUp();
    }

    public function testGetMerchantAuthorizationStatusWhenMappedToPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->createSubMerchant($partner, $app, ['id' => '10000000000111']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantAuthorizationStatusWhenNotMappedToPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantAuthorizationStatusWhenInvalidPartnerIdProvided()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantAuthorizationStatusWhenInvalidMerchantIdProvided()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantAuthorizationStatusWhenPartnerIsNotAggregator()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'reseller']);

        $this->createSubMerchant($partner, $app, ['id' => '10000000000111']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantAuthorizationStatusWhenPartnerIdProvidedIsMerchant()
    {
        $this->fixtures->merchant->createAccount('10000000000111');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSaveMerchantAuthorization()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => '10000000000000', 'promoter_pan_name' => 'Test']);

        $this->ba->proxyAuth();

        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->once())->method('createLegalDocument')->withAnyParameters();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenMerchantMappedToPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => '10000000000000', 'promoter_pan_name' => 'Test']);

        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => '10000000000000',
            'entity_owner_id' => $partner->getId(),
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->ba->proxyAuth();

        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->once())->method('createLegalDocument')->withAnyParameters();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenInvalidPartnerIdProvided()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenInvalidMerchantIdProvided()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenPartnerIsNotAggregator()
    {
        $this->fixtures->merchant->createAccount('10000000000111');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenMerchantConsentIsAlreadyPresent()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->fixtures->merchant->createAccount('10000000000111');

        $this->fixtures->create('merchant_consents',
                                [
                                    'merchant_id' => '10000000000111',
                                    'consent_for' => 'PartnerAuth_Terms & Conditions',
                                    'status'      => 'initiated',
                                    'entity_id'   => $partner->getId(),
                                    'entity_type' => 'partner'
                                ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSaveMerchantAuthorizationWhenMerchantConsentIsPresentForAnotherPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant_detail', $partner->getId(), ['business_name' => 'Amazon Inc']);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => '10000000000000', 'promoter_pan_name' => 'Test']);

        list($secondPartner, $secondApp) = $this->createPartnerAndApplication(['id' => '10000000000001'], ['id' => '8ckeirnw84fdkf']);

        $this->fixtures->create('merchant_consents',
                                [
                                    'merchant_id' => '10000000000111',
                                    'consent_for' => 'PartnerAuth_Terms & Conditions',
                                    'status'      => 'initiated',
                                    'entity_id'   => $secondPartner->getId(),
                                    'entity_type' => 'partner'
                                ]);

        $this->ba->proxyAuth();

        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->once())->method('createLegalDocument')->withAnyParameters();

        $this->startTest();
    }
}
