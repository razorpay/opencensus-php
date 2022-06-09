<?php


namespace Functional\Merchant\AutoKyc;


use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Services\MerchantRiskClient;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Core as DetailCore;


class AutoKycTest extends TestCase
{
    public function testGetApplicableActivationStatusForMerchantNotYetRegistered()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE                    => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY                => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY             => 'accommodation',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantNotYetRegistered()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE                    => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY                => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY             => 'accommodation',
        ]);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForMerchantIndividual()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE                    => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
            Entity::BUSINESS_CATEGORY                => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY             => 'accommodation',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantIndividual()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE                    => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
            Entity::BUSINESS_CATEGORY                => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY             => 'accommodation',
        ]);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    protected function mockRazorxAndMerchantRiskClient()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $merchantRiskMock = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $this->app->instance('merchantRiskClient', $merchantRiskMock);
    }

    public function setImpersonatedMerchant()
    {
        $this->app->merchantRiskClient->method('getMerchantImpersonatedDetails')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id"   => "Gz5tpWukNj9e4l",
                "fields"      => [
                    [
                        "impersonation_id" => "Gz6Rhe3pKP5EXo",
                        "field" => "business_website",
                        "config_key" => "business_website",
                        "list" => "blacklist",
                        "score" => 1260,
                    ],
                ],
                "merchant_id" => "Gz5tpWukNj9e4l",
                "entity_type" => "merchant"
            ]);
    }

    public function setNonImpersonatedMerchant()
    {
        $this->app->merchantRiskClient->method('getMerchantImpersonatedDetails')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id"   => "Gz5tpWukNj9e4l",
                "entity_type" => "merchant"
            ]);
    }
}
