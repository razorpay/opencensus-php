<?php


namespace Functional\Merchant\AutoKyc;


use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\Status;
use RZP\Services\MerchantRiskClient;
use RZP\Tests\Functional\TestCase;

class AutoKycTest extends TestCase
{


    public function testGetApplicableActivationStatusForMerchantNotYetRegistered()
    {
        $this->markTestSkipped("Skipping the test: fix it later. ");
        
        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
        ]);

        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantRiskFactor'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantRiskFactor')
            ->willReturn(['impersonated' => false]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $core->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantNotYetRegistered()
    {
        $this->markTestSkipped("Skipping the test: fix it later. ");

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
        ]);

        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantImpersonatedDetails')
            ->willReturn([
                'fields' => [
                    'field' =>[
                        'list' => 'blacklist'
                    ]
                ]
            ]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, $core->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForMerchantIndividual()
    {
        $this->markTestSkipped("Skipping the test: fix it later. ");

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
        ]);

        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantImpersonatedDetails')
            ->willReturn(['impersonated' => false]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $core->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantIndividual()
    {
        $this->markTestSkipped("Skipping the test: fix it later. ");

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS          => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
        ]);

        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantImpersonatedDetails')
            ->willReturn(['impersonated' => true]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, $core->getApplicableActivationStatus($merchantDetails));
    }
}
